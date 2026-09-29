<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception dasar untuk semua kegagalan memanggil HR Intelligence API.
 * Tangkap ini kalau Anda tidak peduli jenis error spesifiknya, atau
 * tangkap subclass-nya (lihat file lain di folder ini) untuk penanganan
 * yang lebih spesifik per jenis kegagalan.
 */
class HrIntelligenceException extends Exception
{
}
