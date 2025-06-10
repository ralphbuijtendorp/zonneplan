<?php

use App\Config\Environment;
use App\Controllers\CronjobController;
use App\Controllers\DataController;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\CorsMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Initialize environment variables
Environment::init();

// Create container with configuration
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');    
});

// Add OPTIONS route for CORS preflight requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger
 */
$app->addErrorMiddleware(true, true, true);

$app->get('/execute_cronjob_electricity', [CronjobController::class, 'storeElectricityData']);
$app->get('/execute_cronjob_gas', [CronjobController::class, 'storeGasData']);
$app->get('/get_electricity_data', [DataController::class, 'getElectricityData']);
$app->get('/get_gas_data', [DataController::class, 'getGasData']);

$app->run();