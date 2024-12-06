<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewConversationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        // Add email channel if user has email notifications enabled
        if ($notifiable->profile && $notifiable->profile->getSetting('notifications.email', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New conversation request from {$this->conversation->client->name}")
            ->line("You have received a new conversation request from {$this->conversation->client->name}.")
            ->when($this->conversation->details?->description, function (MailMessage $mail) {
                return $mail->line("Message: {$this->conversation->details->description}");
            })
            ->action('View Conversation', url("/conversations/{$this->conversation->id}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'client_id' => $this->conversation->client_id,
            'client_name' => $this->conversation->client->name,
            'description' => $this->conversation->details->description ?? null,
            'created_at' => $this->conversation->created_at,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
