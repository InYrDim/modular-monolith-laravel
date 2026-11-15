<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    IdentityServiceProvider::class,
];
