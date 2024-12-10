<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram',
        'phone',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update settings using dot notation
     * Example: updateSettings(['notifications.email' => false])
     */
    public function updateSettings(array $newSettings): void
    {
        $settings = $this->settings ?? [];

        foreach ($newSettings as $key => $value) {
            Arr::set($settings, $key, $value);
        }

        $this->update(['settings' => $settings]);
    }

    /**
     * Get a specific setting using dot notation
     * Example: getSetting('notifications.email', true)
     */
    public function getSetting(string $key, $default = null)
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }
} 