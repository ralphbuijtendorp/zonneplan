<?php

use App\Interfaces\DataServiceInterface;
use App\Interfaces\EnergyProviderInterface;
use App\Providers\ZonneplanProvider;
use App\Services\DataService;
use App\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;
use function DI\get;

return [
    // Logger configuration
    LoggerInterface::class => function () {
        $factory = new LoggerFactory();
        return $factory->createLogger();
    },

    // Interface bindings
    DataServiceInterface::class => get(DataService::class),
    EnergyProviderInterface::class => get(ZonneplanProvider::class)
];
