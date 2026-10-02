<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
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

        return view('employees.show', compact('employee', 'predictions'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.edit', compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->payload($request));

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "Data \"{$employee->name}\" berhasil diperbarui.");
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
