<?php

use App\Config\Environment;
use App\Controllers\DataController;
use App\Http\HttpClient;
use App\Services\ResponseService;
use App\Services\ZonneplandataService;
use DI\Container;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Initialize environment variables
Environment::init();

$container = new Container();

// Register services
$container->set(HttpClient::class, function() {
    return new HttpClient();
});

$container->set(ResponseService::class, function() {
    return new ResponseService();
});

$container->set(ZonneplandataService::class, function(Container $container) {
    return new ZonneplandataService($container->get(HttpClient::class));
});

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

$app->get('/execute_cronjob', [DataController::class, 'store_actual_data']);
$app->get('/get_data', [DataController::class, 'get_data']);


$app->run();