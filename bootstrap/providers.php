<?php

use App\Providers\AppServiceProvider;
use Modules\Billing\Providers\BillingServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    BillingServiceProvider::class,
    IdentityServiceProvider::class,
];
