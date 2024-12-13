<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ConversationsTableSeeder extends Seeder
{
    private function generateTattooDescription(): string
    {
        $styles = ['Traditional', 'Japanese', 'Blackwork', 'Realism', 'Neo-traditional', 'Watercolor'];
        $placements = ['Forearm', 'Upper arm', 'Back', 'Leg', 'Chest', 'Shoulder'];
        $sizes = ['Small (2-3 inches)', 'Medium (4-6 inches)', 'Large (7-10 inches)', 'Extra large (11+ inches)'];

        return fake()->randomElement([
            "Looking to get a {$styles[array_rand($styles)]} style tattoo on my {$placements[array_rand($placements)]}. Thinking about {$sizes[array_rand($sizes)]} in size.",
            "Hi! I'm interested in getting a custom {$styles[array_rand($styles)]} design. Would love to discuss ideas.",
            "Been following your work for a while. Want to get a {$placements[array_rand($placements)]} piece in your style.",
            "Need help designing a meaningful piece. Interested in your {$styles[array_rand($styles)]} work.",
        ]);
    }

    private function generateMessages(Conversation $conversation): void
    {
        $messages = [
            // Initial client message
            [
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->client_id,
                'content' => $this->generateTattooDescription(),
                'created_at' => $conversation->created_at,
            ],
            // Artist response
            [
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->artist_id,
                'content' => fake()->randomElement([
                    "Thanks for reaching out! I'd love to help bring your vision to life. Could you share any reference images that inspire you?",
                    "Hey there! Thanks for your interest. Your idea sounds great, and I think it would work well with my style. When were you thinking of getting it done?",
                    "Hello! Thank you for contacting me. This sounds like an interesting project. Would you be able to come in for a consultation?",
                ]),
                'created_at' => Carbon::parse($conversation->created_at)->addHours(2),
            ],
            // Client follow-up
            [
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->client_id,
                'content' => fake()->randomElement([
                    "I'll gather some reference images and send them over. What's your availability like in the next few weeks?",
                    "Thanks for the quick response! I'm pretty flexible with timing. Do you have any openings next month?",
                    "A consultation would be great! What days/times work best for you?",
                ]),
                'created_at' => Carbon::parse($conversation->created_at)->addHours(3),
            ],
        ];

        foreach ($messages as $message) {
            Message::create($message);
        }

        // Update last_message_at
        $conversation->update(['last_message_at' => $messages[count($messages) - 1]['created_at']]);
    }

    public function run(): void
    {
        // Get all artists and clients
        $artists = User::where('role', 'artist')->get();
        $clients = User::where('role', 'client')->get();

        // Create 20 conversations
        collect()->times(20, function () use ($artists, $clients) {
            // Create conversation with random artist and client
            $conversation = Conversation::create([
                'artist_id' => $artists->random()->id,
                'client_id' => $clients->random()->id,
                'status' => fake()->randomElement(['pending', 'active', 'closed']),
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);

            // Create conversation details
            ConversationDetails::create([
                'conversation_id' => $conversation->id,
                'description' => $this->generateTattooDescription(),
                'email' => $conversation->client->email,
                'phone' => $conversation->client->profile->phone,
                'instagram' => $conversation->client->profile->instagram,
            ]);

            // Generate messages for the conversation
            $this->generateMessages($conversation);
        });
    }
} 