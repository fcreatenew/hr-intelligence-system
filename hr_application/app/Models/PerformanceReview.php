<?php

namespace App\Models;

use App\Models\Concerns\RecordsAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Siklus review kinerja karyawan per periode (satu review per periode).
 *
 * `kategori` diturunkan dari `rating` (1–5) agar konsisten dan tidak diisi
 * manual: >=4.5 A, >=3.5 B, >=2.5 C, sisanya D.
 */
class PerformanceReview extends Model
{
    use HasFactory;
    use RecordsAudit;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'diajukan';

    public const STATUS_ACKNOWLEDGED = 'diakui';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_ACKNOWLEDGED,
    ];

    protected $fillable = [
        'employee_id',
        'period',
        'reviewer_id',
        'review_date',
        'rating',
        'kategori',
        'strengths',
        'improvements',
        'status',
    ];

    protected $casts = [
        'review_date' => 'date',
        'rating' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public static function kategoriFromRating(?int $rating): ?string
    {
        if ($rating === null) {
            return null;
        }

        return match (true) {
            $rating >= 5 => 'A',
            $rating === 4 => 'B',
            $rating === 3 => 'C',
            default => 'D',
        };
    }
}
