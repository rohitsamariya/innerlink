<?php

use App\Providers\AppServiceProvider;
use App\Domains\Identity\Providers\IdentityServiceProvider;
use App\Domains\Communication\Providers\CommunicationServiceProvider;
use App\Domains\Admin\Providers\AdminServiceProvider;
use App\Domains\Calling\Providers\CallingServiceProvider;
use App\Providers\EventServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    CommunicationServiceProvider::class,
    AdminServiceProvider::class,
    CallingServiceProvider::class,
    EventServiceProvider::class,
];
