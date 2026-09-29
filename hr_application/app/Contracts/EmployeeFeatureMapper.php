<?php

namespace App\Contracts;

interface EmployeeFeatureMapper
{
    /**
     * Ubah satu record karyawan (Eloquent model atau array) menjadi payload
     * sesuai skema EmployeeInput di HR Intelligence API:
     * employee_id, usia, masa_kerja_bulan, jam_lembur_bulanan, gaji_bulanan,
     * tanggal_masuk (Y-m-d), tanggal_promosi (Y-m-d|null), departemen,
     * status_kerja, pendidikan.
     *
     * @param  mixed  $employee
     * @return array<string, mixed>
     */
    public function map(mixed $employee): array;

    /**
     * Versi jamak dari map(), untuk efisiensi kalau implementasi Anda perlu
     * melakukan sesuatu secara batch (mis. lookup tambahan ke tabel lain).
     * Implementasi default cukup memanggil map() per item.
     *
     * @param  iterable<mixed>  $employees
     * @return array<int, array<string, mixed>>
     */
    public function mapMany(iterable $employees): array;
}
