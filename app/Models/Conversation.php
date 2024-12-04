<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function artist()
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function details()
    {
        return $this->hasOne(ConversationDetails::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function unreadMessages()
    {
        return $this->messages()->whereNull('read_at');
    }

    public function conversationDetail()
    {
        return $this->details();
    }
}