<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingParticipantRequest;
use App\Http\Requests\UpdateTrainingParticipantRequest;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingParticipant;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingParticipantController extends Controller
{
    public function __construct(private TrainingService $trainingService) {}

    /**
     * Daftarkan satu atau beberapa karyawan sekaligus. Karyawan yang sudah
     * terdaftar dilewati (bukan error) agar pendaftaran massal tetap jalan.
     */
    public function store(StoreTrainingParticipantRequest $request, Training $training): RedirectResponse
    {
        $employeeIds = $request->validated()['employee_ids'];

        $sudah = $training->participants()->pluck('employee_id')->all();
        $baru = array_values(array_diff($employeeIds, $sudah));

        if ($baru === []) {
            return back()->with('status', 'Semua karyawan yang dipilih sudah terdaftar.');
        }

        if ($training->quota !== null) {
            $terpakai = $training->participants()
                ->where('status', '!=', TrainingParticipant::STATUS_CANCELLED)
                ->count();

            if ($terpakai + count($baru) > $training->quota) {
                throw ValidationException::withMessages([
                    'employee_ids' => 'Kuota pelatihan tersisa '.max(0, $training->quota - $terpakai).'.',
                ]);
            }
        }

        DB::transaction(function () use ($training, $baru) {
            foreach ($baru as $employeeId) {
                $training->participants()->create([
                    'employee_id' => $employeeId,
                    'status' => TrainingParticipant::STATUS_REGISTERED,
                ]);
            }
        });

        return redirect()
            ->route('trainings.show', $training)
            ->with('status', count($baru).' karyawan didaftarkan.');
    }

    /**
     * Catat hasil pelatihan partisipan (status, skor, sertifikasi) dan
     * sinkronkan jumlah pelatihan karyawan ke fitur ML.
     */
    public function update(UpdateTrainingParticipantRequest $request, TrainingParticipant $participant): RedirectResponse
    {
        $data = $request->validated();

        if (in_array($data['status'], [TrainingParticipant::STATUS_COMPLETED, TrainingParticipant::STATUS_FAILED], true)
            && $participant->completed_at === null) {
            $data['completed_at'] = now();
        }

        if (! ($data['certified'] ?? false)) {
            $data['certificate_number'] = null;
        }

        DB::transaction(function () use ($participant, $data) {
            $participant->update($data);
            $this->trainingService->syncEmployeeTrainingCount($participant->employee);
        });

        return redirect()
            ->route('trainings.show', $participant->training_id)
            ->with('status', 'Hasil pelatihan disimpan.');
    }

    public function destroy(TrainingParticipant $participant): RedirectResponse
    {
        $this->authorize('manageParticipants', $participant->training);

        $training = $participant->training;

        DB::transaction(function () use ($participant) {
            $employee = $participant->employee;
            $participant->delete();
            $this->trainingService->syncEmployeeTrainingCount($employee);
        });

        return redirect()
            ->route('trainings.show', $training)
            ->with('status', 'Partisipan dihapus dari pelatihan.');
    }
}
