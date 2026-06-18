<?php

use App\Providers\AppServiceProvider;
use App\Domains\Identity\Providers\IdentityServiceProvider;
use App\Domains\Communication\Providers\CommunicationServiceProvider;
use App\Domains\Admin\Providers\AdminServiceProvider;
use App\Providers\EventServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    CommunicationServiceProvider::class,
    AdminServiceProvider::class,
    EventServiceProvider::class,
];
