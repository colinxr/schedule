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
        'status',
        'notes',
        'price',
        'deposit_amount',
        'deposit_paid_at'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    /**
     * Get upcoming appointments for an artist
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now())
                    ->orderBy('starts_at');
    }

    /**
     * Get appointments for a specific date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where('starts_at', '>=', $startDate)
                    ->where('starts_at', '<=', $endDate)
                    ->orderBy('starts_at');
    }

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

    /**
     * Calculate the default deposit amount based on the artist's settings
     */
    public function calculateDefaultDepositAmount(): ?float
    {
        if (!$this->price || !$this->artist || !$this->artist->profile) {
            return null;
        }

        $percentage = $this->artist->profile->getSetting('deposit_percentage', 30);
        return round($this->price * ($percentage / 100), 2);
    }

    /**
     * Mark the deposit as paid
     */
    public function markDepositAsPaid(): void
    {
        $this->update(['deposit_paid_at' => now()]);
    }

    /**
     * Check if the deposit has been paid
     */
    public function isDepositPaid(): bool
    {
        return !is_null($this->deposit_paid_at);
    }

    /**
     * Get the remaining balance after deposit
     */
    public function getRemainingBalance(): ?float
    {
        if (!$this->price || !$this->deposit_amount) {
            return $this->price;
        }

        return round($this->price - $this->deposit_amount, 2);
    }
} 