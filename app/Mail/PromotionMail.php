<?php

namespace App\Mail;

use App\Models\Ad;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PromotionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $ad;
    public $user;
    public $baseUrl;
    public $systemSetting;

    /**
     * Create a new message instance.
     */
    public function __construct(Ad $ad, User $user, string $baseUrl)
    {
        $this->ad = $ad;
        $this->user = $user;
        $this->baseUrl = $baseUrl;
        $this->systemSetting = SystemSetting::first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = $this->systemSetting->system_title ?? config('app.name', 'LIA247');
        return new Envelope(
            subject: 'Exclusive Update & Promotion from ' . $appName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.promotion',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
