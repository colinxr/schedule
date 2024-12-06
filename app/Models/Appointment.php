<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'client_id',
        'conversation_id',
        'starts_at',
        'ends_at',
        'google_event_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getStartsAtAttribute($value): string
    {
        return Carbon::parse($value)->toRfc3339String();
    }

    public function getEndsAtAttribute($value): string
    {
        return Carbon::parse($value)->toRfc3339String();
    }
} 