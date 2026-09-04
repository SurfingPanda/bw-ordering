<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // PayMongo (hosted Checkout Sessions). When `secret` is unset, online
    // payment is simply disabled and the app keeps using the manual QR / cash.
    'paymongo' => [
        'secret' => env('PAYMONGO_SECRET_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        // Methods offered on the hosted page (PayMongo must enable each on the account).
        'methods' => array_filter(array_map('trim', explode(',', env('PAYMONGO_METHODS', 'gcash,card,paymaya,grab_pay')))),
    ],

    // Where the frontend lives, for PayMongo success/cancel redirects.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    // Groq (free-tier hosted LLM) powers the public shop assistant widget.
    // When `key` is unset the widget doesn't render and POST /assistant/chat
    // 404s — same silent-disable pattern as PayMongo above. `model` is the
    // primary model; `fallback_model` is tried once when the primary errors.
    //
    // Groq churns its catalogue — verify a model still exists with
    //   curl https://api.groq.com/openai/v1/models -H "Authorization: Bearer $GROQ_API_KEY"
    // before pinning it. As of 2026-09 the Llama IDs are gone; the gpt-oss
    // pair below are current. They're reasoning models (a separate `reasoning`
    // field precedes `content`), which is why GroqAssistantService asks for a
    // generous max_tokens.
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'fallback_model' => env('GROQ_FALLBACK_MODEL', 'openai/gpt-oss-20b'),
    ],

];
