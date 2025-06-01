<?php

use App\Config\Environment;
use App\Controllers\CronjobController;
use App\Controllers\DataController;
use DI\Container;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Initialize environment variables
Environment::init();

$container = new Container();

AppFactory::setContainer($container);
$app = AppFactory::create();

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger
 */
$app->addErrorMiddleware(true, true, true);

$app->get('/execute_cronjob_electricity', [CronjobController::class, 'store_electricity_data']);
$app->get('/execute_cronjob_gas', [CronjobController::class, 'store_gas_data']);
$app->get('/get_electricity_data', [DataController::class, 'get_electricity_data']);
$app->get('/get_gas_data', [DataController::class, 'get_gas_data']);

$app->run();