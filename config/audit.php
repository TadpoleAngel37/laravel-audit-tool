<?php

return [

    // projects you want to audit
    'projects' => [
        //filler
        '/var/www/site-a',
        '/var/www/site-b',
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