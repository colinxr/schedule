<?php

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_are_ordered_by_latest_first(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->has(Message::factory()->count(3))
            ->create();

        $messages = $conversation->messages;
        
        $this->assertTrue(
            $messages->first()->created_at->isAfter($messages->last()->created_at),
            'Messages should be ordered by latest first'
        );
    }

    public function test_messages_can_be_paginated(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->has(Message::factory()->count(75))
            ->create();

        $firstPage = $conversation->messages()->paginate(50);
        
        $this->assertCount(50, $firstPage);
        $this->assertTrue($firstPage->hasMorePages());
        
        $secondPage = $conversation->messages()->paginate(50, ['*'], 'page', 2);
        
        $this->assertCount(25, $secondPage);
        $this->assertFalse($secondPage->hasMorePages());
        
        // Ensure no duplicate messages between pages
        $this->assertEmpty(
            $firstPage->pluck('id')->intersect($secondPage->pluck('id'))
        );
    }
} 