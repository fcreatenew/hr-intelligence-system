<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $report = $this->report($request);

        return view('reports.index', [
            'departments' => $report['departments'],
            'totals' => $report['totals'],
            'filters' => ['from' => $report['from'], 'to' => $report['to']],
        ]);
    }

    /**
     * Ekspor rekap per departemen sebagai CSV.
     *
     * Memakai `streamDownload` agar tetap hemat memori walau jumlah karyawan
     * bertambah banyak; format CSV dipilih supaya bisa langsung dibuka di
     * Excel/Sheets tanpa dependensi tambahan.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $report = $this->report($request);
        $filename = 'laporan-hr-'.$report['from'].'-'.$report['to'].'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar

            fputcsv($out, ['Departemen', 'Karyawan', 'Aktif', 'Non-aktif', 'Keluar (periode)', 'Rasio turnover (%)', 'Total gaji bulanan', 'Rata-rata tidak hadir']);

            foreach ($report['departments'] as $row) {
                fputcsv($out, [
                    $row['department'],
                    $row['total'],
                    $row['aktif'],
                    $row['non_aktif'],
                    $row['keluar'],
                    $row['turnover_rate'],
                    $row['total_gaji'],
                    $row['rata_tidak_hadir'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Total', $report['totals']['total'], $report['totals']['aktif'], $report['totals']['non_aktif'], $report['totals']['keluar'], $report['totals']['turnover_rate'], $report['totals']['total_gaji'], '']);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Susun angka laporan dari data HRIS.
     *
     * Rasio turnover dihitung dari jumlah karyawan yang **keluar dalam rentang
     * periode** dibagi total karyawan per departemen. Tanggal keluar
     * (`termination_date`) dipakai bila tersedia; karyawan berstatus non-aktif
     * tanpa tanggal keluar (data lama) tetap dihitung sebagai keluar agar
     * laporan tidak kehilangan angka.
     *
     * @return array<string, mixed>
     */
    private function report(Request $request): array
    {
        $from = $request->string('from')->toString() ?: CarbonImmutable::now()->startOfYear()->toDateString();
        $to = $request->string('to')->toString() ?: CarbonImmutable::now()->toDateString();

        $absences = Attendance::query()
            ->betweenDates($from, $to)
            ->absence()
            ->select('employee_id', DB::raw('count(*) as jumlah'))
            ->groupBy('employee_id')
            ->pluck('jumlah', 'employee_id');

        $departments = Employee::query()
            ->orderBy('department')
            ->get()
            ->groupBy('department')
            ->map(function ($employees, string $department) use ($absences, $from, $to) {
                $total = $employees->count();
                $aktif = $employees->where('status', true)->count();
                $nonAktif = $total - $aktif;

                $keluar = $employees->filter(function (Employee $employee) use ($from, $to) {
                    if ($employee->termination_date !== null) {
                        $tanggal = $employee->termination_date->toDateString();

                        return $tanggal >= $from && $tanggal <= $to;
                    }

                    return $employee->status === false;
                })->count();

                return [
                    'department' => $department,
                    'total' => $total,
                    'aktif' => $aktif,
                    'non_aktif' => $nonAktif,
                    'keluar' => $keluar,
                    'turnover_rate' => $total > 0 ? round($keluar / $total * 100, 1) : 0.0,
                    'total_gaji' => (float) $employees->where('status', true)->sum('gaji_bulanan'),
                    'rata_tidak_hadir' => $total > 0
                        ? round($employees->sum(fn (Employee $e) => (int) ($absences[$e->id] ?? 0)) / $total, 1)
                        : 0.0,
                ];
            })
            ->values();

        $totalGaji = (float) $departments->sum('total_gaji');
        $totalKaryawan = (int) $departments->sum('total');

        return [
            'from' => $from,
            'to' => $to,
            'departments' => $departments,
            'totals' => [
                'total' => $totalKaryawan,
                'aktif' => (int) $departments->sum('aktif'),
                'non_aktif' => (int) $departments->sum('non_aktif'),
                'keluar' => (int) $departments->sum('keluar'),
                'turnover_rate' => $totalKaryawan > 0
                    ? round((int) $departments->sum('keluar') / $totalKaryawan * 100, 1)
                    : 0.0,
                'total_gaji' => $totalGaji,
            ],
        ];
    }
}
