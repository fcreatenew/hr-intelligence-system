<?php

namespace App\Support;

/**
 * Nilai kategorikal yang diterima pipeline model HR Intelligence.
 *
 * Ini satu-satunya sumber kebenaran untuk nilai yang sah. Sebelumnya daftar
 * yang sama ditulis ulang di mapper dan di form, sehingga keduanya bisa
 * berbeda diam-diam — nilai yang tidak dikenal akan diubah mapper menjadi
 * nilai lain tanpa peringatan (mis. "D3" menjadi "S1"), yang berarti HR
 * mengisi satu hal tetapi model membaca hal lain.
 *
 * Sumber: hr_intelligence_api/app/schemas/employee.py (Literal types).
 */
final class HrCategories
{
    /** @var list<string> */
    public const DEPARTEMEN = [
        'Sales', 'Marketing', 'HR', 'Operations', 'IT', 'Finance', 'Legal',
    ];

    /** @var list<string> */
    public const STATUS_KERJA = ['Tetap', 'Kontrak', 'Freelance'];

    /** @var list<string> */
    public const PENDIDIKAN = ['SMA', 'Diploma', 'S1', 'S2'];

    public static function departemenFallback(): string
    {
        return 'Operations';
    }

    public static function statusKerjaFallback(): string
    {
        return 'Tetap';
    }

    public static function pendidikanFallback(): string
    {
        return 'S1';
    }
}
