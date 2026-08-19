<?php

use App\Providers\AiRetrievalServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ServiceOwnershipProvider;

return [
    AppServiceProvider::class,
    AiRetrievalServiceProvider::class,
    AuthServiceProvider::class,
    ServiceOwnershipProvider::class,
];
