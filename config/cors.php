<?php
/*
return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'user'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://quran-review.test:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
*/

return [
    'paths' => [
        'api/*',
    ],

    'allowed_methods' => ['*'],

    // الدومينات المسموح بها (Production)
    'allowed_origins' => [
        'https://www.quranreview.app',
        'https://quranreview.app',
        'http://quran-review.test:3000',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ],

    // السماح بروابط Preview من Vercel
    'allowed_origins_patterns' => [
        '#^https://.*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // نستخدم Bearer Token، لذلك اجعلها false
    'supports_credentials' => false,
];
