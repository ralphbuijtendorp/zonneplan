<?php

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

return [
    'default' => [
        'path' => __DIR__ . '/../logs/app.log',
        'level' => Level::Debug,
        'name' => 'zonneplan'
    ]
];
