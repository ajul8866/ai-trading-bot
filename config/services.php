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

    /*
    |--------------------------------------------------------------------------
    | Binance API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Binance Futures API integration. Get your API keys from
    | https://www.binance.com/en/my/settings/api-management
    |
    | IMPORTANT: For testing, use Binance Testnet:
    | https://testnet.binancefuture.com
    |
    */

    'binance' => [
        'api_key' => env('BINANCE_API_KEY'),
        'api_secret' => env('BINANCE_API_SECRET'),
        'testnet' => env('BINANCE_TESTNET', false),
        'base_url' => env('BINANCE_TESTNET', false)
            ? 'https://testnet.binancefuture.com'
            : 'https://fapi.binance.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenRouter AI service. Get your API key from
    | https://openrouter.ai/keys
    |
    | OpenRouter provides access to multiple AI models including Claude,
    | GPT-4, and many others.
    |
    */

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('AI_MODEL', 'anthropic/claude-3.5-sonnet'),
        'temperature' => env('AI_TEMPERATURE', 0.3),
    ],

];
