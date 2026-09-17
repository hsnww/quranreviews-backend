<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('quiz-session-create', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        // حد عام لكل مسارات الـ API لمنع إغراق الخادم بالطلبات.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        /*
         * مسارات المصادقة تُهاجَم بتخمين كلمات المرور، لذلك حدّ صارم
         * على البريد + الـ IP معًا حتى لا يُحجب مستخدم بريء خلف نفس الشبكة.
         */
        RateLimiter::for('auth', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
            ];
        });
    }
}
