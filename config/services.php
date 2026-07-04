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

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        // 2026-07-04: manual testing hit real ~20s client timeouts ("0 bytes
        // received" — the model was still reasoning when we gave up waiting).
        // gemini-3.5-flash defaults to dynamic (~medium) thinking; lowering
        // this trades a bit of answer sophistication for the latency a real-time
        // chat companion needs. Valid values (Gemini 3.x family only):
        // "minimal", "low", "medium", "high". Empty/null skips sending
        // thinkingConfig at all (needed if GEMINI_MODEL is ever swapped to a
        // Gemini 2.5-series model, which uses thinkingBudget instead and would
        // likely reject/ignore an unrecognized thinkingLevel field).
        // Bumped from "low" to "minimal" same day after a live side-by-side
        // (new dedicated GCP project, fresh quota): minimal ~2.1s vs low
        // ~4.72s / medium ~4.37s for the same simple question — user confirmed
        // speed matters more than answer sophistication for this use case.
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'minimal'),
    ],

];
