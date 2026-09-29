<?php

namespace App\Services;

use App\Exceptions\HrIntelligenceAuthException;
use App\Exceptions\HrIntelligenceRateLimitException;
use App\Exceptions\HrIntelligenceUnavailableException;
use App\Exceptions\HrIntelligenceValidationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrIntelligenceService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds,
        private readonly int $retryTimes,
        private readonly int $retryDelayMs,
        private readonly int $batchChunkSize,
    ) {
    }

    public function maxBatchSize(): int
    {
        return $this->batchChunkSize;
    }

    public function health(): array
    {
        return $this->request('get', '/health', requiresAuth: false);
    }

    /** @return array<string, mixed> metadata & metrik model yang sedang aktif */
    public function modelInfo(): array
    {
        return $this->request('get', '/model/info');
    }

    public function predict(array $employee): array
    {
        return $this->request('post', '/predict', $employee);
    }

    public function predictBatch(array $employees): array
    {
        if (count($employees) > $this->batchChunkSize) {
            throw new \InvalidArgumentException(
                sprintf(
                    'predictBatch() menerima %d karyawan, melebihi batas %d. '
                    .'Pecah dulu jadi beberapa chunk (lihat maxBatchSize()).',
                    count($employees),
                    $this->batchChunkSize,
                )
            );
        }

        return $this->request('post', '/predict/batch', ['employees' => $employees]);
    }

    private function request(string $method, string $path, array $payload = [], bool $requiresAuth = true): array
    {
        $client = Http::baseUrl($this->baseUrl)->timeout($this->timeoutSeconds)->acceptJson();

        if ($requiresAuth) {
            $client = $client->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        $response = $this->sendWithRetry($client, $method, $path, $payload);

        return $this->handleResponse($response, $path);
    }

    private function sendWithRetry(PendingRequest $client, string $method, string $path, array $payload): Response
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryTimes) {
            $attempt++;

            try {
                $response = $client->{$method}($path, $payload);

                if ($response->serverError()) {
                    Log::warning('HR Intelligence API mengembalikan 5xx, akan dicoba lagi.', [
                        'path' => $path,
                        'attempt' => $attempt,
                        'status' => $response->status(),
                    ]);
                    usleep($this->retryDelayMs * 1000);

                    continue;
                }

                return $response;
            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::warning('Koneksi ke HR Intelligence API gagal, akan dicoba lagi.', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                usleep($this->retryDelayMs * 1000);
            }
        }

        throw new HrIntelligenceUnavailableException(
            "HR Intelligence API tidak bisa dihubungi setelah {$this->retryTimes}x percobaan ({$path})."
        );
    }

    /** @return array<string, mixed> */
    private function handleResponse(Response $response, string $path): array
    {
        if ($response->successful()) {
            return $response->json();
        }

        $body = $response->json() ?? [];
        $message = $body['detail'] ?? "HR Intelligence API mengembalikan status {$response->status()} untuk {$path}.";
        $message = is_string($message) ? $message : "HR Intelligence API mengembalikan status {$response->status()} untuk {$path}.";

        return match ($response->status()) {
            401 => throw new HrIntelligenceAuthException($message),
            422 => throw new HrIntelligenceValidationException($message, $body['errors'] ?? []),
            429 => throw new HrIntelligenceRateLimitException($message, $this->parseRetryAfter($response)),
            503 => throw new HrIntelligenceUnavailableException($message),
            default => throw new HrIntelligenceUnavailableException(
                "HR Intelligence API mengembalikan status tak terduga {$response->status()} untuk {$path}."
            ),
        };
    }

    private function parseRetryAfter(Response $response): ?int
    {
        $value = $response->header('Retry-After');

        return $value !== '' && $value !== null ? (int) $value : null;
    }
}
