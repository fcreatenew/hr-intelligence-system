<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            Attendance::STATUS_HADIR,
            Attendance::STATUS_HADIR,
            Attendance::STATUS_HADIR,
            Attendance::STATUS_TERLAMBAT,
            Attendance::STATUS_IZIN,
            Attendance::STATUS_SAKIT,
            Attendance::STATUS_ALPHA,
        ]);

        $present = in_array($status, [Attendance::STATUS_HADIR, Attendance::STATUS_TERLAMBAT], true);

        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'status' => $status,
            'check_in' => $present ? '08:'.fake()->numberBetween(0, 59) : null,
            'check_out' => $present ? '17:'.fake()->numberBetween(0, 59) : null,
            'late_minutes' => $status === Attendance::STATUS_TERLAMBAT ? fake()->numberBetween(5, 60) : 0,
            'overtime_minutes' => $present ? fake()->numberBetween(0, 120) : 0,
            'note' => null,
        ];
    }

    public function absence(): static
    {
        return $this->state(fn () => [
            'status' => Attendance::STATUS_ALPHA,
            'check_in' => null,
            'check_out' => null,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn () => ['date' => $date]);
    }
}
