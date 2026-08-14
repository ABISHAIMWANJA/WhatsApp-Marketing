<?php

namespace App\Providers;

use App\Models\User;
use App\Yantrana\Components\Auth\Models\AuthModel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Str;

class FortifyServiceProvider extends ServiceProvider
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
        // Login view
        Fortify::loginView(function () {
            // return view('auth.login');
            return redirect()->route('auth.login');
        });

        // Two-factor challenge view (🚀 important for you)
        Fortify::twoFactorChallengeView(function () {
            return view('auth.two-factor-challenge');
        });

        // Rate limiter for login
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email . $request->ip());
        });

        // Rate limiter for 2FA
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // How users are authenticated (customizable)
        Fortify::authenticateUsing(function (Request $request) {

            // check if user wants to login using username
            if (! Str::contains($request->email, ['@'])) {
                if (is_numeric($request->email)) {
                    $columnNameForLogin = 'mobile_number';
                } else {
                    $columnNameForLogin = 'username';
                }
            } else {
                $columnNameForLogin = 'email';
            }

            $user = AuthModel::where($columnNameForLogin, $request->email)->first();
            
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }
        });
    }
}
