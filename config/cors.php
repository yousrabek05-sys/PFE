<?php

return [

    // Which routes accept cross-origin requests
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Which HTTP methods are allowed
    'allowed_methods' => ['*'],

    // Which origins (URLs) are allowed to call your API
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    // Which headers are allowed in requests
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Cache preflight request for 24 hours (performance)
    'max_age' => 86400,

    // Allow cookies/tokens to be sent with requests
    'supports_credentials' => false,
];