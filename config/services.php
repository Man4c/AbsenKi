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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-2'),
        'bucket' => env('AWS_BUCKET'),
    ],

    'rekognition' => [
        'collection' => env('REKOG_COLLECTION', 'staf_desa_teromu'),
        'threshold' => env('FACE_THRESHOLD', 80),
    ],

    'face' => [
        // Python binary used to run OpenCV scripts (crop & quality check).
        // On many Linux shared hostings, `python` is not available and you must use `python3`.
        'python_bin' => env('PYTHON_BIN'),

        'min_laplace' => env('FACE_MIN_LAPLACE', 100),
        'min_brightness' => env('FACE_MIN_BRIGHTNESS', 65),
        'min_width' => env('FACE_MIN_WIDTH', 200),
        'min_height' => env('FACE_MIN_HEIGHT', 200),

        // Adaptive threshold settings
        'enable_adaptive_laplace' => env('ENABLE_ADAPTIVE_LAPLACE', true),
        'laplace_base' => env('FACE_LAPLACE_BASE', 100),
        'laplace_min' => env('FACE_LAPLACE_MIN', 60),
        'laplace_max' => env('FACE_LAPLACE_MAX', 140),
        'target_brightness' => env('FACE_TARGET_BRIGHTNESS', 90),

        // Client-side enhancement settings
        'enable_client_enhance' => env('ENABLE_CLIENT_ENHANCE', true),
        'client_jpeg_quality' => env('CLIENT_JPEG_QUALITY', 0.85),
        'client_unsharp_amount' => env('CLIENT_UNSHARP_AMOUNT', 0.6),
        'client_unsharp_radius' => env('CLIENT_UNSHARP_RADIUS', 1.0),
        'client_brightness_delta' => env('CLIENT_BRIGHTNESS_DELTA', 0.0),
        'client_contrast_factor' => env('CLIENT_CONTRAST_FACTOR', 1.08),
    ],

];
