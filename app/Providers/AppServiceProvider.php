<?php

namespace App\Providers;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
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
        // This sets the URL that the reset password email will use
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return "http://127.0.0.1:5500/reset-password.html?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
