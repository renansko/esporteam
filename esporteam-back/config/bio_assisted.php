<?php

return [
    'provider' => env('BIO_ASSISTED_PROVIDER', 'openai'),
    'model' => env('BIO_ASSISTED_MODEL', 'gpt-4o-mini'),
    'prompt_version' => env('BIO_ASSISTED_PROMPT_VERSION', 'bio_v1'),
    'max_instruction_chars' => (int) env('BIO_ASSISTED_MAX_INSTRUCTION_CHARS', 500),
    'max_bio_chars' => (int) env('BIO_ASSISTED_MAX_BIO_CHARS', 320),
    'timeout_seconds' => (int) env('BIO_ASSISTED_TIMEOUT_SECONDS', 30),
    'rate_limit' => [
        'max_attempts' => (int) env('BIO_ASSISTED_RATE_MAX', 5),
        'decay_seconds' => (int) env('BIO_ASSISTED_RATE_DECAY_SECONDS', 3600),
    ],
];
