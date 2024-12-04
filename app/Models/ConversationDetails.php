<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConversationDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'description',
        'reference_images',
        'email',
    ];

    protected $casts = [
        'reference_images' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
