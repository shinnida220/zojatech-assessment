<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteUser extends Notification implements ShouldQueue
{
    use Queueable;

    public $inviteText;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $inviteText)
    {
        $this->inviteText = $inviteText;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('You are invited to register on '. config('app.name'))
            ->from("info@domain.com")
            ->greeting('Hi there,')
            ->line($this->inviteText)
            // ->action('Notification Action', url('/'))
            ->line('Thank you for honouring our invitation!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
