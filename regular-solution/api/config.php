<?php
/**
 * Regular Solution — API configuration
 *
 * HOW TO GO LIVE WITH REAL SMS
 * ----------------------------
 * 1. Pick a provider below and set 'sms_provider' to its key
 *    ('msg91', 'fast2sms' or 'twilio').
 * 2. Fill in that provider's credentials.
 * 3. Leave 'sms_provider' => 'log' while testing: the code is written to
 *    api/storage/otp.log and echoed back to the browser so you can complete
 *    the flow without spending SMS credits.
 *
 * Nothing else in the site needs to change when you switch providers.
 */

return [

    // OTP SWITCH — false = forms are accepted without phone verification.
    // Set to true once SMS OTP is live (also set OTP_ENABLED = true in js/script.js).
    'otp_enabled' => false,

    // 'log' | 'msg91' | 'fast2sms' | 'twilio'
    'sms_provider' => 'log',

    'otp' => [
        'length'          => 6,
        'ttl_seconds'     => 300,  // code is valid for 5 minutes
        'max_attempts'    => 5,    // wrong guesses before the code is burned
        'resend_cooldown' => 30,   // seconds a visitor must wait between sends
        'max_per_hour'    => 6,    // sends allowed per number per hour
    ],

    // Indian mobile numbers: 10 digits starting 6-9
    'country_code' => '91',

    // --- Providers ---------------------------------------------------------

    'msg91' => [
        'auth_key'    => '',        // MSG91 auth key
        'template_id' => '',        // DLT-approved template id
        'sender_id'   => 'RSOLTN',
    ],

    'fast2sms' => [
        'api_key'   => '',
        'sender_id' => 'FSTSMS',
        'route'     => 'otp',
    ],

    'twilio' => [
        'sid'   => '',
        'token' => '',
        'from'  => '',              // e.g. +15551234567
    ],

    // --- Where booking requests go ----------------------------------------

    // Every submission is always appended to api/storage/submissions.csv.
    // Set an address here to also receive it by email (needs a working mail
    // setup on the server; XAMPP does not send mail out of the box).
    'notify_email' => 'ms.balamurugan2633@gmail.com',

    'site_name' => 'Regular Solution',

    // --- Admin panel (/admin/) --------------------------------------------

    // Used ONLY to create the admin account the first time the panel runs.
    // After that the password lives (hashed) in api/storage/site.sqlite and
    // is changed from Admin > Change Password — editing it here does nothing.
    'admin' => [
        'username' => 'admin',
        'password' => 'Admin@123',
    ],

    // Wrong passwords allowed per IP within 15 minutes before login locks.
    'admin_max_attempts' => 5,

    // Sign the admin out after this many minutes without activity.
    'admin_idle_minutes' => 60,
];
