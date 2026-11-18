<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'namdel' => [
        'admin_user' => env('ADMIN_USER'),
        'admin_pass' => env('ADMIN_PASS'),
        'whatsapp_number' => env('WHATSAPP_NUMBER', '6285777103329'),
        'trusted_proxies' => env('TRUSTED_PROXIES'),
        'webhook_url' => env('WEBHOOK_URL'),
        'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

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

];
