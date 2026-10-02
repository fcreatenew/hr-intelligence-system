<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceImportService;
use App\Services\AttendanceMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceMetricsService $metrics,
        private AttendanceImportService $importer,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $from = $request->string('from')->toString() ?: CarbonImmutable::now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: CarbonImmutable::now()->toDateString();

        $attendances = Attendance::query()
            ->with('employee')
            ->betweenDates($from, $to)
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        return view('attendances.index', [
            'attendances' => $attendances,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'statuses' => Attendance::statuses(),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'employee_id' => $request->string('employee_id')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Attendance::class);

        return view('attendances.create', [
            'attendance' => new Attendance([
                'date' => now(),
                'employee_id' => $request->integer('employee_id') ?: null,
                'status' => Attendance::STATUS_HADIR,
            ]),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'statuses' => Attendance::statuses(),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $attendance = Attendance::create($request->validated());

        return redirect()
            ->route('attendances.index', ['employee_id' => $attendance->employee_id])
            ->with('status', 'Catatan absensi berhasil disimpan.');
    }

    public function edit(Attendance $attendance): View
    {
        $this->authorize('update', $attendance);

        return view('attendances.edit', [
            'attendance' => $attendance,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'statuses' => Attendance::statuses(),
        ]);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $attendance->update($request->validated());

        return redirect()
            ->route('attendances.index', ['employee_id' => $attendance->employee_id])
            ->with('status', 'Catatan absensi berhasil diperbarui.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);

        $employeeId = $attendance->employee_id;
        $attendance->delete();

        return redirect()
            ->route('attendances.index', ['employee_id' => $employeeId])
            ->with('status', 'Catatan absensi dihapus.');
    }

    /**
     * Impor absensi dari CSV mesin absensi/fingerprint.
     */
    public function import(ImportAttendanceRequest $request): RedirectResponse
    {
        $result = $this->importer->import($request->file('file'));

        $status = "Impor selesai: {$result['imported']} baris baru, {$result['updated']} baris diperbarui.";
        $skipped = count($result['skipped']);

        if ($skipped > 0) {
            $status .= " {$skipped} baris dilewati.";
        }

        return redirect()
            ->route('attendances.index')
            ->with('status', $status)
            ->with('import_skipped', array_slice($result['skipped'], 0, 25));
    }

    /**
     * Unduh berkas contoh agar format impor jelas bagi operator.
     */
    public function importTemplate(): StreamedResponse
    {
        $this->authorize('create', Attendance::class);

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['nik', 'date', 'in', 'out', 'status']);
            fputcsv($handle, ['EMP0001', '2026-05-04', '08:55', '17:30', 'hadir']);
            fputcsv($handle, ['EMP0002', '2026-05-04', '09:20', '17:00', 'terlambat']);

            fclose($handle);
        }, 'template-absensi.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Rekap per karyawan: kehadiran, keterlambatan, dan hari tidak hadir —
     * termasuk angka yang dipakai model ML, agar dapat ditelusuri asalnya.
     */
    public function recap(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $from = $request->string('from')->toString() ?: CarbonImmutable::now()->subDays(89)->toDateString();
        $to = $request->string('to')->toString() ?: CarbonImmutable::now()->toDateString();

        $rows = Employee::query()
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($from, $to) {
                $base = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->betweenDates($from, $to);

                return [
                    'employee' => $employee,
                    'hadir' => (clone $base)->where('status', Attendance::STATUS_HADIR)->count(),
                    'terlambat' => (clone $base)->where('status', Attendance::STATUS_TERLAMBAT)->count(),
                    'tidak_hadir' => (clone $base)->absence()->count(),
                    'cuti' => (clone $base)->where('status', Attendance::STATUS_CUTI)->count(),
                    'late_minutes' => (int) (clone $base)->sum('late_minutes'),
                    'overtime_minutes' => (int) (clone $base)->sum('overtime_minutes'),
                    'riwayat_absen_90hari' => $this->metrics->absencesInLastDays($employee, 90),
                    'hari_absen_setahun' => $this->metrics->absencesInLastDays($employee, 365),
                ];
            });

        return view('attendances.recap', [
            'rows' => $rows,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }
}
