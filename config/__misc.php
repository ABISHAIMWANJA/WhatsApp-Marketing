<?php
// items which may have base values from .env as __tech etc gets assigned after translation ready
return [
    // force url to use https
    'force_https' => env('FORCE_HTTPS', false),
    /* Email Config
    ------------------------------------------------------------------------- */
    'mail_from' => [
        env('MAIL_FROM_ADD', 'your@domain.com'),
        env('MAIL_FROM_NAME', 'E-Mail Service'),
    ],
    // development mode
    'ngrok_url' => env('NGROK_URL', ''),
    // demos
    'demo_protected_bots' => env('DEMO_PROTECTED_BOTS', ''),
    'demo_test_recipient_contact_number' => env('DEMO_TEST_RECIPIENT_CONTACT_NUMBER', ''),
    'demo_template_uid' =>  env('DEMO_TEMPLATE_UID', 0),
    'storage_base_folder' =>  env('STORAGE_BASE_FOLDER', ''),
    'lw_internal_debug' => env('LW_INTERNAL_DEBUG', false)
];