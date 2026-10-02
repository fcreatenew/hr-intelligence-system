<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Hari libur nasional Indonesia 2026 (tanggal pasti). Cuti bersama yang
 * tanggalnya belum ditetapkan sengaja tidak diisi agar tidak menebak.
 */
class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            '2026-01-01' => 'Tahun Baru 2026',
            '2026-02-17' => 'Tahun Baru Imlek 2577',
            '2026-03-19' => 'Hari Suci Nyepi',
            '2026-03-20' => 'Idul Fitri 1447 H',
            '2026-03-21' => 'Idul Fitri 1447 H (hari kedua)',
            '2026-04-03' => 'Wafat Isa Al Masih',
            '2026-05-01' => 'Hari Buruh Internasional',
            '2026-05-14' => 'Kenaikan Isa Al Masih',
            '2026-05-27' => 'Idul Adha 1447 H',
            '2026-06-01' => 'Hari Lahir Pancasila',
            '2026-08-17' => 'Hari Kemerdekaan RI',
            '2026-08-25' => 'Maulid Nabi Muhammad SAW',
            '2026-12-25' => 'Hari Raya Natal',
        ];

        foreach ($holidays as $date => $name) {
            Holiday::updateOrCreate(
                ['date' => $date],
                ['name' => $name, 'is_national' => true],
            );
        }

        $this->command?->info(sprintf(
            'Hari libur %d di-seed untuk %d.',
            count($holidays),
            CarbonImmutable::now()->year,
        ));
    }
}
