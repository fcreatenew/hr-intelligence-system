<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompensationComponentRequest;
use App\Http\Requests\UpdateCompensationComponentRequest;
use App\Models\CompensationComponent;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationComponentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CompensationComponent::class);

        $components = CompensationComponent::query()
            ->with('employee')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderByDesc('effective_date')
            ->paginate(20)
            ->withQueryString();

        return view('compensation.index', [
            'components' => $components,
            'types' => CompensationComponent::TYPES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'filters' => [
                'type' => $request->string('type')->toString(),
                'employee_id' => $request->string('employee_id')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', CompensationComponent::class);

        return view('compensation.create', [
            'component' => new CompensationComponent([
                'type' => CompensationComponent::TYPE_ALLOWANCE,
                'recurring' => true,
                'effective_date' => now(),
            ]),
            'types' => CompensationComponent::TYPES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function store(StoreCompensationComponentRequest $request): RedirectResponse
    {
        CompensationComponent::create($request->validated());

        return redirect()
            ->route('compensation-components.index')
            ->with('status', 'Komponen kompensasi dibuat.');
    }

    public function edit(CompensationComponent $compensationComponent): View
    {
        $this->authorize('update', $compensationComponent);

        return view('compensation.edit', [
            'component' => $compensationComponent,
            'types' => CompensationComponent::TYPES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function update(UpdateCompensationComponentRequest $request, CompensationComponent $compensationComponent): RedirectResponse
    {
        $compensationComponent->update($request->validated());

        return redirect()
            ->route('compensation-components.index')
            ->with('status', 'Komponen kompensasi diperbarui.');
    }

    public function destroy(CompensationComponent $compensationComponent): RedirectResponse
    {
        $this->authorize('delete', $compensationComponent);

        $compensationComponent->delete();

        return redirect()
            ->route('compensation-components.index')
            ->with('status', 'Komponen kompensasi dihapus.');
    }
}
