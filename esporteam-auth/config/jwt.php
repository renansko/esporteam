<?php

return [
    'private_key' => '/var/www/keys/private.pem',
    'public_key' => '/var/www/keys/public.pem',
    'ttl' => (int) env('JWT_TTL', 31536000),
];
