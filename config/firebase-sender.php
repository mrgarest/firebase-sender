<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Firebase Sender
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'google_access_token' => true // Allows caching Google access tokens for sending notification requests. It is recommended to enable caching to reduce the number of unnecessary requests for obtaining a token.
    ],

    'job' => [
        'send_timeout' => 600 // Allows to set the number of seconds during which a task can send messages before the timeout expires.
    ],

    // The number of hours after which log entries will be deleted. Set to null to disable deletion.
    'log' => [
        'prune_after' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account
    |--------------------------------------------------------------------------
    */
    'service_accounts' => [
        '###WRITE_YOUR_OWN_NAME_HERE###' => [
            'project_id' => "###REPLACE_WITH_YOUR_PROJECT_ID###",
            'private_key' => "###REPLACE_WITH_YOUR_PRIVATE_KEY###",
            'client_email' => "###REPLACE_WITH_YOUR_CLIENT_EMAIL###"
        ],
    ]
];
