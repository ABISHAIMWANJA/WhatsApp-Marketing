<?php

return [

    /* Configuration setting data-types id
    ------------------------------------------------------------------------- */
    'datatypes' => [
        'string' => 1,
        'bool' => 2,
        'int' => 3,
        'json' => 4,
        'float' => 6,
    ],
    /* Auto load exceptions
    Don't load these items automatically
    ------------------------------------------------------------------------- */
    'autoload_exceptions' => [
        'open_ai_input_training_data',
        'open_ai_embedded_training_data',
        'whatsapp_health_status_data',
        'whatsapp_phone_numbers',
        'whatsapp_onboarding_raw_data',
        'contacts_import_process_data',
        'contact_advance_filter_data',
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
            ],
            'favicon_name' => [
                'key' => 'favicon_name',
                'data_type' => 1,    // string,
                'placeholder' => '',
                'default' => 'favicon.ico',
            ],
            'name' => [
                'key' => 'name',
                'data_type' => 1,    // string,
                'placeholder' => __tr('Your Business Name'),
                'default' => 'business name',
                'validation_rules' => [
                    'required',
                ],
            ],
            'vendor_slug' => [
                'key' => 'vendor_slug',
                'data_type' => 1,    // string,
                'placeholder' => 'name in the url',
                'default' => uniqid(),
            ],
            'contact_email' => [
                'key' => 'contact_email',
                'data_type' => 1,    // string
                'placeholder' => 'your-email-address@example.com',
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
            ],
            'contact_phone' => [
                'key' => 'contact_phone',
                'data_type' => 1,    // string
                'placeholder' => __tr('your business phone number'),
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
            ],
            'address' => [
                'key' => 'address',
                'data_type' => 1,    // string
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
            ],
            'postal_code' => [
                'key' => 'postal_code',
                'data_type' => 1,    // string
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
            ],
            'city' => [
                'key' => 'city',
                'data_type' => 1,    // string
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
            ],
            'state' => [
                'key' => 'state',
                'data_type' => 1,    // string
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
            ],
            'country' => [
                'key' => 'country',
                'data_type' => 3,    // int
                'default' => '',
                'validation_rules' => [
                    'required',
                ],
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
                'validation_rules' => [
                    'required',
                ],
            ],
        ],
        'bot_timing_settings' => [
            'enable_bot_timing_restrictions' => [
                'key' => 'enable_bot_timing_restrictions',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'bot_start_timing' => [
                'key' => 'bot_start_timing',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'date_format:H:i',
                   'required_if:enable_bot_timing_restrictions,on',
                ],
            ],
            'bot_end_timing' => [
                'key' => 'bot_end_timing',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'date_format:H:i',
                   'required_if:enable_bot_timing_restrictions,on',
                //    'after:bot_start_timing'
                ],
            ],
            'bot_timing_timezone' => [
                'key' => 'bot_timing_timezone',
                'data_type' => 1,    // string
                'default' => 'UTC',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                   'required_if:enable_bot_timing_restrictions,on',
                   'timezone:all'
                ],
            ],
            // ai timezone
            'enable_ai_bot_timing_restrictions' => [
                'key' => 'enable_ai_bot_timing_restrictions',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'enable_selected_other_bot_timing_restrictions' => [
                'key' => 'enable_selected_other_bot_timing_restrictions',
                'data_type' => 4,    // json
                'default' => [],
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                //    'required',
                ],
            ],
        ],
        'ai_bot_settings' => [
            // key as for flowise but its for all type of ai bots
            'default_enable_flowise_ai_bot_for_users' => [
                'key' => 'default_enable_flowise_ai_bot_for_users',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'flowise_failed_message' => [
                'key' => 'flowise_failed_message',
                'data_type' => 1,    // string
                'default' => 'Sorry but something went wrong while processing your request by our AI.',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                //    'required',
                ],
            ],
        ],
        'flowise_ai_bot_setup' => [
            'enable_flowise_ai_bot' => [
                'key' => 'enable_flowise_ai_bot',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'flowise_url' => [
                'key' => 'flowise_url',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:enable_flowise_ai_bot,on',
                    'nullable',
                    'url',
                ],
            ],
            'flowise_access_token' => [
                'key' => 'flowise_access_token',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                //    'alpha_dash',
                   'nullable',
                ],
            ],
        ],
        'open_ai_bot_setup' => [
            'enable_open_ai_bot' => [
                'key' => 'enable_open_ai_bot',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'use_existing_chat_history' => [
                'key' => 'use_existing_chat_history',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'open_ai_access_key' => [
                'key' => 'open_ai_access_key',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                   'alpha_dash',
                   'required',
                // 'required_if:enable_open_ai_bot,on',
                ],
            ],
            'open_ai_bot_name' => [
                'key' => 'open_ai_bot_name',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                //    'alpha_dash',
                //    'required',
                // 'required_if:enable_open_ai_bot,on',
                ],
            ],
            'open_ai_organization_id' => [
                'key' => 'open_ai_organization_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                   'alpha_dash',
                //    'nullable',
                   'required',
                //    'starts_with:org-',
                // 'required_if:enable_open_ai_bot,1',
                ],
            ],
            'open_ai_bot_data_source_type' => [
                'key' => 'open_ai_bot_data_source_type',
                'data_type' => 1,    // string
                'default' => 'text',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:enable_open_ai_bot,on',
                ],
            ],
            'open_ai_assistant_id' => [
                'key' => 'open_ai_assistant_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                   'alpha_dash',
                   'nullable',
                   'sometimes',
                    'required_if:open_ai_bot_data_source_type,assistant',
                    // 'starts_with:asst_',
                ],
            ],
            'open_ai_max_token' => [
                'key' => 'open_ai_max_token',
                'data_type' => 3,    // int
                'default' => 300,
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                   'numeric',
                   'nullable',
                // 'required_if:enable_open_ai_bot,1',
                ],
            ],
            'open_ai_model_key' => [
                'key' => 'open_ai_model_key',
                'data_type' => 1,    // string
                'default' => 'gpt-4o-mini',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'regex:/^[a-zA-Z0-9\-.]+$/',
                   'required',
                ],
            ],
            'open_ai_input_training_data' => [
                'key' => 'open_ai_input_training_data',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                    'required_if:open_ai_bot_data_source_type,text',
                //    'required',
                //    'nullable',
                ],
            ],
            'open_ai_embedded_training_data' => [
                'key' => 'open_ai_embedded_training_data',
                'data_type' => 4,    // json
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                //    'required',
                   'nullable',
                ],
            ],
        ],
        /**
         * WhatsApp Cloud API setup
         */
        'whatsapp_cloud_api_setup' => [
            'facebook_app_id' => [
                'key' => 'facebook_app_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                    'numeric',
                ],
            ],
            'facebook_app_secret' => [
                'key' => 'facebook_app_secret',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                    'alpha_num',
                ],
            ],
            'whatsapp_access_token' => [
                'key' => 'whatsapp_access_token',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            'whatsapp_business_account_id' => [
                'key' => 'whatsapp_business_account_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                    'numeric',
                ],
            ],
            'whatsapp_phone_numbers' => [
                'key' => 'whatsapp_phone_numbers',
                'data_type' => 4,    // json
                'default' => [],
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required',
                    // 'numeric',
                ],
            ],
            'current_phone_number_number' => [
                'key' => 'current_phone_number_number',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required',
                    // 'numeric',
                    // 'doesnt_start_with:+',
                ],
            ],
            'current_phone_number_id' => [
                'key' => 'current_phone_number_id',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required',
                    // 'numeric',
                ],
            ],
            'marketing_messages_onboarding_status' => [
                'key' => 'marketing_messages_onboarding_status',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required',
                    // 'numeric',
                ],
            ],
            'template_analytics_status' => [
                'key' => 'template_analytics_status',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                    // 'required',
                    // 'numeric',
                ],
            ],
            'webhook_verified_at' => [
                'key' => 'webhook_verified_at',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            'webhook_messages_field_verified_at' => [
                'key' => 'webhook_messages_field_verified_at',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                ],
            ],
            // internal uses
            'whatsapp_onboarding_raw_data' => [
                'key' => 'whatsapp_onboarding_raw_data',
                'data_type' => 4,    // json
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                ],
            ],
            // internal uses
            'whatsapp_phone_numbers_data' => [
                'key' => 'whatsapp_phone_numbers_data',
                'data_type' => 4,    // json
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                ],
            ],
            'whatsapp_token_info_data' => [
                'key' => 'whatsapp_token_info_data',
                'data_type' => 4,    // json
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                ],
            ],
            'embedded_setup_done_at' => [
                'key'           => 'embedded_setup_done_at',
                'data_type'     => 1,    // string,
                'placeholder'   => '',
                'default'       => false,
                'ignore_empty' => true,
            ],
            'test_recipient_contact' => [
                'key' => 'test_recipient_contact',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required',
                    'numeric',
                ],
            ],
            'enable_whatsapp_calling' => [
                'key' => 'enable_whatsapp_calling',
                'data_type' => 2,    // boolean
                'default' => false,
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                ],
            ],
        ],
        'language-settings' => [
            'translation_languages' => [
                'key' => 'translation_languages',
                'data_type' => 4,    // string
                'default' => '',
            ],
        ],
        'vendor_webhook' => [
            // webhook
            'enable_vendor_webhook' => [
                'key' => 'enable_vendor_webhook',
                'data_type' => 2,    // boolean
                'default' => false,
            ],
            'vendor_webhook_endpoint' => [
                'key' => 'vendor_webhook_endpoint',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => true,
                'validation_rules' => [
                    'required_if:enable_vendor_webhook,on',
                    'nullable',
                    'url'
                ],
            ],
        ],
        'internals' => [
            // non interface based settings info, INTERNAL USE ONLY
            'whatsapp_access_token_expired' => [
                'key'           => 'whatsapp_access_token_expired',
                'data_type'     => 2,    // bool,
                'placeholder'   => '',
                'default'       => false,
                'ignore_empty' => false
            ],
            'whatsapp_health_status_data' => [
                'key'           => 'whatsapp_health_status_data',
                'data_type'     => 4,    // json,
                'placeholder'   => '',
                'default'       => '',
                'ignore_empty' => true
            ],
            'is_disabled_message_sound_notification' => [
                'key' => 'is_disabled_message_sound_notification',
                'data_type' => 2,    // bool
                'placeholder' => '',
                'default' => '',
            ],
            'vendor_api_access_token' => [
                'key' => 'vendor_api_access_token',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => true,
                'ignore_empty' => true,
                'validation_rules' => [
                ],
            ],
            'contacts_import_process_data' => [
                'key'           => 'contacts_import_process_data',
                'data_type'     => 4,    // json,
                'placeholder'   => '',
                'default'       => '',
                'ignore_empty' => false
            ],
            'whatsapp_display_name' => [
                'key' => 'whatsapp_display_name',
                'data_type' => 1,    // string
                'default' => '',
                'hide_value' => false,
                'ignore_empty' => false,
                'validation_rules' => [
                ],
            ],
            'contact_advance_filter_data' => [
                'key'           => 'contact_advance_filter_data',
                'data_type'     => 4,    // json,
                'placeholder'   => '',
                'default'       => '',
                'ignore_empty' => false
            ],
        ],
    ],
];
