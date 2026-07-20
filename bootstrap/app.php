<?php

use App\Http\Middleware\Api\LogApiRequest;
use App\Http\Middleware\Api\ValidateCommuneStoreFields;
use App\Http\Middleware\Api\ValidateCommuneUpdateFields;
use App\Http\Middleware\Api\ValidateCustomerChangePasswordFields;
use App\Http\Middleware\Api\ValidateCustomerUpdateFields;
use App\Http\Middleware\Api\ValidateCustomerQueryFields;
use App\Http\Middleware\Api\ValidateCustomerStoreFields;
use App\Http\Middleware\Api\ValidateLoginFields;
use App\Http\Middleware\Api\ValidateRegionStoreFields;
use App\Http\Middleware\Api\ValidateRegionUpdateFields;
use App\Http\Middleware\Api\VerifyApiToken;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'api.token' => VerifyApiToken::class,
            'api.log' => LogApiRequest::class,
            'validate.login' => ValidateLoginFields::class,
            'validate.customer.store' => ValidateCustomerStoreFields::class,
            'validate.customer.query' => ValidateCustomerQueryFields::class,
            'validate.customer.update' => ValidateCustomerUpdateFields::class,
            'validate.customer.change-password' => ValidateCustomerChangePasswordFields::class,
            'validate.region.store' => ValidateRegionStoreFields::class,
            'validate.region.update' => ValidateRegionUpdateFields::class,
            'validate.commune.store' => ValidateCommuneStoreFields::class,
            'validate.commune.update' => ValidateCommuneUpdateFields::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
