<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'timezone',
        'is_active'
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get active schedules for a specific day
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true);
    }

    /**
     * Get cached work schedule for a user
     */
    public static function getCachedSchedule($userId)
    {
        $cacheKey = "work_schedule:{$userId}";
        
        return Cache::remember($cacheKey, now()->addMonths(6), function () use ($userId) {
            return static::where('user_id', $userId)
                        ->where('is_active', true)
                        ->orderBy('day_of_week')
                        ->get();
        });
    }

    /**
     * Clear cached schedule for a user
     */
    public static function clearCachedSchedule($userId)
    {
        Cache::forget("work_schedule:{$userId}");
    }

    protected static function booted()
    {
        // Clear cache when schedule is updated
        static::saved(function ($schedule) {
            static::clearCachedSchedule($schedule->user_id);
        });

        static::deleted(function ($schedule) {
            static::clearCachedSchedule($schedule->user_id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Add mutators to ensure proper time format
    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = date('H:i:s', strtotime($value));
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = date('H:i:s', strtotime($value));
    }
} 