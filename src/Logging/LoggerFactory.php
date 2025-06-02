<?php

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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

        $logger->pushHandler(
            new StreamHandler(
                $config['path'],
                $config['level']
            )
        );

        return $logger;
    }
}
