<?php

declare(strict_types=1);

namespace App\Domains\Health\Contracts;

use App\Domains\Health\DTOs\HealthCheckResult;

interface HealthExecutionStrategy
{
    public function execute(): HealthCheckResult;
}
