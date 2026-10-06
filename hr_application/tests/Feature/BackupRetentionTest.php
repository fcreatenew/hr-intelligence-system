<?php

namespace Tests\Feature;

use App\Models\HrIntelligencePrediction;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_writes_sqlite_file_to_disk(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hrdb').'.sqlite';
        touch($path);

        config(['database.connections.backup_test' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        DB::connection('backup_test')->statement('CREATE TABLE contoh (id integer primary key)');

        Storage::fake('local');

        $service = new DatabaseBackupService(disk: 'local', directory: 'backups', keep: 5);
        $hasil = $service->backup('backup_test');

        Storage::disk('local')->assertExists($hasil['path']);
        $this->assertSame('sqlite', $hasil['driver']);
        $this->assertGreaterThan(0, $hasil['bytes']);

        @unlink($path);
    }

    public function test_backup_rejects_in_memory_database(): void
    {
        $this->expectException(RuntimeException::class);

        (new DatabaseBackupService())->backup();
    }

    public function test_prune_keeps_only_latest_backups(): void
    {
        Storage::fake('local');

        foreach ([
            'backups/db-20260101-000000-sqlite.sqlite',
            'backups/db-20260102-000000-sqlite.sqlite',
            'backups/db-20260103-000000-sqlite.sqlite',
        ] as $file) {
            Storage::disk('local')->put($file, 'x');
        }

        $hasil = (new DatabaseBackupService(disk: 'local', directory: 'backups', keep: 2))->prune();

        $this->assertCount(1, $hasil['dihapus']);
        $this->assertSame(2, $hasil['disimpan']);
        Storage::disk('local')->assertMissing('backups/db-20260101-000000-sqlite.sqlite');
        Storage::disk('local')->assertExists('backups/db-20260103-000000-sqlite.sqlite');
    }

    public function test_prune_command_deletes_expired_predictions(): void
    {
        $lama = HrIntelligencePrediction::create([
            'use_case' => 'turnover',
            'employee_id' => 'EMP-1',
            'skor' => 0.4,
            'predicted_at' => now()->subDays(400),
        ]);

        $baru = HrIntelligencePrediction::create([
            'use_case' => 'turnover',
            'employee_id' => 'EMP-2',
            'skor' => 0.2,
            'predicted_at' => now()->subDays(10),
        ]);

        DB::table('audit_logs')->insert([
            'event' => 'created',
            'auditable_type' => 'App\\Models\\Employee',
            'auditable_id' => 1,
            'created_at' => now()->subDays(1000),
        ]);

        $this->artisan('hr:prune', ['--predictions-days' => 365, '--audit-days' => 730])
            ->assertSuccessful();

        $this->assertDatabaseMissing('hr_intelligence_predictions', ['id' => $lama->id]);
        $this->assertDatabaseHas('hr_intelligence_predictions', ['id' => $baru->id]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_prune_dry_run_keeps_rows(): void
    {
        HrIntelligencePrediction::create([
            'use_case' => 'turnover',
            'employee_id' => 'EMP-1',
            'skor' => 0.4,
            'predicted_at' => now()->subDays(400),
        ]);

        $this->artisan('hr:prune', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('hr_intelligence_predictions', 1);
    }
}
