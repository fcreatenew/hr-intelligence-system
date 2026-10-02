<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Impor absensi dari berkas CSV hasil ekspor mesin absensi/fingerprint.
 *
 * Format kolom: `nik,date,in,out,status` (status opsional; bila kosong
 * diturunkan dari jam masuk). Baris yang tidak cocok dilewati dan dilaporkan,
 * bukan menggagalkan seluruh impor — satu NIK salah tidak boleh membatalkan
 * ribuan baris lain.
 */
class AttendanceImportService
{
    public function __construct(private AttendanceImportParser $parser) {}

    /**
     * @return array{imported: int, updated: int, skipped: list<string>}
     */
    public function import(UploadedFile $file): array
    {
        $rows = $this->readCsv($file);

        $employeesByNik = Employee::query()
            ->whereNotNull('nik')
            ->get()
            ->keyBy('nik');

        $parsed = $this->parser->parse($rows, $employeesByNik);

        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($parsed, &$imported, &$updated) {
            foreach ($parsed['records'] as $record) {
                $attendance = Attendance::firstOrNew([
                    'employee_id' => $record['employee_id'],
                    'date' => $record['date'],
                ]);

                $exists = $attendance->exists;

                $attendance->fill($record)->save();

                $exists ? $updated++ : $imported++;
            }
        });

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $parsed['errors'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        $header = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($column) => strtolower(trim((string) $column)), $line);

                continue;
            }

            if (count($line) === 1 && trim((string) $line[0]) === '') {
                continue;
            }

            $rows[] = array_combine($header, array_pad($line, count($header), null)) ?: [];
        }

        fclose($handle);

        return $rows;
    }
}
