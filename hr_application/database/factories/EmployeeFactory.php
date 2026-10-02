<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $joinDate = fake()->dateTimeBetween('-8 years', '-1 month');

        return [
            'nik' => fake()->unique()->numerify('EMP####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Operations', 'Marketing']),
            'position' => fake()->randomElement(['Staff', 'Senior Staff', 'Supervisor', 'Manager']),
            'join_date' => $joinDate,
            'status' => true,
            'tanggal_lahir' => fake()->dateTimeBetween('-55 years', '-22 years'),
            'tanggal_promosi' => fake()->dateTimeBetween($joinDate, 'now'),
            'status_kerja' => fake()->randomElement(['Tetap', 'Kontrak', 'Freelance']),
            'pendidikan' => fake()->randomElement(['SMA', 'D3', 'S1', 'S2']),
            'gaji_bulanan' => fake()->numberBetween(4_000_000, 25_000_000),
            'jam_lembur_bulanan' => fake()->numberBetween(0, 40),
            'jarak_km' => fake()->randomFloat(1, 1, 40),
            'beban_kerja' => fake()->randomFloat(1, 30, 100),
            'kepuasan_kerja' => fake()->randomFloat(2, 1, 5),
            'riwayat_absen_90hari' => fake()->numberBetween(0, 12),
            'jumlah_training_setahun' => fake()->numberBetween(0, 8),
            'proyek_selesai' => fake()->numberBetween(0, 20),
            'skor_umpan_balik_360' => fake()->randomFloat(2, 1, 5),
            'hari_absen_setahun' => fake()->numberBetween(0, 20),
            'bulan_sejak_promosi' => fake()->numberBetween(0, 60),
            'periode' => now()->format('Y-m'),
            'level' => fake()->randomElement(['Junior', 'Mid', 'Senior']),
            'gaji_pokok' => fake()->numberBetween(4_000_000, 20_000_000),
            'tunjangan' => fake()->numberBetween(0, 5_000_000),
            'lembur_jam' => fake()->numberBetween(0, 30),
            'tarif_lembur' => fake()->numberBetween(30_000, 120_000),
            'potongan' => fake()->numberBetween(0, 1_000_000),
            'total_gaji' => fake()->numberBetween(4_000_000, 25_000_000),
        ];
    }

    /** Karyawan nonaktif (sudah keluar). */
    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }
}
