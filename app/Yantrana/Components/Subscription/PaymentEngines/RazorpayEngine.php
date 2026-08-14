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
use Razorpay\Api\Api as RazorpayAPI;
use Razorpay\Api\Errors\BadRequestError;
use Illuminate\Support\Str;


/**
 * This MailService class for manage globally -
 * mail service in application.
 *---------------------------------------------------------------- */
class RazorpayEngine extends BaseEngine
{
    protected $razorpayAPI;
    protected $webhookSecret;
    /**
     * Constructor.
     *
     *-----------------------------------------------------------------------*/
    public function __construct()
    {
        //check razorpay test mode is on
        if (getAppSettings('use_test_razorpay')) {
            $razorpayKey = getAppSettings('razorpay_testing_publishable_key');
            $razorpaySecret = getAppSettings('razorpay_testing_secret_key');
            $this->webhookSecret = getAppSettings('razorpay_testing_webhook_secret');
        } else {
            $razorpayKey = getAppSettings('razorpay_live_publishable_key');
            $razorpaySecret = getAppSettings('razorpay_live_secret_key');
            $this->webhookSecret = getAppSettings('razorpay_live_webhook_secret');
        }
        $this->razorpayAPI = new RazorpayAPI($razorpayKey, $razorpaySecret);
    }

    /**
     * This method use for capturing payment.
     *
     * @param  string  $paymentId
     * @return paymentReceived
     *---------------------------------------------------------------- */
    public function capturePayment($paymentId)
    {
        try {
            // fetch a particular payment
            $payment = $this->razorpayAPI->payment->fetch($paymentId);

            // Captures a payment
            $paymentReceived = $this->razorpayAPI->payment->fetch($paymentId)->capture(['amount' => $payment['amount']]);

            return $this->engineReaction(1, [
                'transactionDetail' => $paymentReceived->toArray(),
            ], __tr('Complete'));
        } catch (\Exception $e) {
            return $this->engineReaction(2, [
                'errorMessage' => 'Invalid Api Key',
            ], $e->getMessage());
        }
    }

    /**
     * get payment response by webhook
     *
     * @return  array
     *-------------------------------------*/
    public function paymentWebhook()
    {
        $this->razorpayAPI;

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
        $event = $paymentIntent = null;

        try {
            $this->razorpayAPI->utility->verifyWebhookSignature($payload, $sig_header, $this->webhookSecret);
            $event = json_decode($payload, true);
        } catch (\Errors\SignatureVerificationError $e) {
            // Invalid signature
            __logDebug($e->getMessage());
            http_response_code(400);
            exit();
        }

        switch ($event['event']) {
            case 'payment.captured':
                $paymentIntent = $event;
                break;

            default:
                echo 'Received unknown event type ' . $event['event'];
                break;
        }
        http_response_code(200);
        return $this->engineReaction(1, ['paymentIntent' => $paymentIntent]);
    }
}
