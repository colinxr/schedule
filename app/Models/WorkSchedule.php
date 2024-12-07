<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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