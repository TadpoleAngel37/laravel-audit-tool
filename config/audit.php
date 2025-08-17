<?php

return [

    // projects you want to audit
    'projects' => [
        // put the paths to your Laravel projects here
        // these should be absolute paths
        // e.g., '/var/www/my-laravel-project'
        '',
    ],
    
    // email address to send the report to
    'mail' => [
        'to' => env('AUDIT_REPORT_TO', ''), //set in .env
        'subject' => env('AUDIT_REPORT_SUBJECT', 'Laravel Security Audit Report'), //set in .env
    ],

    // how to call composer
    'composer_bin' => env('AUDIT_COMPOSER_BIN', 'composer'),

    // timeout
    'timeout' => 120, // in seconds
];