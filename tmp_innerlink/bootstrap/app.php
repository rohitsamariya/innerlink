<?php

use App\Domains\Identity\Console\Commands\CreateAdminCommand;
use App\Domains\Identity\Console\Commands\CreateUserCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.only' => \App\Domains\Identity\Http\Middleware\AdminOnlyMiddleware::class,
            'active.user' => \App\Domains\Identity\Http\Middleware\ActiveUserMiddleware::class,
            'track.last.seen' => \App\Domains\Identity\Http\Middleware\TrackLastSeenMiddleware::class,
            'health.headers' => \App\Domains\Health\Http\Middleware\HealthSecurityHeaders::class,
        ]);
    })
    ->withCommands([
        CreateAdminCommand::class,
        CreateUserCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

