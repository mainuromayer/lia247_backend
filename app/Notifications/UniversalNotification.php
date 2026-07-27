<?php

namespace App\Notifications;

use App\Helper\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UniversalNotification extends Notification
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected ?string $type;
    protected ?array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $type = 'info', ?array $data = [])
    {
        $this->title   = $title;
        $this->message = $message;
        $this->type    = $type;
        $this->data    = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'title'   => $this->title,
            'message' => $this->message,
            'type'    => $this->type,
            'data'    => $this->data
        ];

        if (($notifiable->push_notification ?? true) && method_exists($notifiable, 'firebaseTokens')) {
            $tokens = $notifiable->firebaseTokens()->where('is_active', 1)->pluck('token');
            foreach ($tokens as $token) {
                if ($notifiable->user_type === 'customer') {
                    Helper::sendCustomerNotifyMobile($token, $data);
                } else {
                    Helper::sendRiderNotifyMobile($token, $data);
                }
            }
        }

        return $data;
    }
}
