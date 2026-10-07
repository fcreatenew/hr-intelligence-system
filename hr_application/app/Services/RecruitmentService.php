<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Employee;
use App\Models\JobOpening;
use Illuminate\Support\Facades\DB;

/**
 * Menjembatani rekrutmen ke data karyawan inti: kandidat yang diterima
 * dibuatkan record `employees`, sehingga datanya langsung mengalir ke fitur
 * model ML (turnover/kinerja) lewat kontrak API yang sudah ada.
 */
class RecruitmentService
{
    /**
     * Tandai kandidat diterima dan buat record karyawan.
     *
     * @param  array<string, mixed>  $employeeData  Override data karyawan (mis. gaji, level).
     */
    public function hire(Candidate $candidate, array $employeeData = []): Employee
    {
        return DB::transaction(function () use ($candidate, $employeeData) {
            $opening = $candidate->jobOpening;

            $employee = Employee::create(array_merge(
                $this->defaultEmployeeData($candidate, $opening),
                $employeeData,
            ));

            $candidate->update([
                'stage' => Candidate::STAGE_HIRED,
                'notes' => trim(($candidate->notes ? $candidate->notes."\n" : '')."Diterima sebagai {$employee->nik}."),
            ]);

            return $employee;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultEmployeeData(Candidate $candidate, ?JobOpening $opening): array
    {
        return [
            'nik' => 'CAND-'.str_pad((string) $candidate->id, 5, '0', STR_PAD_LEFT),
            'name' => $candidate->name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'department' => $opening?->department ?? 'Umum',
            'position' => $opening?->title ?? 'Staff',
            'join_date' => now()->toDateString(),
            'status' => true,
            'status_kerja' => $this->mapEmploymentType($opening?->employment_type),
            'gaji_bulanan' => $candidate->expected_salary,
        ];
    }

    private function mapEmploymentType(?string $type): string
    {
        return match ($type) {
            'kontrak' => 'Kontrak',
            'magang' => 'Magang',
            'freelance' => 'Freelance',
            default => 'Tetap',
        };
    }
}
