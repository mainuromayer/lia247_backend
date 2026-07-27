<?php

namespace App\Jobs;

use App\Mail\PromotionMail;
use App\Models\Ad;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdPromotionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ad;
    protected $baseUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(Ad $ad, string $baseUrl)
    {
        $this->ad = $ad;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            User::where('user_type', 'customer')
                ->where('status', 'active')
                ->chunk(100, function ($users) {
                    foreach ($users as $user) {
                        try {
                            Mail::to($user->email)->send(new PromotionMail($this->ad, $user, $this->baseUrl));
                        } catch (Exception $e) {
                            Log::error("Failed to send promotion email to {$user->email}: " . $e->getMessage());
                        }
                    }
                });
        } catch (Exception $e) {
            Log::error("SendAdPromotionEmailJob failed: " . $e->getMessage());
        }
    }
}
