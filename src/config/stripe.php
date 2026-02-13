<?php

return [
    'public_key' => env('STRIPE_PUBLIC_KEY', 'pk_test_51234567890abcdef'),
    'secret_key' => env('STRIPE_SECRET_KEY', 'sk_test_51234567890abcdef'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', 'whsec_test_1234567890abcdef'),
    
    
    // 環境別設定
    'environment' => env('STRIPE_ENVIRONMENT', 'test'), // test or live
    'webhook_enabled' => env('STRIPE_WEBHOOK_ENABLED', false), // Webhook有効化フラグ
];
