<?php

declare(strict_types=1);

namespace App\Domains\Communication\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Communication\Contracts\Repositories\MessageRepositoryInterface;
use App\Domains\Communication\Contracts\Repositories\GroupMembershipRepositoryInterface;
use App\Domains\Communication\Infrastructure\Repositories\MessageRepository;
use App\Domains\Communication\Infrastructure\Repositories\GroupMembershipRepository;

class CommunicationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MessageRepositoryInterface::class,
            MessageRepository::class
        );

        $this->app->bind(
            GroupMembershipRepositoryInterface::class,
            GroupMembershipRepository::class
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
