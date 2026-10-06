<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingRequest;
use App\Http\Requests\UpdateTrainingRequest;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Training::class);

        $trainings = Training::query()
            ->withCount('participants')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderByDesc('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('trainings.index', [
            'trainings' => $trainings,
            'statuses' => Training::STATUSES,
            'categories' => Training::CATEGORIES,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'category' => $request->string('category')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Training::class);

        return view('trainings.create', [
            'training' => new Training([
                'mode' => 'online',
                'status' => Training::STATUS_DRAFT,
                'start_date' => now(),
                'cost' => 0,
            ]),
            'categories' => Training::CATEGORIES,
            'modes' => Training::MODES,
            'statuses' => Training::STATUSES,
        ]);
    }

    public function store(StoreTrainingRequest $request): RedirectResponse
    {
        $training = Training::create($request->validated());

        return redirect()
            ->route('trainings.show', $training)
            ->with('status', "Pelatihan \"{$training->title}\" dibuat.");
    }

    public function show(Training $training): View
    {
        $this->authorize('view', $training);

        $terdaftar = $training->participants()
            ->with('employee')
            ->get();

        $employeeIds = $terdaftar->pluck('employee_id');

        return view('trainings.show', [
            'training' => $training,
            'participants' => $terdaftar,
            'availableEmployees' => \App\Models\Employee::query()
                ->whereNotIn('id', $employeeIds)
                ->orderBy('name')
                ->get(['id', 'name', 'nik']),
        ]);
    }

    public function edit(Training $training): View
    {
        $this->authorize('update', $training);

        return view('trainings.edit', [
            'training' => $training,
            'categories' => Training::CATEGORIES,
            'modes' => Training::MODES,
            'statuses' => Training::STATUSES,
        ]);
    }

    public function update(UpdateTrainingRequest $request, Training $training): RedirectResponse
    {
        $training->update($request->validated());

        return redirect()
            ->route('trainings.show', $training)
            ->with('status', 'Pelatihan diperbarui.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        $this->authorize('delete', $training);

        $training->delete();

        return redirect()
            ->route('trainings.index')
            ->with('status', 'Pelatihan dihapus.');
    }
}
