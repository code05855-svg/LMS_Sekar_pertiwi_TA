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

    /*
    |--------------------------------------------------------------------------
    | n8n (chatbot akademik)
    |--------------------------------------------------------------------------
    |
    | token  : dikirim n8n ke Laravel sebagai "Authorization: Bearer ..."
    |          (AuthenticateN8nToken, route /api/n8n/*).
    | secret : dikirim Laravel ke n8n sebagai header X-N8N-Secret
    |          (N8nClient, node Webhook memeriksanya).
    */
    'n8n' => [
        'token' => env('N8N_API_TOKEN'),
        'secret' => env('N8N_WEBHOOK_SECRET'),
        'webhook_indeks' => env('N8N_WEBHOOK_INDEKS'),
        'webhook_tanya' => env('N8N_WEBHOOK_TANYA'),
        'timeout' => (int) env('N8N_TIMEOUT', 60),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
