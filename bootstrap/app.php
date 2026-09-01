<?php

use App\Http\Middleware\EnsureConnectEnabled;
use App\Http\Middleware\EnsureGuardian;
use App\Http\Middleware\EnsureMemberProfile;
use App\Http\Middleware\EnsureOperator;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SetLocale::class]);

        $middleware->alias([
            // The mode wall. Connect routes are unreachable without an
            // explicitly enabled, separately verified Connect profile.
            'connect'  => EnsureConnectEnabled::class,
            'member'   => EnsureMemberProfile::class,
            'guardian' => EnsureGuardian::class,
            'operator' => EnsureOperator::class,
            'staff'    => EnsureStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
