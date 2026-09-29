<?php

namespace App\Exceptions;

/**
 * API key salah atau tidak dikirim (HTTP 401).
 *
 * Ini HAMPIR SELALU masalah konfigurasi (HR_INTELLIGENCE_API_KEY di .env
 * tidak cocok dengan HR_API_KEY di sisi API), bukan masalah sementara.
 * Jangan retry otomatis — retry tidak akan memperbaiki API key yang salah,
 * dan bisa memicu rate limit tanpa guna. Sebaiknya dicatat sebagai error
 * kritis dan diberi tahu ke tim (mis. lewat notifikasi/monitoring).
 */
class HrIntelligenceAuthException extends HrIntelligenceException
{
}
