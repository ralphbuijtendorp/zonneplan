<?php

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/logging.php';
    }

    public function createLogger(string $channel = null): LoggerInterface
    {
        $config = $this->config['default'];
        $logger = new Logger($channel ?? $config['name']);

        // Create a custom line formatter with colors enabled
        $dateFormat = 'Y-m-d H:i:s';
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat, true, true);

        // Create handler with formatter
        $handler = new StreamHandler($config['path'], $config['level']);
        $handler->setFormatter($formatter);
        
        // Add the handler
        $logger->pushHandler($handler);

        // Add introspection processor for file/line information
        $logger->pushProcessor(new IntrospectionProcessor(
            Logger::DEBUG,     // Level threshold
            ['Monolog\\', 'Illuminate\\']  // Skip these namespaces
        ));

        return $logger;
    }
}
