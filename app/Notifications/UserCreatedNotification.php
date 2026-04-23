<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    // EMAIL
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New User Created')
            ->greeting('Hello ' . $notifiable->name)
            ->line('A new user has been created.')
            ->line('User Name: ' . $this->user->name)
            ->line('User Email: ' . $this->user->email)
            ->line('Thank you!');
    }

    // DB data to get notification
    public function toArray($notifiable)
    {
        return [
            'message' => 'New user created: ' . $this->user->name,
            'user_id' => $this->user->id,
        ];
    }
}
