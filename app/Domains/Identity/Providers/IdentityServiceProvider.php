<?php

declare(strict_types=1);

namespace App\Domains\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Identity\Contracts\Repositories\UserRepositoryInterface;
use App\Domains\Identity\Infrastructure\Repositories\UserRepository;

class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
