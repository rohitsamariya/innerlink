<?php

declare(strict_types=1);

namespace App\Domains\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Admin\Contracts\Repositories\AuditRepositoryInterface;
use App\Domains\Admin\Contracts\Repositories\ExportRepositoryInterface;
use App\Domains\Admin\Infrastructure\Repositories\AuditRepository;
use App\Domains\Admin\Infrastructure\Repositories\ExportRepository;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuditRepositoryInterface::class,
            AuditRepository::class
        );

        $this->app->bind(
            ExportRepositoryInterface::class,
            ExportRepository::class
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
