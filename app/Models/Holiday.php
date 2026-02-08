<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Check if a specific date is a holiday
     */
    public static function isHoliday(\DateTimeInterface $date): bool
    {
        return self::where('date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if today is a holiday
     */
    public static function isTodayHoliday(): bool
    {
        return self::isHoliday(now());
    }

    /**
     * Get holiday for a specific date
     */
    public static function getHolidayForDate(\DateTimeInterface $date): ?self
    {
        return self::where('date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->first();
    }
}
