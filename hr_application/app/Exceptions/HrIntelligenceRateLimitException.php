<?php

namespace App\Exceptions;

/**
 * Rate limit API terlampaui (HTTP 429).
 *
 * Beda dari HrIntelligenceUnavailableException: ini bukan API-nya rusak,
 * tapi kita yang mengirim request terlalu sering. Kalau terjadi di job
 * batch, biasanya berarti batch_chunk_size terlalu kecil (terlalu banyak
 * request) atau job lain berjalan bersamaan. Kalau terjadi di request
 * dari user langsung (mis. Fase 11 dashboard), tampilkan pesan "coba lagi
 * sebentar" ke user, bukan error teknis.
 */
class HrIntelligenceRateLimitException extends HrIntelligenceException
{
    public function __construct(string $message, private readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct($message);
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
