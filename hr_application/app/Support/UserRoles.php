<?php

namespace App\Support;

/**
 * Peran pengguna aplikasi HRIS.
 *
 * Satu-satunya sumber kebenaran untuk nilai `users.role` yang sah. Sebelumnya
 * daftar ini tersebar sebagai string literal di policy dan gate, sehingga
 * penambahan peran baru mudah terlewat di salah satu tempat.
 *
 * Nilai ini harus tetap sinkron dengan enum kolom `users.role` di migrasi.
 */
final class UserRoles
{
    public const ADMIN = 'admin';

    public const HR_MANAGER = 'hr-manager';

    /** @var list<string> */
    public const ALL = [self::ADMIN, self::HR_MANAGER];

    /** Label ringkas untuk dropdown/form. */
    public static function labels(): array
    {
        return [
            self::ADMIN => 'Admin',
            self::HR_MANAGER => 'HR Manager',
        ];
    }

    public static function label(string $role): string
    {
        return self::labels()[$role] ?? $role;
    }
}
