<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\DTOs\ServiceStatus;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseHealthService
{
    private const TIMEOUT = 2;

    public function check(): ServiceStatus
    {
        try {
            $connection = DB::connection();

            if ($connection->getDriverName() === 'pgsql') {
                $connection->statement('SET statement_timeout TO 2000');
            }

            $connection->statement('SELECT 1');

            if ($connection->getDriverName() === 'pgsql') {
                $connection->statement('SET statement_timeout TO DEFAULT');
            }

            return ServiceStatus::up('database');
        } catch (Throwable) {
            return ServiceStatus::down('database', 'Database connection failed');
        }
    }
}
