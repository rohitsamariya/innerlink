<?php

declare(strict_types=1);

namespace App\Domains\Calling\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Calling\Contracts\Repositories\CallRepositoryInterface;
use App\Domains\Calling\Infrastructure\Repositories\CallRepository;

class CallingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CallRepositoryInterface::class,
            CallRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
