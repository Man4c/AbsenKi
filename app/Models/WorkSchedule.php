<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = [
        'day_of_week',
        'in_time',
        'out_time',
        'grace_late_minutes',
        'grace_early_minutes',
        'lock_in_start',
        'lock_in_end',
        'lock_out_start',
        'lock_out_end',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'grace_late_minutes' => 'integer',
        'grace_early_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get day name in Indonesian
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $days[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get schedule for specific day
     */
    public static function getScheduleForDay(int $dayOfWeek): ?self
    {
        return self::where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get schedule for today
     */
    public static function getTodaySchedule(): ?self
    {
        return self::getScheduleForDay(now()->dayOfWeek);
    }

    /**
     * Check if check-in is allowed now
     */
    public function isCheckInAllowed(?\DateTimeInterface $time = null): bool
    {
        $time = $time ?? now();
        $timeString = $time->format('H:i:s');

        if (!$this->lock_in_start || !$this->lock_in_end) {
            return true; // No lock, always allowed
        }

        return $timeString >= $this->lock_in_start && $timeString <= $this->lock_in_end;
    }

    /**
     * Check if check-out is allowed now
     */
    public function isCheckOutAllowed(?\DateTimeInterface $time = null): bool
    {
        $time = $time ?? now();
        $timeString = $time->format('H:i:s');

        if (!$this->lock_out_start || !$this->lock_out_end) {
            return true; // No lock, always allowed
        }

        return $timeString >= $this->lock_out_start && $timeString <= $this->lock_out_end;
    }

    /**
     * Calculate attendance status for check-in
     */
    public function calculateCheckInStatus(\DateTimeInterface $actualTime): string
    {
        $actualTimeString = $actualTime->format('H:i:s');
        $expectedTime = $this->in_time;

        // Add grace period
        $graceTime = \Carbon\Carbon::parse($expectedTime)
            ->addMinutes($this->grace_late_minutes)
            ->format('H:i:s');

        return $actualTimeString <= $graceTime ? 'on_time' : 'late';
    }

    /**
     * Calculate attendance status for check-out
     */
    public function calculateCheckOutStatus(\DateTimeInterface $actualTime): string
    {
        $actualTimeString = $actualTime->format('H:i:s');
        $expectedTime = $this->out_time;

        // Subtract grace period
        $graceTime = \Carbon\Carbon::parse($expectedTime)
            ->subMinutes($this->grace_early_minutes)
            ->format('H:i:s');

        return $actualTimeString >= $graceTime ? 'normal_leave' : 'early_leave';
    }
}
