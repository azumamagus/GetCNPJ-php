<?php

declare(strict_types=1);

return [
    'timeout' => (float) env('GETCNPJ_TIMEOUT', 30),
    'max_requests_per_minute' => (int) env('GETCNPJ_MAX_REQUESTS_PER_MINUTE', 3),

    'providers' => [
        'cnpj_ws' => (bool) env('GETCNPJ_ENABLE_CNPJ_WS', true),
        'receita_ws' => (bool) env('GETCNPJ_ENABLE_RECEITA_WS', true),
        'brasil_api' => (bool) env('GETCNPJ_ENABLE_BRASIL_API', true),
        'cnpja' => (bool) env('GETCNPJ_ENABLE_CNPJA', true),
    ],
];
