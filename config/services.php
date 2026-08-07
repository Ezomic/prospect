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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'thijssensoftware' => [
        'base_url' => env('THIJSSENSOFTWARE_ID_URL', 'https://id.thijssensoftware.nl'),
        'client_id' => env('THIJSSENSOFTWARE_ID_CLIENT_ID'),
        'client_secret' => env('THIJSSENSOFTWARE_ID_CLIENT_SECRET'),
        'slug' => env('THIJSSENSOFTWARE_ID_APP_SLUG', 'prospect'),
        'portal_cache_ttl' => (int) env('THIJSSENSOFTWARE_ID_PORTAL_TTL', 300),
    ],

    // IMAP for the outreach sender, used to append sent messages to the Sent
    // folder. Optional: when the host is unset the append is skipped and the
    // send still succeeds. This is the account letters are sent from.
    'outreach_imap' => [
        'host' => env('OUTREACH_IMAP_HOST'),
        'port' => (int) env('OUTREACH_IMAP_PORT', 993),
        'encryption' => env('OUTREACH_IMAP_ENCRYPTION', 'ssl'),
        'username' => env('OUTREACH_IMAP_USERNAME'),
        'password' => env('OUTREACH_IMAP_PASSWORD'),
    ],

    // Every mailbox outreach:poll reads. More than one because outreach predates
    // this app: mail sent by hand from another address gets its replies there,
    // and a mailbox nothing reads is a mailbox whose replies are lost. The
    // sending account above is the first entry; add others as _2_, _3_.
    'outreach_mailboxes' => collect(['', '2_', '3_'])
        ->map(fn (string $slot) => [
            'host' => env("OUTREACH_IMAP_{$slot}HOST"),
            'port' => (int) env("OUTREACH_IMAP_{$slot}PORT", 993),
            'encryption' => env("OUTREACH_IMAP_{$slot}ENCRYPTION", 'ssl'),
            'username' => env("OUTREACH_IMAP_{$slot}USERNAME"),
            'password' => env("OUTREACH_IMAP_{$slot}PASSWORD"),
        ])
        ->filter(fn (array $mailbox) => ! empty($mailbox['host']))
        ->values()
        ->all(),

];
