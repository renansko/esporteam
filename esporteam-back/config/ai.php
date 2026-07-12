<?php

return [
    'default' => 'openai',
    'default_for_embeddings' => 'openai',

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', env('OPENAI_BASE_URL', 'https://api.openai.com/v1')),
        ],
    ],
];
