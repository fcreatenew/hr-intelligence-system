<?php

namespace App\Services\Mappers;

use App\Contracts\EmployeeFeatureMapper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DefaultEmployeeFeatureMapper implements EmployeeFeatureMapper
{
    /**
     * Nilai default bila record karyawan tidak punya atribut yang dibutuhkan
     * use case non-turnover. Tipe nilai default menentukan cast:
     * int -> (int), float -> (float).
     */
    private const DEFAULTS = [
        'jarak_km' => 10.0,
        'beban_kerja' => 70.0,
        'kepuasan_kerja' => 3.5,
        'riwayat_absen_90hari' => 0,
        'jumlah_training_setahun' => 2,
        'proyek_selesai' => 5,
        'skor_umpan_balik_360' => 3.5,
        'hari_absen_setahun' => 3,
        'jam_lembur_bulanan' => 0.0,
        'tunjangan' => 0.0,
        'lembur_jam' => 0,
        'tarif_lembur' => 0.0,
        'potongan' => 0.0,
        'bulan_sejak_promosi' => 0.0,
        'level' => 'Staff',
    ];

    public function map(mixed $employee): array
    {
        $tanggalPromosi = $this->value($employee, 'tanggal_promosi');

        $tanggalMasuk = $this->value($employee, 'tanggal_masuk')
            ?? $this->value($employee, 'join_date');

        return [
            'employee_id' => (string) $this->employeeId($employee),
            'usia' => (int) $this->value($employee, 'usia'),
            'masa_kerja_bulan' => (int) $this->value($employee, 'masa_kerja_bulan'),
            'jam_lembur_bulanan' => (int) $this->number($employee, 'jam_lembur_bulanan'),
            'gaji_bulanan' => (float) ($this->value($employee, 'gaji_bulanan')
                ?? $this->value($employee, 'gaji_pokok')),
            'tanggal_masuk' => $this->date($tanggalMasuk),
            'tanggal_promosi' => $tanggalPromosi ? $this->date($tanggalPromosi) : null,
            'departemen' => $this->departemen($employee),
            'status_kerja' => $this->statusKerja($employee),
            'pendidikan' => $this->pendidikan($employee),
        ];
    }

    public function mapMany(iterable $employees): array
    {
        return Collection::make($employees)->map(fn ($e) => $this->map($e))->values()->all();
    }

    public function mapForUseCase(string $useCase, mixed $employee): array
    {
        if ($useCase === 'turnover') {
            return $this->map($employee);
        }

        return match ($useCase) {
            'absensi' => $this->mapAbsensi($employee),
            'kinerja' => $this->mapKinerja($employee),
            'payroll' => $this->mapPayroll($employee),
            'survival' => $this->mapSurvival($employee),
            default => throw new \InvalidArgumentException(
                sprintf('Use case HR Intelligence tidak dikenal: %s', $useCase)
            ),
        };
    }

    public function mapManyForUseCase(string $useCase, iterable $employees): array
    {
        return Collection::make($employees)
            ->map(fn ($e) => $this->mapForUseCase($useCase, $e))
            ->values()
            ->all();
    }

    private function mapAbsensi(mixed $employee): array
    {
        return [
            'employee_id' => (string) $this->employeeId($employee),
            'usia' => (int) $this->value($employee, 'usia'),
            'masa_kerja_bulan' => (int) $this->value($employee, 'masa_kerja_bulan'),
            'jarak_km' => $this->number($employee, 'jarak_km'),
            'beban_kerja' => $this->number($employee, 'beban_kerja'),
            'lembur_jam_bulanan' => $this->number($employee, 'jam_lembur_bulanan'),
            'kepuasan_kerja' => $this->number($employee, 'kepuasan_kerja'),
            'riwayat_absen_90hari' => $this->number($employee, 'riwayat_absen_90hari'),
            'departemen' => $this->departemen($employee),
            'status_kerja' => $this->statusKerja($employee),
            'pendidikan' => $this->pendidikan($employee),
        ];
    }

    private function mapKinerja(mixed $employee): array
    {
        return [
            'employee_id' => (string) $this->employeeId($employee),
            'usia' => (int) $this->value($employee, 'usia'),
            'masa_kerja_bulan' => (int) $this->value($employee, 'masa_kerja_bulan'),
            'jam_lembur_bulanan' => $this->number($employee, 'jam_lembur_bulanan'),
            'jumlah_training_setahun' => $this->number($employee, 'jumlah_training_setahun'),
            'proyek_selesai' => $this->number($employee, 'proyek_selesai'),
            'skor_umpan_balik_360' => $this->number($employee, 'skor_umpan_balik_360'),
            'hari_absen_setahun' => $this->number($employee, 'hari_absen_setahun'),
            'departemen' => $this->departemen($employee),
            'pendidikan' => $this->pendidikan($employee),
        ];
    }

    private function mapPayroll(mixed $employee): array
    {
        $gajiPokok = $this->value($employee, 'gaji_pokok')
            ?? $this->value($employee, 'gaji_bulanan');

        return [
            'employee_id' => (string) $this->employeeId($employee),
            'periode' => (string) ($this->value($employee, 'periode') ?? now()->format('Y-m')),
            'departemen' => $this->departemen($employee),
            'level' => (string) ($this->value($employee, 'level') ?? self::DEFAULTS['level']),
            'gaji_pokok' => (float) $gajiPokok,
            'tunjangan' => (float) ($this->value($employee, 'tunjangan') ?? self::DEFAULTS['tunjangan']),
            'lembur_jam' => (int) ($this->value($employee, 'lembur_jam') ?? self::DEFAULTS['lembur_jam']),
            'tarif_lembur' => (float) ($this->value($employee, 'tarif_lembur') ?? self::DEFAULTS['tarif_lembur']),
            'potongan' => (float) ($this->value($employee, 'potongan') ?? self::DEFAULTS['potongan']),
            'total_gaji' => (float) ($this->value($employee, 'total_gaji') ?? $gajiPokok),
        ];
    }

    private function mapSurvival(mixed $employee): array
    {
        $gaji = $this->value($employee, 'gaji_bulanan')
            ?? $this->value($employee, 'gaji_pokok');

        return [
            'employee_id' => (string) $this->employeeId($employee),
            'usia' => (int) $this->value($employee, 'usia'),
            'gaji_bulanan' => (float) $gaji,
            'jam_lembur_bulanan' => $this->number($employee, 'jam_lembur_bulanan'),
            'kepuasan_kerja' => $this->number($employee, 'kepuasan_kerja'),
            'bulan_sejak_promosi' => $this->bulanSejakPromosi($employee),
            'jarak_km' => $this->number($employee, 'jarak_km'),
            'departemen' => $this->departemen($employee),
            'pendidikan' => $this->pendidikan($employee),
        ];
    }

    /**
     * Ambil nilai atribut dari array, Eloquent model, atau objek biasa.
     * Model Eloquent mendukung accessor (usia, masa_kerja_bulan).
     */
    private function value(mixed $employee, string $key): mixed
    {
        if (is_array($employee)) {
            return $employee[$key] ?? null;
        }

        return $employee->{$key} ?? null;
    }

    private function number(mixed $employee, string $key): float|int
    {
        $default = self::DEFAULTS[$key] ?? 0;
        $raw = $this->value($employee, $key) ?? $default;

        return is_int($default) ? (int) $raw : (float) $raw;
    }

    private function bulanSejakPromosi(mixed $employee): float
    {
        $explicit = $this->value($employee, 'bulan_sejak_promosi');

        if ($explicit !== null) {
            return (float) $explicit;
        }

        $promosi = $this->value($employee, 'tanggal_promosi');

        if ($promosi) {
            return (float) Carbon::parse($promosi)->diffInMonths(now());
        }

        return 0.0;
    }

    private const DEPARTEMEN_DIIZINKAN = [
        'Sales', 'Marketing', 'HR', 'Operations', 'IT', 'Finance', 'Legal',
    ];

    private const PENDIDIKAN_DIIZINKAN = ['SMA', 'Diploma', 'S1', 'S2'];

    private const STATUS_KERJA_DIIZINKAN = ['Tetap', 'Kontrak', 'Freelance'];

    private function employeeId(mixed $employee): string
    {
        $id = $this->value($employee, 'employee_id')
            ?? $this->value($employee, 'nik')
            ?? $this->value($employee, 'id');

        return (string) $id;
    }

    private function departemen(mixed $employee): string
    {
        $nilai = $this->value($employee, 'departemen')
            ?? $this->value($employee, 'department');

        return in_array($nilai, self::DEPARTEMEN_DIIZINKAN, true)
            ? (string) $nilai
            : 'Operations';
    }

    private function statusKerja(mixed $employee): string
    {
        $nilai = $this->value($employee, 'status_kerja');

        return in_array($nilai, self::STATUS_KERJA_DIIZINKAN, true)
            ? (string) $nilai
            : 'Tetap';
    }

    private function pendidikan(mixed $employee): string
    {
        $nilai = $this->value($employee, 'pendidikan');

        return in_array($nilai, self::PENDIDIKAN_DIIZINKAN, true)
            ? (string) $nilai
            : 'S1';
    }

    private function date(mixed $value): string
    {
        return Carbon::parse($value)->format('Y-m-d');
    }
}
