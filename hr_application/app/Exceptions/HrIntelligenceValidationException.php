<?php

namespace App\Exceptions;

/**
 * Data yang dikirim ditolak API (HTTP 422) — mis. usia negatif, departemen
 * tidak dikenal, tanggal_promosi sebelum tanggal_masuk.
 *
 * Ini kesalahan DATA, bukan kesalahan jaringan — jangan retry, karena data
 * yang sama akan ditolak lagi. Errornya sendiri (lihat getApiErrors())
 * berguna untuk ditampilkan ke user yang menginput data, atau dicatat
 * untuk diperbaiki di sumber data.
 */
class HrIntelligenceValidationException extends HrIntelligenceException
{
    /** @param array<int, array<string, mixed>> $apiErrors detail error per field dari body respons API */
    public function __construct(string $message, private readonly array $apiErrors = [])
    {
        parent::__construct($message);
    }

    public function getApiErrors(): array
    {
        return $this->apiErrors;
    }
}
