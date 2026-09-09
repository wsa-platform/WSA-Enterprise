<?php

use App\Providers\AgriculturalIntelligenceServiceProvider;
use App\Providers\AiRetrievalServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ServiceOwnershipProvider;

return [
    AppServiceProvider::class,
    AiRetrievalServiceProvider::class,
    AgriculturalIntelligenceServiceProvider::class,
    AuthServiceProvider::class,
    ServiceOwnershipProvider::class,
];
