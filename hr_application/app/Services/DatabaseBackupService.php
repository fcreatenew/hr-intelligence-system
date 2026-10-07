<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Backup database ke disk aplikasi dan retensi berkas cadangan.
 *
 * Mendukung driver yang dipakai sistem ini: SQLite (dev/test), MySQL/MariaDB,
 * dan PostgreSQL (produksi). Untuk MySQL/PostgreSQL, dump dijalankan lewat
 * `mysqldump`/`pg_dump`; kredensial diteruskan lewat environment proses, bukan
 * argumen, agar tidak terlihat di daftar proses.
 */
class DatabaseBackupService
{
    public function __construct(
        private readonly string $disk = 'local',
        private readonly string $directory = 'backups',
        private readonly int $keep = 14,
        private readonly int $timeout = 300,
    ) {
    }

    /**
     * Tulis satu cadangan database. Kembalikan metadata berkas hasil.
     */
    public function backup(?string $connection = null): array
    {
        $connection ??= config('database.default');

        $driver = DB::connection($connection)->getDriverName();
        $stamp = now()->format('Ymd-His');
        $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $target = sprintf('%s/db-%s-%s.%s', trim($this->directory, '/'), $stamp, $driver, $extension);

        $driver === 'sqlite'
            ? $this->backupSqlite($connection, $target)
            : $this->backupDump($connection, $driver, $target);

        return [
            'path' => $target,
            'driver' => $driver,
            'bytes' => Storage::disk($this->disk)->size($target),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Pertahankan hanya `$keep` berkas cadangan terbaru.
     */
    public function prune(?int $keep = null): array
    {
        $keep ??= $this->keep;

        $berkas = collect(Storage::disk($this->disk)->files($this->directory))
            ->sortDesc()
            ->values();

        $dihapus = $berkas->slice($keep)->all();

        foreach ($dihapus as $path) {
            Storage::disk($this->disk)->delete($path);
        }

        return [
            'dihapus' => $dihapus,
            'disimpan' => min($keep, $berkas->count()),
        ];
    }

    private function backupSqlite(string $connection, string $target): void
    {
        $source = DB::connection($connection)->getDatabaseName();

        if ($source === ':memory:' || ! is_file($source)) {
            throw new RuntimeException(
                "Basis data SQLite tidak berupa berkas sehingga tidak bisa dicadangkan: {$source}"
            );
        }

        $stream = fopen($source, 'rb');

        if ($stream === false) {
            throw new RuntimeException("Tidak dapat membaca berkas database: {$source}");
        }

        try {
            Storage::disk($this->disk)->writeStream($target, $stream);
        } finally {
            fclose($stream);
        }
    }

    private function backupDump(string $connection, string $driver, string $target): void
    {
        $config = DB::connection($connection)->getConfig();

        [$command, $environment] = match ($driver) {
            'mysql', 'mariadb' => [
                [
                    'mysqldump',
                    '--single-transaction',
                    '--quick',
                    '--host='.($config['host'] ?? '127.0.0.1'),
                    '--port='.($config['port'] ?? 3306),
                    '--user='.($config['username'] ?? 'root'),
                    $config['database'] ?? '',
                ],
                ['MYSQL_PWD' => (string) ($config['password'] ?? '')],
            ],
            'pgsql' => [
                [
                    'pg_dump',
                    '--host='.($config['host'] ?? '127.0.0.1'),
                    '--port='.($config['port'] ?? 5432),
                    '--username='.($config['username'] ?? 'postgres'),
                    '--no-owner',
                    '--dbname='.($config['database'] ?? ''),
                ],
                ['PGPASSWORD' => (string) ($config['password'] ?? '')],
            ],
            default => throw new RuntimeException("Driver database tidak didukung untuk backup: {$driver}"),
        };

        $hasil = Process::env($environment)->timeout($this->timeout)->run($command);

        if (! $hasil->successful()) {
            throw new RuntimeException(
                "Perintah backup gagal ({$driver}): ".trim($hasil->errorOutput())
            );
        }

        Storage::disk($this->disk)->put($target, $hasil->output());
    }
}
