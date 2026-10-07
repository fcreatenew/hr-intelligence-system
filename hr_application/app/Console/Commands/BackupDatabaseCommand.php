<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup
                            {--connection= : Koneksi database yang dicadangkan (default: koneksi aktif)}
                            {--keep= : Jumlah berkas cadangan yang dipertahankan}';

    protected $description = 'Cadangkan database ke disk backup lalu terapkan retensi berkas.';

    public function handle(DatabaseBackupService $service): int
    {
        try {
            $hasil = $service->backup($this->option('connection'));
        } catch (\Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Backup tersimpan: %s (%s, %d byte)',
            $hasil['path'],
            $hasil['driver'],
            $hasil['bytes'],
        ));

        $keep = $this->option('keep') !== null ? (int) $this->option('keep') : null;
        $prune = $service->prune($keep);

        $this->info(sprintf(
            'Retensi: %d berkas disimpan, %d dihapus.',
            $prune['disimpan'],
            count($prune['dihapus']),
        ));

        return self::SUCCESS;
    }
}
