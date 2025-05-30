<?php

namespace App\Config;

use Dotenv\Dotenv;

class Environment
{
    private static bool $initialized = false;

    public static function init(): void
    {
        if (!self::$initialized) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
            self::$initialized = true;
        }
    }

    public static function get(string $key, string $default = null): ?string
    {
        self::init();
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
