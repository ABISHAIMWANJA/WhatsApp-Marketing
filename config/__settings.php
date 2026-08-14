<?php

return [

    /* Configuration setting data-types id
    ------------------------------------------------------------------------- */
    'datatypes' => [
        'string' => 1,
        'bool' => 2,
        'int' => 3,
        'json' => 4,
    ],
    /* Auto load exceptions
    Don't load these items automatically
    ------------------------------------------------------------------------- */
    'autoload_exceptions' => [
        'subscription_plans',
        'translation_languages',
        'privacy_policy',
        'vendor_terms',
        'user_terms',
        'welcome_email_content',
    ],

    /* Configuration Setting Items
    ------------------------------------------------------------------------- */
    'items' => [
        'general' => [
            'logo_name' => [
                'key' => 'logo_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => 'logo.svg',
                'ignore_empty' => true,
            ],
            'small_logo_name' => [
                'key' => 'small_logo_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => '',
                'ignore_empty' => true,
            ],
            'favicon_name' => [
                'key' => 'favicon_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => 'favicon.png',
                'ignore_empty' => true,
            ],
            'dark_theme_logo_name' => [
                'key' => 'dark_theme_logo_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => 'logo.svg',
                'ignore_empty' => true,
            ],
            'dark_theme_small_logo_name' => [
                'key' => 'dark_theme_small_logo_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => '',
                'ignore_empty' => true,
            ],
            'dark_theme_favicon_name' => [
                'key' => 'dark_theme_favicon_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => 'favicon.png',
                'ignore_empty' => true,
            ],
            'name' => [
                'key' => 'name',
                'data_type' => 1,    // string,
                'placeholder' => 'Your Website Name',
                'default' => 'Your Company Name',
                /* 'validation_rules'   => [
                    'min:0'
                ] */
            ],
            'description' => [
                'key' => 'description',
                'data_type' => 1, // string,
                'placeholder' => 'Your Website description',
                'default' => '',
            ],
            'contact_email' => [
                'key' => 'contact_email',
                'data_type' => 1,    // string
                'placeholder' => 'your-email-address@example.com',
                'default' => 'your-contact-email@domain.com',
            ],
            'contact_details' => [
                'key' => 'contact_details',
                'data_type' => 1,    // string
                'placeholder' => __tr('place your contact details'),
                'default' => '',
                'ignore_empty' => true,
            ],
            'default_language' => [
                'key' => 'default_language',
                'data_type' => 1,    // string
                'default' => config('__tech.default_translation_language.id', 'en'),
            ],
            'timezone' => [
                'key' => 'timezone',
                'data_type' => 1,    // string
                'default' => 'UTC',
            ],
            
        ],
        'user' => [
            'enable_vendor_registration' => [
                'key' => 'enable_vendor_registration',
                'data_type' => 2,    // bool
                'default' => 1,
            ],
            'message_for_disabled_registration' => [
                'key' => 'message_for_disabled_registration',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => false, // it won't encrypt values
                'ignore_empty' => false, // it will allow blank entry
                'validation_rules' => [
                ],
            ],
            'activation_required_for_new_user' => [
                'key' => 'activation_required_for_new_user',
                'data_type' => 2,    // bool
                'default' => false,
            ],
            // welcome email
            'send_welcome_email' => [
                'key' => 'send_welcome_email',
                'data_type' => 2,    // bool
                'default' => false,
            ],
            'welcome_email_content' => [
                'key' => 'welcome_email_content',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => false, // it won't encrypt values
                'ignore_empty' => false, // it will allow blank entry
                'validation_rules' => [
                    'required_if:send_welcome_email,on',
                ],
            ],
            'disallow_disposable_emails' => [
                'key' => 'disallow_disposable_emails',
                'data_type' => 2, // boolean
                'default' => false,
            ],
            'user_terms' => [
                'key' => 'user_terms',
                'data_type' => 1,    // string
                'default' => '',
            ],
            'vendor_terms' => [
                'key' => 'vendor_terms',
                'data_type' => 1,    // string
                'default' => '',
            ],
            'privacy_policy' => [
                'key' => 'privacy_policy',
                'data_type' => 1,    // string
                'default' => '',
            ],
        ],
        'currency' => [
            'currency_format' => [
                'key' => 'currency_format',
                'data_type' => 1,    // string
                'default' => '{__currencySymbol__}{__amount__} {__currencyCode__}',
            ],
            // Currency settings
            'currency' => [
                'key' => 'currency',
                'data_type' => 1,    // string
                'default' => 'USD',
            ],
            'currency_symbol' => [
                'key' => 'currency_symbol',
                'data_type' => 1,    // string
                'default' => '&#36;',
            ],
            'currency_value' => [
                'key' => 'currency_value',
                'data_type' => 1,    // string
                'default' => 'USD',
            ],
            'round_zero_decimal_currency' => [
                'key' => 'round_zero_decimal_currency',
                'data_type' => 2, // boolean
                'default' => true, // round
            ],
        ],
        'upi_payment' => [
            // Payment method
            'enable_upi_payment' => [
                'key' => 'enable_upi_payment',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'payment_upi_address' => [
                'key' => 'payment_upi_address',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('UPI Address'),
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required_if:enable_upi_payment,on',
                    'regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+$/'
                ],
            ],
            'payment_upi_customer_notes' => [
                'key' => 'payment_upi_customer_notes',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Notes for the customers'),
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                ],
            ],
        ],
        'bank_transfer' => [
            // Payment method
            'enable_bank_transfer' => [
                'key' => 'enable_bank_transfer',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'bank_transfer_instructions' => [
                'key' => 'bank_transfer_instructions',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('UPI Address'),
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required_if:enable_upi_payment,on',
                ],
            ],
            'payment_upi_customer_notes' => [
                'key' => 'payment_upi_customer_notes',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Notes for the customers'),
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                ],
            ],
        ],
        'paypal_payment' => [
            'enable_paypal' => [
                'key' => 'enable_paypal',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'paypal_checkout_testing_publishable_key' => [
                'key' => 'paypal_checkout_testing_publishable_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Publishable key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'paypal_checkout_testing_secret_key' => [
                'key' => 'paypal_checkout_testing_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Secret Key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'paypal_checkout_live_secret_key' => [
                'key' => 'paypal_checkout_live_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => 'Live secret key',
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'paypal_checkout_live_publishable_key' => [
                'key' => 'paypal_checkout_live_publishable_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => 'Live Publishable Key',
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'use_test_paypal_checkout' => [
                'key' => 'use_test_paypal_checkout',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
        ],
        //======================================
        //Razorpay 
        'razorpay_payment' => [
            'enable_razorpay' => [
                'key' => 'enable_razorpay',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'razorpay_testing_publishable_key' => [
                'key' => 'razorpay_testing_publishable_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Publishable key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'razorpay_testing_secret_key' => [
                'key' => 'razorpay_testing_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Secret Key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'razorpay_live_secret_key' => [
                'key' => 'razorpay_live_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Live secret key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'razorpay_live_publishable_key' => [
                'key' => 'razorpay_live_publishable_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Live Publishable Key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'razorpay_live_webhook_secret'          => [
                'key'           => 'razorpay_live_webhook_secret',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => __tr('Webhook Secret'),
                'hide_value'    => true,
            ],
            'use_test_razorpay' => [
                'key' => 'use_test_razorpay',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'razorpay_testing_webhook_secret'  => [
                'key'           => 'razorpay_testing_webhook_secret',
                'data_type'     => 1,    // string
                'default'       => '',
                'placeholder'   => __tr('Webhook Secret'),
                'hide_value'    => true,
            ],
        ],
        'payment' => [
            // Payment method
            'enable_stripe' => [
                'key' => 'enable_stripe',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'stripe_enable_calculate_taxes' => [
                'key' => 'stripe_enable_calculate_taxes',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'stripe_enable_invoice_list' => [
                'key' => 'stripe_enable_invoice_list',
                'data_type' => 2,    // boolean
                'default' => true,
            ],
            'use_test_stripe' => [
                'key' => 'use_test_stripe',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'stripe_testing_secret_key' => [
                'key' => 'stripe_testing_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Secret Key'),
                'hide_value' => true,
                'ignore_empty' => true,
            ],
            'stripe_testing_publishable_key' => [
                'key' => 'stripe_testing_publishable_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Publishable key'),
                'hide_value' => true,
                'ignore_empty' => true,
            ],
            'stripe_testing_webhook_secret' => [
                'key' => 'stripe_testing_webhook_secret',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => 'Stripe Test Webhook Secret',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required'
                ],
            ],
            'stripe_live_secret_key' => [
                'key' => 'stripe_live_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => 'Live secret key',
                'hide_value' => true,
                'ignore_empty' => true,
            ],
            'stripe_live_publishable_key' => [
                'key' => 'stripe_live_publishable_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => 'Live Publishable Key',
                'hide_value' => true,
                'ignore_empty' => true,
            ],
            'stripe_live_webhook_secret' => [
                'key' => 'stripe_live_webhook_secret',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => 'Stripe Live Webhook Secret',
                'hide_value' => true,
                'validation_rules' => [
                    // 'required'
                ],
                'ignore_empty' => true,
            ],
        ],
        //paystack
           'paystack_payment' => [
                // Payment method
                'enable_paystack' => [
                    'key' => 'enable_paystack',
                    'data_type' => 2,    // boolean
                    'default' => false,
                ],
                'paystack_checkout_testing_publishable_key' => [
                    'key' => 'paystack_checkout_testing_publishable_key',
                    'data_type' => 1,    // string
                    'default' => '',
                    'placeholder' => __tr('Test Publishable key'),
                    'hide_value' => false,
                    'ignore_empty' => false,
                ],
                'paystack_checkout_testing_secret_key' => [
                    'key' => 'paystack_checkout_testing_secret_key',
                    'data_type' => 1,    // string
                    'default' => '',
                    'placeholder' => __tr('Test Secret Key'),
                    'hide_value' => false,
                    'ignore_empty' => false,
                ],
               
                'paystack_checkout_live_secret_key' => [
                    'key' => 'paystack_checkout_live_secret_key',
                    'data_type' => 1,    // string
                    'default' => '',
                    'placeholder' => 'Live secret key',
                    'hide_value' => false,
                    'ignore_empty' => false,
                ],
                'paystack_checkout_live_publishable_key' => [
                    'key' => 'paystack_checkout_live_publishable_key',
                    'data_type' => 1,    // string
                    'default' => '',
                    'placeholder' => 'Live Publishable Key',
                    'hide_value' => false,
                    'ignore_empty' => false,
                ],
                'use_test_paystack_checkout' => [
                    'key' => 'use_test_paystack_checkout',
                    'data_type' => 2,    // boolean
                    'default' => false,
                ],
                
        ],
      //yoo-money end
        'yoomoney_payment' => [
            'enable_yoomoney' => [
                'key' => 'enable_yoomoney',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'use_test_yoomoney' => [
                'key' => 'use_test_yoomoney',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'yoomoney_testing_shop_id' => [
                'key' => 'yoomoney_testing_shop_id',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Shop Id'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'yoomoney_testing_secret_key' => [
                'key' => 'yoomoney_testing_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Secret Key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'yoomoney_live_secret_key' => [
                'key' => 'yoomoney_live_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Live secret key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'yoomoney_live_shop_id' => [
                'key' => 'yoomoney_live_shop_id',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Live Shop Id'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'yoomoney_live_vat_id' => [
                'key' => 'yoomoney_live_vat_id',
                'data_type' => 1,    // string
                'default' => 1,
                'placeholder' => __tr('Live Vat Id'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
        ],
        // PhonePe Start
        'phonepe_payment' => [
            'enable_phonepe' => [
                'key' => 'enable_phonepe',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'use_test_phonepe' => [
                'key' => 'use_test_phonepe',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'phonepe_testing_client_id' => [
                'key' => 'phonepe_testing_client_id',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Shop Id'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'phonepe_testing_secret_key' => [
                'key' => 'phonepe_testing_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Test Secret Key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'phonepe_testing_client_version' => [
                'key' => 'phonepe_testing_client_version',
                'data_type' => 3,    // int
                'default' => null,
                'placeholder' => __tr('Client Version'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'phonepe_live_client_id' => [
                'key' => 'phonepe_live_client_id',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Live Shop Id'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'phonepe_live_secret_key' => [
                'key' => 'phonepe_live_secret_key',
                'data_type' => 1,    // string
                'default' => '',
                'placeholder' => __tr('Live secret key'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
            'phonepe_live_client_version' => [
                'key' => 'phonepe_live_client_version',
                'data_type' => 3,    // int
                'default' => null,
                'placeholder' => __tr('Client Version'),
                'hide_value' => false,
                'ignore_empty' => false,
            ],
        ],
        // PhonePe End
        'pusher' => [
            'broadcast_connection_driver' => [
                'key' => 'broadcast_connection_driver',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => 'pusher',
            ],
            'pusher_app_id' => [
                'key' => 'pusher_app_id',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            'pusher_app_key' => [
                'key' => 'pusher_app_key',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            'pusher_app_secret' => [
                'key' => 'pusher_app_secret',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            'pusher_app_cluster' => [
                'key' => 'pusher_app_cluster',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            // soketi specific
            'pusher_app_host' => [
                'key' => 'pusher_app_host',
                'data_type' => 1,     // string
                'default' => '127.0.0.1',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:broadcast_connection_driver,soketi'
                ],
            ],
            'pusher_app_port' => [
                'key' => 'pusher_app_port',
                'data_type' => 3,     // int
                'default' => 6001,
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'nullable',
                    'integer',
                    'required_if:broadcast_connection_driver,soketi'
                ],
            ],
            'pusher_app_scheme' => [
                'key' => 'pusher_app_scheme',
                'data_type' => 1,     // string
                'default' => 'https',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:broadcast_connection_driver,soketi'
                ],
            ],
            'pusher_app_use_tls' => [
                'key' => 'pusher_app_use_tls',
                'data_type' => 2,     // bool
                'default' => true,
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:broadcast_connection_driver,soketi'
                ],
            ],
            'pusher_app_encrypted' => [
                'key' => 'pusher_app_encrypted',
                'data_type' => 2,     // bool
                'default' => true,
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:broadcast_connection_driver,soketi'
                ],
            ],
        ],
        'social-login' => [
            // Social Login Settings
            'allow_facebook_login'  => [
                'key'           => 'allow_facebook_login',
                'data_type'     => 2,     // boolean
                'default'       => false,
            ],
            'facebook_client_id'    => [
                'key'           => 'facebook_client_id',
                'data_type'     => 1,    // string
                'default'       => '',
                'hide_value'    => true,
                 'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:allow_facebook_login,1'
                ],
            ],
            'facebook_client_secret' => [
                'key'           => 'facebook_client_secret',
                'data_type'     => 1,    // string
                'default'       => '',
                'hide_value'    => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:allow_facebook_login,1'
                ],
            ],
            'allow_google_login' => [
                'key'           => 'allow_google_login',
                'data_type'     => 2,     // boolean
                'default'       => false
            ],
            'google_client_id'      => [
                'key'           => 'google_client_id',
                'data_type'     => 1,    // string
                'default'       => '',
                'hide_value'    => true,
                'validation_rules' => [
                    'required_if:allow_google_login,1'
                ],
            ],
            'google_client_secret'  => [
                'key'           => 'google_client_secret',
                'data_type'     => 1,    // string
                'default'       => '',
                'hide_value'    => true,
                'validation_rules' => [
                    'required_if:allow_google_login,1'
                ],
            ],
        ],
        'integrations' => [
            'microsoft_translator_api_key' => [
                'key' => 'microsoft_translator_api_key',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
            ],
            'microsoft_translator_api_region' => [
                'key' => 'microsoft_translator_api_region',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:microsoft_translator_api_key,1',
                    'alpha_num'
               ],
            ],
            'recaptcha_site_key' => [
                'key' => 'recaptcha_site_key',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                     'required_if:enable_recaptcha,1'
                ],
            ],
            'recaptcha_secret_key' => [
                'key' => 'recaptcha_secret_key',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                     'required_if:enable_recaptcha,1'
                ],
            ],
            'enable_recaptcha' => [
                'key' => 'allow_recaptcha',
                'data_type' => 2,    // boolean
                'default' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required',
                ],
            ],
            'api_documentation_url' => [
                'key' => 'api_documentation_url',
                'data_type' => 1,     // string
                'default' => 'https://documenter.getpostman.com/view/32051566/2sBXitCSjf',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                    'url',
                ],
            ],
            'page_footer_code_all' => [
                'key' => 'page_footer_code_all',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => false, // it won't encrypt values
                'ignore_empty' => false, // it will allow blank entry
                'validation_rules' => [
                ],
            ],
            'page_footer_code_logged_user_only' => [
                'key' => 'page_footer_code_logged_user_only',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => false, // it won't encrypt values
                'ignore_empty' => false, // it will allow blank entry
                'validation_rules' => [
                ],
            ],
        ],
        'misc_settings' => [
            'current_home_page_view' => [
                'key' => 'current_home_page_view',
                'data_type' => 1,     // string
                'default' => 'outer-home-2',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required',
                ],
            ],
            'current_home_page_view' => [
                'key' => 'current_home_page_view',
                'data_type' => 1,     // string
                'default' => 'outer-home-3',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required',
                ],
            ],
            'other_home_page_url' => [
                'key' => 'other_home_page_url',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'nullable',
                    'url',
                ],
            ],
            'cron_process_messages_per_lot' => [
                'key' => 'cron_process_messages_per_lot',
                'data_type' => 3,     // int
                'default' => 35,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required',
                    'numeric',
                    'min:1',
                ],
            ],
            'enable_requeue_healthy_error_msg' => [
                'key' => 'enable_requeue_healthy_error_msg',
                'data_type' => 2,     // bool
                'default' => true,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                ],
            ],
            'enable_queue_jobs_for_campaigns' => [
                'key' => 'enable_queue_jobs_for_campaigns',
                'data_type' => 2,     // bool
                'default' => false,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    // 'required',
                ],
            ],
            'enable_wa_webhook_process_using_db' => [
                'key' => 'enable_wa_webhook_process_using_db',
                'data_type' => 2,     // bool
                'default' => false,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    // 'required',
                ],
            ],
            'contacts_import_limit_per_request' => [
                'key' => 'contacts_import_limit_per_request',
                'data_type' => 3,     // int
                'default' => 5000,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required',
                    'numeric',
                    'min:1',
                ],
            ],
            'current_app_theme' => [
                'key' => 'current_app_theme',
                'data_type' => 1,     // string
                'default' => 'light',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    // 'required',
                ],
            ],
            'allow_to_change_theme' => [
                'key' => 'allow_to_change_theme',
                'data_type' => 2,    // bool
                'default' => true,
            ],
            'disable_bg_image' => [
                'key' => 'disable_bg_image',
                'data_type' => 2,    // bool
                'default' => false,
            ],
            'page_head_code' => [
                'key' => 'page_head_code',
                'data_type' => 1,     // string
                'default' => '',
                'hide_value' => false, // it won't encrypt values
                'ignore_empty' => false, // it will allow blank entry
                'validation_rules' => [
                ],
            ],
            'enable_automatic_message_deletion' => [
                'key' => 'enable_automatic_message_deletion',
                'data_type' => 2,     // bool
                'default' => 0,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [],
            ],
            'delete_whatsapp_message_days' => [
                'key' => 'delete_whatsapp_message_days',
                'data_type' => 1,     // string
                'default' => '90',
                'hide_value' => false, // it won't encrypt values
                'ignore_empty' => false, // it will allow blank entry
                'validation_rules' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:99999'
                ],
            ],
            'enable_automatic_delete_vendor_temp_media' => [
                'key' => 'enable_automatic_delete_vendor_temp_media',
                'data_type' => 2,     // bool
                'default' => 0,
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [],
            ],
        ],
        'language-settings' => [
            'translation_languages' => [
                'key' => 'translation_languages',
                'data_type' => 4,    // string
                'default' => '',
            ],
        ],
        'email' => [
            'use_env_default_email_settings' => [
                'key' => 'use_env_default_email_settings',
                'data_type' => 2,    // boolean
                'placeholder' => '',
                'default' => true,
            ],
            'mail_driver' => [
                'key' => 'mail_driver',
                'data_type' => 1,    // integer
                'placeholder' => '',
                'default' => 'smtp',
            ],
            'mail_from_address' => [
                'key' => 'mail_from_address',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'mail_from_name' => [
                'key' => 'mail_from_name',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'smtp_mail_port' => [
                'key' => 'smtp_mail_port',
                'data_type' => 3,    // integer
                'placeholder' => '',
                'default' => null,
            ],
            'smtp_mail_host' => [
                'key' => 'smtp_mail_host',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'smtp_mail_username' => [
                'key' => 'smtp_mail_username',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'smtp_mail_encryption' => [
                'key' => 'smtp_mail_encryption',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'smtp_mail_password_or_apikey' => [
                'key' => 'smtp_mail_password_or_apikey',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'sparkpost_mail_password_or_apikey' => [
                'key' => 'sparkpost_mail_password_or_apikey',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'mailgun_mail_password_or_apikey' => [
                'key' => 'mailgun_mail_password_or_apikey',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'mailgun_domain' => [
                'key' => 'mailgun_domain',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
            'mailgun_endpoint' => [
                'key' => 'mailgun_endpoint',
                'data_type' => 1,    // string
                'placeholder' => '',
                'default' => '',
            ],
        ],
        'product_registration' => [
            'product_registration' => [
                'key' => 'product_registration',
                'data_type' => 4, // json
                'hide_value' => true,
                'default' => [
                    'registration_id' => 'nullnull',
                    'email' => 'mail@mail.com',
                    'registered_at' => '11.04.2026',
                    'licence' => 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9',
                    'signature' => sha1(array_get($_SERVER, 'HTTP_HOST', '') . 'nullnull' . '4.5+'),
                    'sold_at' => '11.04.2026',
                ],
            ],
        ],
        'subscription_plans' => [
            'subscription_plans' => [
                'key' => 'subscription_plans',
                'data_type' => 4, // json
                'default' => [],
            ],
        ],
        'manual_whatsapp_onboarding' => [
            'enable_whatsapp_manual_signup' => [
                'key' => 'enable_whatsapp_manual_signup',
                'data_type' => 2,    // boolean
                'default' => true,
            ],
        ],
        'whatsapp_onboarding' => [
            'enable_embedded_signup' => [
                'key' => 'enable_embedded_signup',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'embedded_signup_app_id' => [
                'key' => 'embedded_signup_app_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'nullable',
                    'numeric',
                    'required_if:enable_embedded_signup,on',
                ],
            ],
            'embedded_signup_app_secret' => [
                'key' => 'embedded_signup_app_secret',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'nullable',
                    'alpha_num',
                    'required_if:enable_embedded_signup,on',
                ],
            ],
            'embedded_signup_config_id' => [
                'key' => 'embedded_signup_config_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'nullable',
                    'numeric',
                    'required_if:enable_embedded_signup,on',
                ],
            ],
            'enable_business_app_onboarding' => [
                'key' => 'enable_business_app_onboarding',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
        ],
        'application_styles_and_colors' => [
           
            // colors
            'app_bg_color' => [
                'key'           => 'app_bg_color',
                'title' => __tr('App BG Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#f8f6f3',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_sidebar_bg_color' => [
                'key'           => 'app_sidebar_bg_color',
                'title' => __tr('Sidebar BG Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#ffffff',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_sidebar_text_color' => [
                'key'           => 'app_sidebar_text_color',
                'title' => __tr('Sidebar Text Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#212528',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_primary' => [
                'key'           => 'app_bs_color_primary',
                'title' => __tr('Primary Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#09bb9c',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_default' => [
                'key'           => 'app_bs_color_default',
                'title' => __tr('Default Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#172b4d',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_secondary' => [
                'key'           => 'app_bs_color_secondary',
                'title' => __tr('Secondary Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#6c757d',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_danger' => [
                'key'           => 'app_bs_color_danger',
                'title' => __tr('Danger Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#f5365c',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_light' => [
                'key'           => 'app_bs_color_light',
                'title' => __tr('Light Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#adb5bd',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_dark' => [
                'key'           => 'app_bs_color_dark',
                'title' => __tr('Dark Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#212528',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_warning' => [
                'key'           => 'app_bs_color_warning',
                'title' => __tr('Warning Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#fb6340',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_success' => [
                'key'           => 'app_bs_color_success',
                'title' => __tr('Success Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#2dce89',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'app_bs_color_muted' => [
                'key'           => 'app_bs_color_muted',
                'title' => __tr('Muted Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#8898aa',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
        ],
        'application_dark_theme_styles_and_colors' => [
            // dark theme colors -------------------------------------------------
            'dark_theme_app_bg_color' => [
                'key'           => 'dark_theme_app_bg_color',
                'title' => __tr('App BG Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#1E1E25',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_sidebar_bg_color' => [
                'key'           => 'dark_theme_app_sidebar_bg_color',
                'title' => __tr('Sidebar BG Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#15151a',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_sidebar_text_color' => [
                'key'           => 'dark_theme_app_sidebar_text_color',
                'title' => __tr('Sidebar Text Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#FFFFFF',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_primary' => [
                'key'           => 'dark_theme_app_bs_color_primary',
                'title' => __tr('Primary Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#1e8d7a',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_default' => [
                'key'           => 'dark_theme_app_bs_color_default',
                'title' => __tr('Default Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#3f4045 ',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_secondary' => [
                'key'           => 'dark_theme_app_bs_color_secondary',
                'title' => __tr('Secondary Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#57616b',//'#1E1E25',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_danger' => [
                'key'           => 'dark_theme_app_bs_color_danger',
                'title' => __tr('Danger Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#c11616',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_light' => [
                'key'           => 'dark_theme_app_bs_color_light',
                'title' => __tr('Light Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#696868',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_dark' => [
                'key'           => 'dark_theme_app_bs_color_dark',
                'title' => __tr('Dark Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#9a9daf',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_warning' => [
                'key'           => 'dark_theme_app_bs_color_warning',
                'title' => __tr('Warning Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#d99952',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_success' => [
                'key'           => 'dark_theme_app_bs_color_success',
                'title' => __tr('Success Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#2dce89',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
            'dark_theme_app_bs_color_muted' => [
                'key'           => 'dark_theme_app_bs_color_muted',
                'title' => __tr('Muted Color'),
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => '#c3c3c3',
                'validation_rules'   => [
                    'required',
                    'size:7',
                    'starts_with:#'
                ]
            ],
        ],
        'internals' => [
            // non interface based settings info, INTERNAL USE ONLY
            'cron_setup_done_at' => [
                'key'           => 'cron_setup_done_at',
                'data_type'     => 1,    // string
                'placeholder'   => '',
                'default'       => false,
                'ignore_empty' => false
            ],
            'cron_setup_using_artisan_at' => [
                'key'           => 'cron_setup_using_artisan_at',
                'data_type'     => 1,    // string
                'placeholder'   => '',
                'default'       => false,
                'ignore_empty' => false
            ],
            'queue_setup_done_at' => [
                'key'           => 'queue_setup_done_at',
                'data_type'     => 1,    // string
                'placeholder'   => '',
                'default'       => false,
                'ignore_empty' => false
            ],
            'queue_setup_using_artisan_at' => [
                'key'           => 'queue_setup_using_artisan_at',
                'data_type'     => 1,    // string
                'placeholder'   => '',
                'default'       => false,
                'ignore_empty' => false
            ],
            'payment_gateway_info' => [
                'key'           => 'payment_gateway_info',
                'data_type'     => 4,    // json,
                'placeholder'   => '',
                'default'       => '',
                'ignore_empty' => true
            ],
        ]
    ],
];
