<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien Payroll Intelligence (FastAPI di port 8300).
 *
 * Dipakai untuk menilai anomali baris payroll hasil perhitungan HRIS. Layanan
 * ini bersifat opsional: bila tidak dikonfigurasi (`PAYROLL_INTELLIGENCE_BASE_URL`
 * kosong) atau tidak dapat dihubungi, payroll tetap dapat dihitung tanpa skor.
 */
class PayrollIntelligenceService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
        private readonly bool $enabled,
    ) {
    }

    public function enabled(): bool
    {
        return $this->enabled && $this->baseUrl !== '';
    }

    /**
     * Skor anomali untuk sekumpulan baris payroll.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>> hasil per baris; [] bila layanan mati
     */
    public function score(array $rows): array
    {
        if (! $this->enabled() || $rows === []) {
            return [];
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->post('/anomalies/score', ['employees' => array_values($rows)]);

            if (! $response->successful()) {
                Log::warning('Payroll Intelligence menolak permintaan skor anomali.', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json('hasil', []) ?? [];
        } catch (ConnectionException $e) {
            Log::warning('Payroll Intelligence tidak dapat dihubungi; skor anomali dilewati.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /** @return array<string, mixed> */
    public function health(): array
    {
        if (! $this->enabled()) {
            return ['status' => 'disabled'];
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->get('/health');

            return $response->successful()
                ? ($response->json() ?? [])
                : ['status' => 'error', 'http_status' => $response->status()];
        } catch (ConnectionException $e) {
            return ['status' => 'unreachable', 'error' => $e->getMessage()];
        }
    }
}
