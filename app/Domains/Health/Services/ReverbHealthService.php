<?php

declare(strict_types=1);

namespace App\Domains\Health\Services;

use App\Domains\Health\DTOs\ServiceStatus;
use Illuminate\Support\Facades\Config;

class ReverbHealthService
{
    public function check(): ServiceStatus
    {
        if (Config::get('broadcasting.default') !== 'reverb') {
            return ServiceStatus::down('reverb', 'Broadcast driver is not reverb');
        }

        if (!Config::get('reverb.enabled', false)) {
            return ServiceStatus::down('reverb', 'Reverb is not enabled');
        }

        $host = Config::get('reverb.host');
        if (empty($host)) {
            return ServiceStatus::down('reverb', 'Reverb host not configured');
        }

        $port = Config::get('reverb.port');
        if (!is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
            return ServiceStatus::down('reverb', 'Reverb port invalid');
        }

        $apps = Config::get('reverb.apps');
        if (!is_array($apps) || $apps === []) {
            return ServiceStatus::down('reverb', 'No Reverb apps configured');
        }

        $firstApp = $apps[0] ?? [];
        $hasValidApp = !empty($firstApp['app_id'])
            && !empty($firstApp['key'])
            && !empty($firstApp['secret']);

        if (!$hasValidApp) {
            return ServiceStatus::down('reverb', 'No valid Reverb app configuration found');
        }

        return ServiceStatus::up('reverb');
    }
}
