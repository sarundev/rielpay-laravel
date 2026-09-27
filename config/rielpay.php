<?php

return [

    /*
    | Your store's secret API key (Dashboard → Stores → your store → API keys).
    | Keep it on the server only — never ship it to a browser or mobile app.
    */
    'api_key' => env('RIELPAY_API_KEY'),

    /*
    | Signing secret for webhooks (Dashboard → Stores → your store → Webhooks).
    */
    'webhook_secret' => env('RIELPAY_WEBHOOK_SECRET'),

    'base_url' => env('RIELPAY_BASE_URL', 'https://rielpays.com'),

    /*
    | Where RielPay should POST webhook events. Set the same URL in the dashboard,
    | e.g. https://your-shop.com/rielpay/webhook. Set to null to register your own route.
    */
    'webhook_path' => env('RIELPAY_WEBHOOK_PATH', 'rielpay/webhook'),

    // Reject webhook signatures older than this many seconds (replay protection).
    'webhook_tolerance' => 300,

    // Creating a payment asks ABA for a fresh KHQR; allow it enough time.
    'timeout' => 30,

    // Retries for temporary failures (network errors, 502/503) with an idempotency key.
    'retries' => 2,
];
