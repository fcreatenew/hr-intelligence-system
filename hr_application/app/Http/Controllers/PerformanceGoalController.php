<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePerformanceGoalRequest;
use App\Http\Requests\UpdatePerformanceGoalRequest;
use App\Models\Employee;
use App\Models\PerformanceGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceGoalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PerformanceGoal::class);

        $goals = PerformanceGoal::query()
            ->with('employee')
            ->when($request->filled('period'), fn ($q) => $q->where('period', $request->string('period')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderByDesc('period')
            ->paginate(20)
            ->withQueryString();

        return view('performance.goals.index', [
            'goals' => $goals,
            'statuses' => PerformanceGoal::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
            'filters' => [
                'period' => $request->string('period')->toString(),
                'status' => $request->string('status')->toString(),
                'employee_id' => $request->string('employee_id')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PerformanceGoal::class);

        return view('performance.goals.create', [
            'goal' => new PerformanceGoal([
                'period' => now()->format('Y').'-Q'.now()->quarter,
                'status' => PerformanceGoal::STATUS_DRAFT,
                'weight' => 0,
            ]),
            'statuses' => PerformanceGoal::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function store(StorePerformanceGoalRequest $request): RedirectResponse
    {
        PerformanceGoal::create($request->validated());

        return redirect()
            ->route('performance-goals.index')
            ->with('status', 'Sasaran kinerja dibuat.');
    }

    public function edit(PerformanceGoal $performanceGoal): View
    {
        $this->authorize('update', $performanceGoal);

        return view('performance.goals.edit', [
            'goal' => $performanceGoal,
            'statuses' => PerformanceGoal::STATUSES,
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function update(UpdatePerformanceGoalRequest $request, PerformanceGoal $performanceGoal): RedirectResponse
    {
        $performanceGoal->update($request->validated());

        return redirect()
            ->route('performance-goals.index')
            ->with('status', 'Sasaran kinerja diperbarui.');
    }

    public function destroy(PerformanceGoal $performanceGoal): RedirectResponse
    {
        $this->authorize('delete', $performanceGoal);

        $performanceGoal->delete();

        return redirect()
            ->route('performance-goals.index')
            ->with('status', 'Sasaran kinerja dihapus.');
    }
}
