<?php

return [
    'default_provider' => env('LARAVEL_AI_PROVIDER', 'openai'),

    /* Credentials and provider endpoints belong to the SDK's ai.php config. */
    'models' => [
        'openai' => env('LARAVEL_AI_OPENAI_MODEL'),
        'deepseek' => env('LARAVEL_AI_DEEPSEEK_MODEL'),
    ],

    'storage' => [
        'driver' => env('LARAVEL_AI_STORAGE', 'sql'),
        'connection' => env('LARAVEL_AI_CONNECTION', 'mysql'),
    ],

    'executions_table' => 'ai_executions',
];
