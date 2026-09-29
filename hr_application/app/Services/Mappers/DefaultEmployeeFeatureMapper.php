<?php

namespace App\Services\Mappers;

use App\Contracts\EmployeeFeatureMapper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DefaultEmployeeFeatureMapper implements EmployeeFeatureMapper
{
    public function map(mixed $employee): array
    {
        $get = fn (string $key) => is_array($employee) ? ($employee[$key] ?? null) : ($employee->{$key} ?? null);

        $tanggalPromosi = $get('tanggal_promosi');

        return [
            'employee_id' => (string) $get('employee_id'),
            'usia' => (int) $get('usia'),
            'masa_kerja_bulan' => (int) $get('masa_kerja_bulan'),
            'jam_lembur_bulanan' => (int) $get('jam_lembur_bulanan'),
            'gaji_bulanan' => (float) $get('gaji_bulanan'),
            'tanggal_masuk' => Carbon::parse($get('tanggal_masuk'))->format('Y-m-d'),
            'tanggal_promosi' => $tanggalPromosi ? Carbon::parse($tanggalPromosi)->format('Y-m-d') : null,
            'departemen' => (string) $get('departemen'),
            'status_kerja' => (string) $get('status_kerja'),
            'pendidikan' => (string) $get('pendidikan'),
        ];
    }

    public function mapMany(iterable $employees): array
    {
        return Collection::make($employees)->map(fn ($e) => $this->map($e))->values()->all();
    }
}
