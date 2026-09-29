<?php

namespace App\Exceptions;

/**
 * API tidak bisa dihubungi: timeout, koneksi putus, atau server error (5xx)
 * setelah semua percobaan retry habis (lihat HrIntelligenceService::sendWithRetry).
 *
 * Ini masalah SEMENTARA (harapannya) — sudah di-retry beberapa kali oleh
 * service sebelum exception ini dilempar. Penanganan yang wajar di
 * pemanggil: catat error, lewati baris ini (untuk batch), dan lanjut ke
 * data berikutnya — jangan sampai satu karyawan yang gagal menghentikan
 * seluruh proses batch bulanan.
 */
class HrIntelligenceUnavailableException extends HrIntelligenceException
{
}
