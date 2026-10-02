<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Mengubah baris mentah hasil ekspor mesin absensi/fingerprint menjadi catatan
 * absensi. Pencocokan karyawan memakai NIK; waktu `in`/`out` diturunkan menjadi
 * menit terlambat dan menit lembur memakai jam kerja di `config/attendance.php`.
 *
 * Kelas ini murni (tanpa efek samping) agar mudah diuji: importer yang menyimpan
 * hasilnya, kelas ini hanya menormalkan bentuk data.
 */
class AttendanceImportParser
{
    /** Status kehadiran yang butuh jam masuk/keluar untuk dihitung. */
    private const PRESENT_STATUSES = [Attendance::STATUS_HADIR, Attendance::STATUS_TERLAMBAT];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{records: list<array<string, mixed>>, errors: list<string>}
     */
    public function parse(array $rows, Collection $employeesByNik): array
    {
        $records = [];
        $errors = [];
        $seen = [];
        $lateThreshold = $this->minutesFromStart(config('attendance.work_start', '09:00'));
        $overtimeThreshold = $this->minutesFromStart(config('attendance.work_end', '17:00'));

        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $nik = trim((string) ($row['nik'] ?? ''));
            $rawDate = trim((string) ($row['date'] ?? ''));

            if ($nik === '' || $rawDate === '') {
                $errors[] = "Baris {$line}: kolom nik dan date wajib diisi.";

                continue;
            }

            /** @var Employee|null $employee */
            $employee = $employeesByNik->get($nik);

            if (! $employee) {
                $errors[] = "Baris {$line}: NIK {$nik} tidak dikenal.";

                continue;
            }

            try {
                $date = CarbonImmutable::parse($rawDate)->toDateString();
            } catch (\Throwable) {
                $errors[] = "Baris {$line}: tanggal \"{$rawDate}\" tidak valid.";

                continue;
            }

            $key = $employee->id.'|'.$date;

            if (isset($seen[$key])) {
                $errors[] = "Baris {$line}: NIK {$nik} punya lebih dari satu baris pada {$date}.";

                continue;
            }

            $checkIn = $this->normalizeTime($row['in'] ?? null);
            $checkOut = $this->normalizeTime($row['out'] ?? null);

            if ($checkOut !== null && $checkIn !== null && $checkOut <= $checkIn) {
                $errors[] = "Baris {$line}: jam keluar harus setelah jam masuk.";

                continue;
            }

            $status = $this->resolveStatus($row['status'] ?? null, $checkIn);

            $lateMinutes = 0;
            $overtimeMinutes = 0;

            if (in_array($status, self::PRESENT_STATUSES, true)) {
                if ($checkIn !== null) {
                    $lateMinutes = max(0, $this->minutesOfDay($checkIn) - $lateThreshold);
                }

                if ($checkOut !== null) {
                    $overtimeMinutes = max(0, $this->minutesOfDay($checkOut) - $overtimeThreshold);
                }
            }

            $seen[$key] = true;

            $records[] = [
                'employee_id' => $employee->id,
                'date' => $date,
                'status' => $status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'late_minutes' => $lateMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'note' => 'Impor mesin absensi',
            ];
        }

        return ['records' => $records, 'errors' => $errors];
    }

    /** Tentukan status dari kolom eksplisit; jika kosong, dari jam masuk. */
    private function resolveStatus(?string $raw, ?string $checkIn): string
    {
        $raw = strtolower(trim((string) $raw));

        if (in_array($raw, Attendance::statuses(), true)) {
            return $raw;
        }

        if ($raw === 'terlambat') {
            return Attendance::STATUS_TERLAMBAT;
        }

        if ($checkIn === null) {
            return Attendance::STATUS_ALPHA;
        }

        return Attendance::STATUS_HADIR;
    }

    private function normalizeTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $value)->format('H:i');
            } catch (\Throwable) {
                // Coba format berikutnya.
            }
        }

        return null;
    }

    private function minutesOfDay(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function minutesFromStart(string $time): int
    {
        return $this->minutesOfDay($this->normalizeTime($time) ?? '00:00');
    }
}
