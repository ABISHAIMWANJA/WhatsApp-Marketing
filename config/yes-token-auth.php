<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Yes Token Auth
    |--------------------------------------------------------------------------
    |
    | Manage your token auth configurations
    |
    */
    'refresh_after' => 60 * 50, // 5 mins
    'expiration' => 60 * 60 * 3 * 50, // 3 hours
    'refresh_after_for_mobile_app' => 24 * 60 * 60 * 7, // 5 mins
    'expiration_for_mobile_app' => 24 * 60 * 60 * 10, // 3 hours
    'verify_user_agent' => true, // whatever to cross check user agent
    'verify_ip_address' => false, // whatever to cross check ip address
    'token_registry' => [
        'enabled' => false,
        'schema' => [
            'jti' => '_uid',
            'jwt_token' => 'jwt_token',
            'uaid' => 'user_authorities__id',
            'ip_address' => 'ip_address',
            'expiry_at' => 'expiry_at',
        ],
    ],
    'routes_via_url' => [],
    'routes_via_input' => [],
];
