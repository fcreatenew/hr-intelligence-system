<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Models\Candidate;
use App\Models\JobOpening;
use App\Services\RecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function __construct(private readonly RecruitmentService $recruitment) {}

    public function store(StoreCandidateRequest $request, JobOpening $jobOpening): RedirectResponse
    {
        $jobOpening->candidates()->create($request->validated());

        return redirect()
            ->route('job-openings.show', $jobOpening)
            ->with('status', 'Kandidat ditambahkan.');
    }

    public function edit(Candidate $candidate): View
    {
        $this->authorize('update', $candidate);

        return view('recruitment.candidate-edit', [
            'candidate' => $candidate,
            'stages' => Candidate::STAGES,
        ]);
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate): RedirectResponse
    {
        $candidate->update($request->validated());

        return redirect()
            ->route('job-openings.show', $candidate->job_opening_id)
            ->with('status', 'Kandidat diperbarui.');
    }

    /** Terima kandidat dan buat record karyawan (menyambung ke fitur ML). */
    public function hire(Candidate $candidate): RedirectResponse
    {
        $this->authorize('update', $candidate);

        if ($candidate->stage === Candidate::STAGE_HIRED) {
            return back()->with('status', 'Kandidat sudah diterima sebelumnya.');
        }

        $employee = $this->recruitment->hire($candidate);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', "Kandidat diterima sebagai karyawan {$employee->nik}.");
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->authorize('delete', $candidate);

        $opening = $candidate->job_opening_id;
        $candidate->delete();

        return redirect()
            ->route('job-openings.show', $opening)
            ->with('status', 'Kandidat dihapus.');
    }
}
