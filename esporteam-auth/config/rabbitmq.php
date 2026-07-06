<?php

return [
    'host'     => env('RABBITMQ_HOST', 'localhost'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'esporteam'),
    'password' => env('RABBITMQ_PASSWORD', ''),
    'vhost'    => env('RABBITMQ_VHOST', '/'),
];
