<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */


/**
 * ConfigurationRequest.php - Request file
 *
 * This file is part of the Configuration component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Configuration\Requests;

use App\Yantrana\Base\BaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ConfigurationRequest extends BaseRequest
{
        /**
     * Loosely sanitize fields.
     *------------------------------------------------------------------------ */
    protected $looseSanitizationFields = [
        'user_terms' => '',
        'vendor_terms' => '',
        'privacy_policy' => '',
        'page_footer_code_all' => '<script></script>',
        'page_footer_code_logged_user_only' => '<script></script>',
        'message_for_disabled_registration' => '',
        'welcome_email_content' => '',
        'page_head_code'=> '<script></script>',
    ];
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     *-----------------------------------------------------------------------*/
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the user register request.
     *
     * @return bool
     *-----------------------------------------------------------------------*/
    public function rules()
    {
        $requestData = new Request;
        $inputData = $this->all();

        $pageType = request()->pageType;
        if(request()->registration_id and request()->licence_type) {
            $pageType = 'product_registration';
        }
        $rules = [];
        // check validation via page type
        switch ($pageType) {
            case 'general':
                $rules = [
                    'name' => 'required',
                    'contact_email' => 'required|email',
                ];
                break;

            case 'user':
                $rules = [
                    'activation_required_for_new_user' => 'required',
                    'user_photo_restriction' => 'integer|min:0',
                ];
                break;

            case 'credit-package':
                $formType = request()->form_type;
                if ($formType == 'currency_form') {
                    $rules = [
                        'currency' => 'required',
                        'currency_symbol' => 'required',
                        'currency_value' => 'required',
                        'round_zero_decimal_currency' => 'required',
                    ];
                } else {
                    $uid = $inputData['uid'];
                    foreach ($inputData['credit_packages']['package_data'][$uid] as $key => $value) {
                        $rules['credit_packages.package_data.'.$uid.'.'.$key] = 'required';
                    }
                }

                break;

            case 'payment':
                $enableStripe = Arr::get($inputData, 'enable_stripe');

                // check if stripe payment enable
                if ($enableStripe) {
                    $stripeTestMode = $inputData['use_test_stripe'];
                    // Check if stripe test mode is enable
                    if ($stripeTestMode and ! $inputData['stripe_test_keys_exist']) {
                        $rules = [
                            'stripe_testing_secret_key' => 'required',
                            'stripe_testing_publishable_key' => 'required',
                        ];
                    } elseif (! $stripeTestMode and ! array_get($inputData, 'stripe_live_keys_exist')) {
                        $rules = [
                            'stripe_live_secret_key' => 'required',
                            'stripe_live_publishable_key' => 'required',
                        ];
                    }
                }
                break;
            case 'paypal_payment':
                $enablePaypal = Arr::get($inputData, 'enable_paypal');

                // check if paypal payment enable
                if ($enablePaypal) {
                    $paypalTestMode = $inputData['use_test_paypal_checkout'];
                    // Check if paypal test mode is enable
                    if ($paypalTestMode and ! $inputData['paypal_test_keys_exist']) {
                        $rules = [
                            'paypal_checkout_testing_secret_key' => 'required',
                            'paypal_checkout_testing_publishable_key' => 'required',
                        ];
                    } elseif (! $paypalTestMode and ! array_get($inputData, 'paypal_live_keys_exist')) {
                        $rules = [
                            'paypal_checkout_live_secret_key' => 'required',
                            'paypal_checkout_live_publishable_key' => 'required',
                        ];
                    }
                }
                break;
            case 'razorpay_payment':
                $enableRazorpay = Arr::get($inputData, 'enable_razorpay');
                $enableRazorpaySubscription = Arr::get($inputData, 'enable_razorpay_subscription');

                // check if paypal payment enable
                if ($enableRazorpay or $enableRazorpaySubscription) {
                    $razorpayTestMode = $inputData['use_test_razorpay'];
                    // Check if paypal test mode is enable
                    if ($razorpayTestMode and ! $inputData['razorpay_test_keys_exist']) {
                        $rules = [
                            'razorpay_testing_secret_key' => 'required',
                            'razorpay_testing_publishable_key' => 'required',
                        ];
                    } elseif (! $razorpayTestMode and ! array_get($inputData, 'razorpay_live_keys_exist')) {
                        $rules = [
                            'razorpay_live_secret_key' => 'required',
                            'razorpay_live_publishable_key' => 'required',
                        ];
                    }
                }
                break;
            case 'paystack_payment':
                $enablePaypal = Arr::get($inputData, 'enable_paystack');

                // check if paypal payment enable
                if ($enablePaypal) {
                    $paypalTestMode = $inputData['use_test_paystack_checkout'];
                    // Check if paypal test mode is enable
                    if ($paypalTestMode and ! $inputData['paystack_test_keys_exist']) {
                        $rules = [
                            'paystack_checkout_testing_secret_key' => 'required',
                            'paystack_checkout_testing_publishable_key' => 'required',
                        ];
                    } elseif (! $paypalTestMode and ! array_get($inputData, 'paystack_live_keys_exist')) {
                        $rules = [
                            'paystack_checkout_live_secret_key' => 'required',
                            'paystack_checkout_live_publishable_key' => 'required',
                        ];
                    }
                }
                break;
                case 'yoomoney_payment':
                    $enablePaypal = Arr::get($inputData, 'enable_yoomoney');
    
                    // check if paypal payment enable
                    if ($enablePaypal) {
                        $paypalTestMode = $inputData['use_test_yoomoney'];
                        // Check if paypal test mode is enable
                        if ($paypalTestMode and ! $inputData['yoomoney_test_keys_exist']) {
                            $rules = [
                                'yoomoney_testing_shop_id' => 'required',
                                'yoomoney_testing_secret_key' => 'required',
                            ];
                        } elseif (! $paypalTestMode and ! array_get($inputData, 'yoomoney_live_keys_exist')) {
                            $rules = [
                                'yoomoney_live_shop_id' => 'required',
                                'yoomoney_live_secret_key' => 'required',
                                'yoomoney_live_vat_id' => 'required'
                                
                            ];
                        }
                    }
                    break;
                    case 'phonepe_payment':
                        $enablePhonePe = Arr::get($inputData, 'enable_phonepe');
        
                        // check if phonepe payment enable
                        if ($enablePhonePe) {
                            $phonePeTestMode = $inputData['use_test_phonepe'];
                            // Check if phonepe test mode is enable
                            if ($phonePeTestMode and ! $inputData['phonepe_test_keys_exist']) {
                                $rules = [
                                    'phonepe_testing_client_id' => 'required',
                                    'phonepe_testing_secret_key' => 'required',
                                    'phonepe_testing_client_version' => 'required'
                                ];
                            } elseif (!$phonePeTestMode and !array_get($inputData, 'phonepe_live_keys_exist')) {
                                $rules = [
                                    'phonepe_live_client_id' => 'required',
                                    'phonepe_live_secret_key' => 'required',
                                    'phonepe_live_client_version' => 'required'
                                    
                                ];
                            }
                        }
                    break;
            case 'email':

                //driver specific rules
                $driverRules = [];

                if ($inputData['use_env_default_email_settings'] != '1') {

                    //default rules
                    $rules = [
                        'mail_from_address' => 'required|email',
                        'mail_from_name' => 'required',
                        'mail_driver' => 'required',
                    ];

                    //for driver specific rules
                    switch ($inputData['mail_driver']) {

                        case 'smtp':
                            $driverRules = [
                                'smtp_mail_host' => 'required',
                                'smtp_mail_port' => 'required',
                                'smtp_mail_encryption' => 'required',
                                'smtp_mail_username' => 'required',
                                'smtp_mail_password_or_apikey' => 'required',
                            ];
                            break;

                        case 'sparkpost':
                            $driverRules = [
                                'sparkpost_mail_password_or_apikey' => 'required',
                            ];
                            break;

                        case 'mailgun':
                            $driverRules = [
                                'mailgun_domain' => 'required',
                            ];
                            break;

                        default:
                            $driverRules = [];
                            break;
                    }
                }

                $rules = array_merge($rules, $driverRules);
                break;

            default:
                $rules = [];
                break;
        }

        foreach (config('__settings.items.'.$pageType) as $settingItemKey => $settingItemValue) {
            $settingsValidationRules = Arr::get($settingItemValue, 'validation_rules', []);
            $isValueHidden = Arr::get($settingItemValue, 'hide_value');
            if ($settingsValidationRules) {
                // skip validation if hidden value item and empty and the value is already set
                if(!array_key_exists($settingItemKey, $inputData) or ($isValueHidden and empty(Arr::get($inputData, $settingItemKey)) and getAppSettings($settingItemKey))) {
                    continue;
                }
                $existingItemRules = Arr::get($rules, $settingItemKey, []);
                $rules[$settingItemKey] = array_merge(
                    ! is_array($existingItemRules) ? [$existingItemRules] : $existingItemRules,
                    $settingsValidationRules
                );
            }
        }

        return $rules;
    }
}
