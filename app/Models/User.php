<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements AuthenticatableContract
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function unreadMessages()
    {
        return $this->messages()->whereNull('read_at');
    }

    public function details(): HasOne
    {
        return $this->hasOne(ConversationDetails::class, 'conversation_id')
            ->whereHas('conversation', function($query) {
                $query->where('client_id', $this->id);
            });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}
