<?php

return [
    'default'           => env('LLM_DRIVER', 'anthropic'),
    'clustering_prompt' => env('LLM_CLUSTERING_PROMPT', 'clustering_v1'),
    'clustering_model'  => env('LLM_CLUSTERING_MODEL', 'claude-haiku-4-5-20251001'),

    // Limite mensal por workspace em tokens (in + out somados).
    'monthly_token_budget_per_workspace' => (int) env('LLM_MONTHLY_BUDGET', 5_000_000),

    // Limite de rate: 10 chamadas / 60 minutos / workspace.
    'rate_limit' => [
        'max_attempts'    => (int) env('LLM_RATE_MAX', 10),
        'decay_minutes'   => (int) env('LLM_RATE_DECAY', 60),
    ],

    // Pré-clustering com embeddings.
    'pre_cluster_threshold' => (float) env('LLM_PRECLUSTER_THRESHOLD', 0.85),
    'pre_cluster_max_size'  => (int) env('LLM_PRECLUSTER_MAX_SIZE', 10),

    // Janela e contagem do circuit breaker (Redis).
    'circuit_breaker' => [
        'failure_threshold' => (int) env('LLM_CB_THRESHOLD', 5),
        'window_seconds'    => (int) env('LLM_CB_WINDOW', 300),
    ],

    // Watchdog timeout (segundos).
    'watchdog_timeout_seconds' => (int) env('LLM_WATCHDOG_TIMEOUT', 600),

    'providers' => [
        'anthropic' => [
            'api_key'             => env('ANTHROPIC_API_KEY'),
            'base_url'            => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'timeout_seconds'     => (int) env('ANTHROPIC_TIMEOUT', 60),
            'cost_per_1m_in_usd'  => 0.25,  // Haiku 4.5
            'cost_per_1m_out_usd' => 1.25,
        ],
        'openai' => [
            'api_key'                   => env('OPENAI_API_KEY'),
            'base_url'                  => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout_seconds'           => (int) env('OPENAI_TIMEOUT', 60),
            'embedding_model'           => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'embedding_dimensions'      => 1536,
            'cost_per_1m_embedding_usd' => 0.02,
        ],
    ],
];
