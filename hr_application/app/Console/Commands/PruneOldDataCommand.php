<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\HrIntelligencePrediction;
use App\Models\TurnoverPrediction;
use Illuminate\Console\Command;

class PruneOldDataCommand extends Command
{
    protected $signature = 'hr:prune
                            {--dry-run : Hitung saja, jangan hapus}
                            {--predictions-days= : Umur maksimum prediksi ML (default dari config)}
                            {--audit-days= : Umur maksimum audit log (default dari config)}';

    protected $description = 'Hapus data operasional yang melewati masa retensi (prediksi ML & audit log).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $predictionDays = (int) ($this->option('predictions-days')
            ?: config('hr_intelligence.prediction_retention_days', 365));
        $auditDays = (int) ($this->option('audit-days')
            ?: config('backup.audit_log_retention_days', 730));

        $targets = [
            'hr_intelligence_predictions' => [
                HrIntelligencePrediction::query()
                    ->where('predicted_at', '<', now()->subDays($predictionDays)),
                $predictionDays,
            ],
            'turnover_predictions' => [
                TurnoverPrediction::query()
                    ->where('predicted_at', '<', now()->subDays($predictionDays)),
                $predictionDays,
            ],
            'audit_logs' => [
                AuditLog::query()
                    ->where('created_at', '<', now()->subDays($auditDays)),
                $auditDays,
            ],
        ];

        foreach ($targets as $nama => [$query, $hari]) {
            $jumlah = (clone $query)->count();

            if (! $dryRun) {
                $query->delete();
            }

            $this->info(sprintf(
                '%s %s: %d baris lebih tua dari %d hari.',
                $dryRun ? '[dry-run]' : '[hapus]',
                $nama,
                $jumlah,
                $hari,
            ));
        }

        return self::SUCCESS;
    }
}
