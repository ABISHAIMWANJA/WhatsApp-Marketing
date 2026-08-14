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


namespace App\Yantrana\Components\Subscription\PaymentEngines;

use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;

class PhonePeEngine extends BaseEngine
{
    /**
    * @var clientId - Client Id
    */
    protected $clientId;

    /**
    * @var clientVersion - Client Version
    */
    protected $clientVersion;

    /**
    * @var clientSecret - Client Secret
    */
    protected $clientSecret;

    /**
    * @var baseUrl - Base URL
    */
    protected $baseUrl = '';

    /**
    * @var isPhonePeTestMode - PhonePe Test Model
    */
    protected $isPhonePeTestMode;

    /**
     * Constructor
     *
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct() {
        if (getAppSettings('use_test_phonepe')) {
            $this->clientId = getAppSettings('phonepe_testing_client_id');
            $this->clientVersion = getAppSettings('phonepe_testing_client_version');
            $this->clientSecret = getAppSettings('phonepe_testing_secret_key');
            $this->baseUrl = 'https://api-preprod.phonepe.com/apis/';
            $this->isPhonePeTestMode = true;
        } else {
            $this->clientId = getAppSettings('phonepe_live_client_id');
            $this->clientVersion = getAppSettings('phonepe_live_secret_key');
            $this->clientSecret = getAppSettings('phonepe_live_client_version');
            $this->baseUrl = 'https://api.phonepe.com/apis/';
            $this->isPhonePeTestMode = false;
        }        
    }

    /**
     * Initiate PhonePe Payment Request
     *
     * @return Object
     */
    public function initiatePayment($subscriptionUid, $amount)
    {
        try {
            $phonePeApiUrl = '';
            // Check Test mode or Live mode
            if ($this->isPhonePeTestMode) {
                $phonePeApiUrl = "{$this->baseUrl}pg-sandbox/checkout/v2/pay";
            } else {                
                $phonePeApiUrl = "{$this->baseUrl}pg/checkout/v2/pay";
            }

            $accessTokenData = $this->generatePhonePeToken();

            // Check if access token generated or not
            if (!$accessTokenData['status']) {
                return $this->engineFailedResponse(['show_message' => true], $accessTokenData['message']);
            }
            
            $merchantOrderId = Uuid::uuid4()->toString();
            $initialPaymentData = Http::withHeaders([
                "Content-Type" => "application/json",
                "Authorization" => "O-Bearer ".$accessTokenData['accessToken']
            ])->post($phonePeApiUrl, [
                "amount" => $this->calculateAmount($amount),
                "metaInfo" => [
                    "udf1" => $subscriptionUid
                ],
                "paymentFlow" => [
                    "type" => "PG_CHECKOUT",
                    "merchantUrls" => [
                        "redirectUrl" => route('subscription.read.show')
                    ]
                ],
                "merchantOrderId" => $merchantOrderId
            ]);
            
            $phonePeInitiateData = $initialPaymentData->json();
            $phonePeInitiateData['merchantOrderId'] = $merchantOrderId;
            return $this->engineSuccessResponse(['phonePeInitiateData' => $phonePeInitiateData]);
        } catch (\Exception $e) {
            return $this->engineFailedResponse(['show_message' => true], $e->getMessage());
        }
    }

    /**
     * This method use for capturing payment.
     *
     * @param  string  $paymentId
     * @return paymentReceived
     *---------------------------------------------------------------- */
    public function capturePayment($merchantOrderId)
    {
        try {
            $phonePeApiUrl = '';
            // Check Test mode or Live mode
            if ($this->isPhonePeTestMode) {
                $phonePeApiUrl = "{$this->baseUrl}pg-sandbox/checkout/v2/order/{$merchantOrderId}/status";
            } else {
                $phonePeApiUrl = "{$this->baseUrl}pg/checkout/v2/order/{$merchantOrderId}/status";
            }

            $accessTokenData = $this->generatePhonePeToken();

            // Check if access token generated or not
            if (!$accessTokenData['status']) {
                return $this->engineFailedResponse(['show_message' => true], $accessTokenData['message']);
            }

            // fetch a particular payment
            $paymentReceived = $initialPaymentData = Http::withHeaders([
                "Content-Type" => "application/json",
                "Authorization" => "O-Bearer ".$accessTokenData['accessToken']
            ])->get($phonePeApiUrl);

            return $this->engineReaction(1, [
                'transactionDetail' => $paymentReceived->json(),
            ], __tr('Complete'));
        } catch (\Exception $e) {
            return $this->engineReaction(2, [
                'errorMessage' => 'Invalid Api Key',
            ], $e->getMessage());
        }
    }

    /**
     * Calculate PhonePe Amount
     *
     * @param $amount
     * 
     * @return Number
     */
    protected function calculateAmount($amount)
    {
        return $amount * 100; // PhonePe accept amount in paisa
    }

    /**
     * Generate PhonePe Token
     *
     * @return Http query request
     */
    protected function generatePhonePeToken()
    {
        try {
            $phonePeApiUrl = '';
            // Check Test mode or Live mode
            if ($this->isPhonePeTestMode) {
                $phonePeApiUrl = "{$this->baseUrl}pg-sandbox/v1/oauth/token";                
            } else {
                $phonePeApiUrl = "{$this->baseUrl}identity-manager/v1/oauth/token";
            }

            // Get token from PhonePe
            // This token is used for all PhonePe payment related API request
            $generateTokenResponse = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
            ])
            ->asForm()
            ->post($phonePeApiUrl, [
                'client_id' => $this->clientId,
                'client_version' => $this->clientVersion,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials'
            ]);

            // Check if PhonePe credentials are invalid
            if ($generateTokenResponse->status() == 401) {
                return [
                    'status' => false,
                    'message' => __tr('Invalid PhonePe Credentials.'),
                    'accessToken' => ''
                ];
            }

            // Check if API response status is 200
            if ($generateTokenResponse->status() != 200) {
                return [
                    'status' => false,
                    'message' => __tr('Error Occurred while creating PhonePe Order.'),
                    'accessToken' => ''
                ];
            }
        
            $generatedTokenData = $generateTokenResponse->json();

            return [
                'status' => true,
                'message' => __tr('Access Token Generated.'),
                'accessToken' => $generatedTokenData['access_token']
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => __tr('Something went wrong on server.'),
                'accessToken' => ''
            ];
        }
    }
}