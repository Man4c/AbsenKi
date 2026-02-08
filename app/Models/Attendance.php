<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'lat',
        'lng',
        'geo_ok',
        'face_score',
        'status',
        'status_flag',
        'device_info',
        'quality_blur_var',
        'quality_brightness',
        // Offsite fields
        'is_offsite',
        'offsite_location_text',
        'offsite_coords',
        'offsite_reason',
        'evidence_path',
        'evidence_mime',
        'evidence_size',
        'evidence_note',
        'evidence_uploaded_at',
        'created_by_admin_id',
    ];

    protected $casts = [
        'geo_ok' => 'boolean',
        'face_score' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'quality_blur_var' => 'decimal:2',
        'quality_brightness' => 'decimal:2',
        'is_offsite' => 'boolean',
        'evidence_uploaded_at' => 'datetime',
        // Don't cast to array - handle in accessor for backward compatibility
    ];

    /**
     * Relationship to User (staff who did the attendance)
     * @return BelongsTo<User, covariant Attendance>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to Admin who created this offsite entry
     * @return BelongsTo<User, covariant Attendance>
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Accessor: Get public URL for evidence file
     * @return array<int, string>
     */
    public function getEvidenceUrlsAttribute(): array
    {
        if (!$this->evidence_path) {
            return [];
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return [$disk->url($this->evidence_path)];
    }

    /**
     * Accessor: Get evidence URL
     */
    public function getEvidenceUrlAttribute(): ?string
    {
        if (!$this->evidence_path) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        return $disk->url($this->evidence_path);
    }

    /**
     * Accessor: Check if attendance has evidence
     */
    public function getHasEvidenceAttribute(): bool
    {
        return !empty($this->evidence_path);
    }

    /**
     * Accessor: Get evidence file name
     * @return array<int, string>
     */
    public function getEvidenceFileNamesAttribute(): array
    {
        if (!$this->evidence_path) {
            return [];
        }

        return [basename($this->evidence_path)];
    }

    /**
     * Accessor: Get human-readable file size
     */
    public function getEvidenceSizeFormattedAttribute(): ?string
    {
        if (!$this->evidence_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->evidence_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Accessor: Get status label (Indonesian)
     */
    public function getStatusLabelAttribute(): string
    {
        // Offsite entries don't have status
        if ($this->is_offsite) {
            return '—';
        }

        if (!$this->status_flag) {
            return '—';
        }

        $labels = [
            'on_time' => 'Tepat Waktu',
            'late' => 'Terlambat',
            'normal_leave' => 'Pulang Normal',
            'early_leave' => 'Pulang Cepat',
        ];

        return $labels[$this->status_flag] ?? '—';
    }

    /**
     * Accessor: Get status label with duration (Indonesian)
     */
    public function getStatusLabelWithDurationAttribute(): string
    {
        $baseLabel = $this->status_label;

        if ($baseLabel === '—') {
            return $baseLabel;
        }

        // Get duration for late or early_leave
        if ($this->status_flag === 'late' || $this->status_flag === 'early_leave') {
            $duration = $this->getStatusDuration();
            if ($duration) {
                return $baseLabel . ' ' . $duration;
            }
        }

        return $baseLabel;
    }

    /**
     * Get duration for late check-in or early check-out
     */
    private function getStatusDuration(): ?string
    {
        if (!$this->created_at) {
            return null;
        }

        $schedule = WorkSchedule::where('day_of_week', $this->created_at->dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return null;
        }

        $actualTime = $this->created_at;
        $actualTimeString = $actualTime->format('H:i:s');

        if ($this->status_flag === 'late') {
            // Calculate how late (compared to in_time + grace_late_minutes)
            $expectedTime = \Carbon\Carbon::parse($this->created_at->format('Y-m-d') . ' ' . $schedule->in_time)
                ->addMinutes($schedule->grace_late_minutes);

            // User came after expected time, so actualTime > expectedTime
            if ($actualTime->greaterThan($expectedTime)) {
                $diffInMinutes = abs((int) $actualTime->diffInMinutes($expectedTime));

                $hours = intdiv($diffInMinutes, 60);
                $minutes = $diffInMinutes % 60;

                if ($hours > 0 && $minutes > 0) {
                    return "({$hours} jam {$minutes} menit)";
                } elseif ($hours > 0) {
                    return "({$hours} jam)";
                } else {
                    return "({$minutes} menit)";
                }
            }
        } elseif ($this->status_flag === 'early_leave') {
            // Calculate how early (compared to out_time - grace_early_minutes)
            $expectedTime = \Carbon\Carbon::parse($this->created_at->format('Y-m-d') . ' ' . $schedule->out_time)
                ->subMinutes($schedule->grace_early_minutes);

            // User left before expected time, so actualTime < expectedTime
            if ($actualTime->lessThan($expectedTime)) {
                $diffInMinutes = abs((int) $expectedTime->diffInMinutes($actualTime));

                $hours = intdiv($diffInMinutes, 60);
                $minutes = $diffInMinutes % 60;

                if ($hours > 0 && $minutes > 0) {
                    return "({$hours} jam {$minutes} menit)";
                } elseif ($hours > 0) {
                    return "({$hours} jam)";
                } else {
                    return "({$minutes} menit)";
                }
            }
        }

        return null;
    }

    /**
     * Accessor: Get status badge color class
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_offsite || !$this->status_flag) {
            return 'gray';
        }

        $colors = [
            'on_time' => 'green',
            'late' => 'red',
            'normal_leave' => 'green',
            'early_leave' => 'yellow',
        ];

        return $colors[$this->status_flag] ?? 'gray';
    }

    /**
     * Boot method - setup observers
     */
    protected static function booted(): void
    {
        // Auto-delete evidence file when attendance is deleted
        static::deleting(function (Attendance $attendance) {
            if ($attendance->evidence_path && Storage::disk('public')->exists($attendance->evidence_path)) {
                Storage::disk('public')->delete($attendance->evidence_path);
            }
        });
    }
}
