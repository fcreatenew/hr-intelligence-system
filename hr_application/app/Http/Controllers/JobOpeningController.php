<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobOpeningRequest;
use App\Http\Requests\UpdateJobOpeningRequest;
use App\Models\JobOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobOpeningController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', JobOpening::class);

        $openings = JobOpening::query()
            ->withCount('candidates')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->string('department')))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('recruitment.index', [
            'openings' => $openings,
            'statuses' => JobOpening::STATUSES,
            'departments' => JobOpening::query()->distinct()->orderBy('department')->pluck('department'),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'department' => $request->string('department')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', JobOpening::class);

        return view('recruitment.create', [
            'opening' => new JobOpening([
                'employment_type' => 'tetap',
                'status' => JobOpening::STATUS_DRAFT,
                'quota' => 1,
            ]),
            'statuses' => JobOpening::STATUSES,
            'employmentTypes' => JobOpening::EMPLOYMENT_TYPES,
        ]);
    }

    public function store(StoreJobOpeningRequest $request): RedirectResponse
    {
        $opening = JobOpening::create($request->validated() + ['created_by' => $request->user()->id]);

        return redirect()
            ->route('job-openings.show', $opening)
            ->with('status', "Lowongan \"{$opening->title}\" dibuat.");
    }

    public function show(JobOpening $jobOpening): View
    {
        $this->authorize('view', $jobOpening);

        return view('recruitment.show', [
            'opening' => $jobOpening->load('candidates'),
            'stages' => \App\Models\Candidate::STAGES,
        ]);
    }

    public function edit(JobOpening $jobOpening): View
    {
        $this->authorize('update', $jobOpening);

        return view('recruitment.edit', [
            'opening' => $jobOpening,
            'statuses' => JobOpening::STATUSES,
            'employmentTypes' => JobOpening::EMPLOYMENT_TYPES,
        ]);
    }

    public function update(UpdateJobOpeningRequest $request, JobOpening $jobOpening): RedirectResponse
    {
        $jobOpening->update($request->validated());

        return redirect()
            ->route('job-openings.show', $jobOpening)
            ->with('status', 'Lowongan diperbarui.');
    }

    public function destroy(JobOpening $jobOpening): RedirectResponse
    {
        $this->authorize('delete', $jobOpening);

        $jobOpening->delete();

        return redirect()
            ->route('job-openings.index')
            ->with('status', 'Lowongan dihapus.');
    }
}
