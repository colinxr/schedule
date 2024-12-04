<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IntakeForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'description',
        'placement',
        'size',
        'reference_images',
        'budget_range',
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
