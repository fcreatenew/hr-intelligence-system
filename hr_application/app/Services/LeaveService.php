<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alur persetujuan cuti. Persetujuan menulis dua hal sekaligus: status
 * pengajuan dan saldo cuti, plus catatan absensi harian bertipe `cuti`.
 *
 * Semua ditulis dalam satu transaksi agar tidak ada kondisi di mana cuti
 * disetujui tetapi saldo tidak ikut berkurang (atau sebaliknya).
 */
class LeaveService
{
    /**
     * Setujui pengajuan cuti.
     *
     * @throws RuntimeException bila saldo tidak mencukupi
     */
    public function approve(LeaveRequest $leaveRequest, User $approver, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $note) {
            $leaveRequest->refresh();

            if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
                throw new RuntimeException('Pengajuan ini sudah diputuskan sebelumnya.');
            }

            if ($leaveRequest->consumesBalance()) {
                $balance = $leaveRequest->employee->leaveBalanceFor((int) $leaveRequest->start_date->year);

                if ($balance->remaining_days < $leaveRequest->days) {
                    throw new RuntimeException(sprintf(
                        'Saldo cuti tidak cukup: tersisa %d hari, diminta %d hari.',
                        $balance->remaining_days,
                        $leaveRequest->days,
                    ));
                }

                $balance->increment('used_days', $leaveRequest->days);
            }

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'decided_by' => $approver->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            $this->writeAttendance($leaveRequest);

            return $leaveRequest;
        });
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, ?string $note = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $note) {
            $leaveRequest->refresh();

            if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
                throw new RuntimeException('Pengajuan ini sudah diputuskan sebelumnya.');
            }

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'decided_by' => $approver->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            return $leaveRequest;
        });
    }

    /**
     * Tulis catatan absensi bertipe `cuti` untuk setiap **hari kerja** dalam
     * rentang. Akhir pekan dan hari libur dilewati karena tidak dihitung
     * sebagai cuti. Catatan tidak hadir yang lebih spesifik (sakit/izin/alpha)
     * tidak ditimpa, karena itu data nyata yang tidak boleh hilang hanya karena
     * cuti disetujui.
     */
    private function writeAttendance(LeaveRequest $leaveRequest): void
    {
        $date = $leaveRequest->start_date->copy();
        $holidays = Holiday::between(
            $leaveRequest->start_date->toImmutable(),
            $leaveRequest->end_date->toImmutable(),
        );

        while ($date->lte($leaveRequest->end_date)) {
            if ($date->isWeekend() || in_array($date->toDateString(), $holidays, true)) {
                $date->addDay();

                continue;
            }

            $existing = Attendance::query()
                ->where('employee_id', $leaveRequest->employee_id)
                ->where('date', $date->toDateString())
                ->first();

            if ($existing && in_array($existing->status, Attendance::ABSENCE_STATUSES, true)) {
                $date->addDay();

                continue;
            }

            Attendance::updateOrCreate(
                ['employee_id' => $leaveRequest->employee_id, 'date' => $date->toDateString()],
                ['status' => Attendance::STATUS_CUTI, 'note' => 'Cuti disetujui #'.$leaveRequest->id],
            );

            $date->addDay();
        }
    }
}
