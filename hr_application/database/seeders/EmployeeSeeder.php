<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Data contoh agar daftar karyawan tidak kosong saat pertama kali dijalankan.
 * Aman diulang: memakai updateOrCreate berdasarkan NIK.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            ['nik' => 'EMP0001', 'name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'department' => 'IT', 'position' => 'Backend Engineer', 'status_kerja' => 'Tetap', 'pendidikan' => 'S1', 'gaji_bulanan' => 9_500_000, 'jam_lembur_bulanan' => 22, 'kepuasan_kerja' => 3.2],
            ['nik' => 'EMP0002', 'name' => 'Siti Aminah', 'email' => 'siti.aminah@example.com', 'department' => 'HR', 'position' => 'HR Generalist', 'status_kerja' => 'Tetap', 'pendidikan' => 'S1', 'gaji_bulanan' => 7_800_000, 'jam_lembur_bulanan' => 6, 'kepuasan_kerja' => 4.1],
            ['nik' => 'EMP0003', 'name' => 'Andi Wijaya', 'email' => 'andi.wijaya@example.com', 'department' => 'Finance', 'position' => 'Staff Akuntansi', 'status_kerja' => 'Kontrak', 'pendidikan' => 'Diploma', 'gaji_bulanan' => 6_200_000, 'jam_lembur_bulanan' => 18, 'kepuasan_kerja' => 3.6],
        ];

        foreach ($samples as $sample) {
            Employee::updateOrCreate(
                ['nik' => $sample['nik']],
                $sample + [
                    'join_date' => now()->subMonths(random_int(12, 72))->startOfMonth(),
                    'status' => true,
                    'tanggal_lahir' => now()->subYears(random_int(24, 50))->startOfMonth(),
                    'jarak_km' => random_int(2, 25),
                    'beban_kerja' => random_int(40, 90),
                ],
            );
        }
    }
}
