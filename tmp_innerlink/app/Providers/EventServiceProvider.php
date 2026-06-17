<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Identity\Listeners\UpdateUserPresence;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Reverb\Events\ChannelCreated;
use Laravel\Reverb\Events\ChannelRemoved;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ChannelCreated::class => [
            UpdateUserPresence::class,
        ],
        ChannelRemoved::class => [
            UpdateUserPresence::class,
        ],
    ];
}
