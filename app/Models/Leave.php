<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'evidence_path'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return BelongsTo<User, covariant Model>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        $typeAttr = $this->getAttribute('type');
        $type = is_string($typeAttr) ? $typeAttr : '';

        return match ($type) {
            'izin' => 'Izin',
            'cuti' => 'Cuti',
            'sakit' => 'Sakit',
            default => $type,
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        $typeAttr = $this->getAttribute('type');
        $type = is_string($typeAttr) ? $typeAttr : '';

        return match ($type) {
            'izin' => 'warning',
            'cuti' => 'info',
            'sakit' => 'danger',
            default => 'secondary'
        };
    }

    public function getDurationAttribute(): int
    {
        return (int) ($this->start_date->diffInDays($this->end_date) + 1);
    }
}
