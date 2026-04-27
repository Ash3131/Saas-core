<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;

    public $tries = 3;
    public $timeout = 60;

    public function backoff()
    {
        return [10, 30];
    }

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function viaQueues()
    {
        return [
            'mail' => 'high',
            'database' => 'default',
        ];
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
