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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'rekognition' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'bucket'    => env('AWS_BUCKET'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-1'),
        'collection_id' => env('REKOGNITION_COLLECTION_ID', 'dost_users'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

    'procurement_ai' => [
        'base_url' => env('PROCUREMENT_AI_BASE_URL', 'http://127.0.0.1:8010'),
        'api_key' => env('PROCUREMENT_AI_API_KEY'),
        'timeout' => env('PROCUREMENT_AI_TIMEOUT', 90),
        'auto_start' => env('PROCUREMENT_AI_AUTO_START', true),
        'start_command' => env('PROCUREMENT_AI_START_COMMAND') ?: (PHP_OS_FAMILY === 'Windows' ? 'start-oneapp-chatbot.bat' : 'sh start-oneapp-chatbot.sh'),
        'working_dir' => env('PROCUREMENT_AI_WORKING_DIR') ?: base_path('fastapi_ai_service'),
    ],

];
