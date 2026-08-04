<?php

namespace App\Providers;

use App\Models\DynamicPage;
use App\Models\SystemSetting;
use App\Services\TwilioService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Apple\Provider as AppleProvider;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

use Stripe\Stripe;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return ($user->hasRole('admin') || $user->email === 'admin@admin.com') ? true : null;
        });

        Stripe::setApiKey(config('services.stripe.secret'));

        View::composer('*', function ($view) {
            // Greetings
            $currentHour = \Carbon\Carbon::now()->hour;
            if ($currentHour < 12) {
                $greetings = [
                    'type' => 'morning',
                    'message' => "Good Morning!"
                ];
            } elseif ($currentHour < 18) {
                $greetings = [
                    'type' => 'afternoon',
                    'message' => "Good Afternoon!"
                ];
            } else {
                $greetings = [
                    'type' => 'evening',
                    'message' => "Good Evening!"
                ];
            }
            $setting = SystemSetting::first();
            $dynamicpage = DynamicPage::where('status', 1)->get();

            $view->with('setting', $setting);
            $view->with('dynamicpages', $dynamicpage);
            $view->with('greetings', $greetings);
        });


        Mail::extend('brevo', function () {
            $config = $this->app['config']->get('services.brevo', []);
            $key = $config['key'] ?? '';

            if (str_starts_with($key, 'xsmtpsib-')) {
                $transport = (new BrevoTransportFactory())->create(
                    new Dsn(
                        'brevo+smtp',
                        'default',
                        $config['username'] ?? '',
                        $key
                    )
                );

                if ($transport instanceof EsmtpTransport) {
                    $stream = $transport->getStream();
                    if ($stream instanceof SocketStream) {
                        $stream->setStreamOptions([
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true,
                            ]
                        ]);
                    }
                }

                return $transport;
            }

            return (new BrevoTransportFactory())->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    $key
                )
            );
        });
    }
}
