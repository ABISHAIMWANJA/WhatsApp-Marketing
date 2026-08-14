<?php

use Illuminate\Foundation\Application;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Kernel;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'razorpay/*',
            'subscription/*',
            'whatsapp-webhook/*',
        ]);

        $middleware->append([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'api.vendor.authenticate' => \App\Http\Middleware\ApiVendorAccessCheckpost::class,
            'app_api.vendor.authenticate' => \App\Http\Middleware\AppApiAuthenticateMiddleware::class,
            'vendor.authenticate' => \App\Http\Middleware\VendorAccessCheckpost::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 419) {
                return __apiResponse([
                    'message' => __tr('Token Expired, Please reload and try again.'),
                    'auth_info' => getUserAuthInfo(5),
                    'show_message' => true,
                ], 2);
            }
            return $response;
        });
        // required to run scheduler commands
    })->withBindings([
        Illuminate\Contracts\Console\Kernel::class => \App\Console\Kernel::class,
    ])
    ->create();
