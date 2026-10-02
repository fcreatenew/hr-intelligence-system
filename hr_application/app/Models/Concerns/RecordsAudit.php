<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Mencatat jejak audit perubahan model secara otomatis.
 *
 * Dipakai oleh model HR yang datanya penting untuk ditelusuri (karyawan,
 * absensi, cuti, payroll). Hanya atribut yang berubah yang dicatat; nilai
 * sensitif yang tercantum di `$auditExclude` tidak pernah disimpan.
 */
trait RecordsAudit
{
    public static function bootRecordsAudit(): void
    {
        static::created(function (Model $model) {
            $model->recordAudit(AuditLog::EVENT_CREATED, [], $model->auditableValues());
        });

        static::updated(function (Model $model) {
            $changes = $model->auditableChanges();

            if ($changes === []) {
                return;
            }

            $model->recordAudit(
                AuditLog::EVENT_UPDATED,
                array_map(fn ($change) => $change[0], $changes),
                array_map(fn ($change) => $change[1], $changes),
            );
        });

        static::deleted(function (Model $model) {
            $model->recordAudit(AuditLog::EVENT_DELETED, $model->auditableValues(), []);
        });
    }

    /** Atribut yang dikecualikan dari jejak audit. */
    protected function auditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }

    /** @return array<string, mixed> */
    protected function auditableValues(): array
    {
        return collect($this->getAttributes())
            ->except($this->auditExclude())
            ->all();
    }

    /**
     * Perubahan yang dicatat: hanya atribut yang berubah dan tidak dikecualikan.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    protected function auditableChanges(): array
    {
        return collect($this->getChanges())
            ->except(array_merge($this->auditExclude(), ['updated_at']))
            ->map(fn ($new, $key) => [$this->getOriginal($key), $new])
            ->all();
    }

    protected function recordAudit(string $event, array $old, array $new): void
    {
        $request = request();

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255) ?: null,
        ]);
    }

    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
