<?php

use App\Config\Environment;

return [
    'zonneplan' => [
        'base_url' => Environment::get('ZONNEPLAN_API_BASE_URL'),
        'secret' => Environment::get('ZONNEPLAN_API_SECRET'),
        'endpoints' => [
            'electricity' => '/energy-prices/electricity/upcoming',
            'gas' => '/energy-prices/gas/upcoming',

        ]
    ]
];
