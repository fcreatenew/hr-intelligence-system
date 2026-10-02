<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Support\HrCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:50', 'unique:employees,nik'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['required', 'string', Rule::in(HrCategories::DEPARTEMEN)],
            'position' => ['required', 'string', 'max:100'],
            'join_date' => ['required', 'date'],
            'status' => ['boolean'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'tanggal_promosi' => ['nullable', 'date'],
            'status_kerja' => ['nullable', Rule::in(HrCategories::STATUS_KERJA)],
            'pendidikan' => ['nullable', Rule::in(HrCategories::PENDIDIKAN)],
            'gaji_bulanan' => ['nullable', 'integer', 'min:0'],
            'jam_lembur_bulanan' => ['nullable', 'integer', 'min:0', 'max:744'],
            'jarak_km' => ['nullable', 'numeric', 'min:0', 'max:999.9'],
            'beban_kerja' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'kepuasan_kerja' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'jumlah_training_setahun' => ['nullable', 'integer', 'min:0', 'max:365'],
            'proyek_selesai' => ['nullable', 'integer', 'min:0'],
            'skor_umpan_balik_360' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'bulan_sejak_promosi' => ['nullable', 'numeric', 'min:0'],
            'periode' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'level' => ['nullable', 'string', 'max:20'],
            'gaji_pokok' => ['nullable', 'integer', 'min:0'],
            'tunjangan' => ['nullable', 'numeric', 'min:0'],
            'lembur_jam' => ['nullable', 'integer', 'min:0'],
            'tarif_lembur' => ['nullable', 'numeric', 'min:0'],
            'potongan' => ['nullable', 'numeric', 'min:0'],
            'total_gaji' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.unique' => 'NIK sudah dipakai karyawan lain.',
            'email.unique' => 'Email sudah dipakai karyawan lain.',
            'department.in' => 'Departemen harus salah satu dari: '.implode(', ', HrCategories::DEPARTEMEN).'.',
            'status_kerja.in' => 'Status kerja harus salah satu dari: '.implode(', ', HrCategories::STATUS_KERJA).'.',
            'pendidikan.in' => 'Pendidikan harus salah satu dari: '.implode(', ', HrCategories::PENDIDIKAN).'.',
            'kepuasan_kerja.between' => 'Kepuasan kerja harus antara 1 dan 5.',
            'skor_umpan_balik_360.between' => 'Skor umpan balik 360 harus antara 1 dan 5.',
            'periode.regex' => 'Periode harus berformat YYYY-MM, mis. 2026-01.',
        ];
    }
}
