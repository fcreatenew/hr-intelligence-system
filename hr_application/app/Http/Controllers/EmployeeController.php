<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\TerminateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($request->filled('department'), fn ($query) => $query->where('department', $request->string('department')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status') === 'aktif'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Employee::query()->distinct()->orderBy('department')->pluck('department'),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'department' => $request->string('department')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', ['employee' => new Employee(['status' => true, 'join_date' => now()])]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($this->payload($request));

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "Karyawan \"{$employee->name}\" berhasil ditambahkan.");
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $predictions = $employee->predictions()
            ->orderByDesc('predicted_at')
            ->limit(20)
            ->get();

        $histories = $employee->histories()
            ->with('changedBy:id,name')
            ->limit(50)
            ->get();

        return view('employees.show', compact('employee', 'predictions', 'histories'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.edit', compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $before = [
            'department' => $employee->department,
            'position' => $employee->position,
            'gaji_bulanan' => $employee->gaji_bulanan,
            'status' => (bool) $employee->status,
        ];

        $employee->update($this->payload($request));

        $this->recordMeaningfulChanges($employee, $before);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "Data \"{$employee->name}\" berhasil diperbarui.");
    }

    /**
     * Catat mutasi/promosi/perubahan gaji/status ke linimasa karyawan.
     *
     * @param  array<string, mixed>  $before
     */
    private function recordMeaningfulChanges(Employee $employee, array $before): void
    {
        if ($before['department'] !== $employee->department) {
            $employee->recordHistory(
                EmployeeHistory::EVENT_DEPARTMENT,
                'department',
                $before['department'],
                $employee->department,
            );
        }

        if ($before['position'] !== $employee->position) {
            $employee->recordHistory(
                EmployeeHistory::EVENT_POSITION,
                'position',
                $before['position'],
                $employee->position,
            );
        }

        if ((int) $before['gaji_bulanan'] !== (int) $employee->gaji_bulanan) {
            $employee->recordHistory(
                EmployeeHistory::EVENT_SALARY,
                'gaji_bulanan',
                $before['gaji_bulanan'],
                $employee->gaji_bulanan,
            );
        }

        if ($before['status'] !== (bool) $employee->status) {
            $employee->recordHistory(
                EmployeeHistory::EVENT_STATUS,
                'status',
                $before['status'] ? 'Aktif' : 'Nonaktif',
                $employee->status ? 'Aktif' : 'Nonaktif',
            );
        }
    }

    /**
     * Hentikan karyawan: simpan tanggal & alasan keluar, set status nonaktif,
     * dan catat ke linimasa.
     */
    public function terminate(TerminateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update([
            'termination_date' => $request->date('termination_date'),
            'termination_reason' => $request->string('termination_reason')->toString(),
            'status' => false,
        ]);

        $employee->recordHistory(
            EmployeeHistory::EVENT_TERMINATED,
            'termination_date',
            null,
            $employee->termination_date?->toDateString(),
            $request->string('termination_note')->toString() ?: null,
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "Karyawan \"{$employee->name}\" ditandai berhenti kerja.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $name = $employee->name;
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('status', "Karyawan \"{$name}\" berhasil dihapus.");
    }

    /**
     * Hanya kolom yang boleh diisi dari form. Kolom ML yang tidak dikirim
     * (mis. saat integrasi belum berjalan) dibiarkan null agar model memakai
     * nilai default di mapper, bukan angka nol yang menyesatkan.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $data = $request->safe()->except(['_token', '_method']);
        $data['status'] = $request->boolean('status');

        return array_filter(
            $data,
            fn ($value) => $value !== '' && $value !== null,
        ) + ['status' => $data['status']];
    }
}
