<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewPostFromFavoriteUser extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private Post $post)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
                    ->subject("New post from {$this->post->user->name}")
                    ->greeting("Hello {$notifiable->name}!")
                    ->line("{$this->post->user->name} posted a new article:")
                    ->line("**{$this->post->title}**")
                    ->line($this->post->body)
                    ->action('Read Post', config('app.frontend_url') . '/posts/' . $this->post->id)
                    ->line('Thank you for using Chipper!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'author_id' => $this->post->user->id,
            'author_name' => $this->post->user->name,
            'post_title' => $this->post->title,
        ];
    }
}
