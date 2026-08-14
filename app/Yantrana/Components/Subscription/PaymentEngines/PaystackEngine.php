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

/**
 * This MailService class for manage globally -
 * mail service in application.
 *---------------------------------------------------------------- */
class PaystackEngine extends BaseEngine
{


     protected $paystackKey;
    protected $paystackSecret;

    /**
     * Constructor.
     *
     *-----------------------------------------------------------------------*/
    public function __construct()
    {
        //check paystack test mode is on
        if (getAppSettings('use_test_paystack_checkout')) {
            $paystackKey = getAppSettings('paystack_checkout_testing_publishable_key
            ');
            $paystackSecret = getAppSettings('paystack_checkout_testing_secret_key');
        } else {
            $paystackKey = getAppSettings('paystack_checkout_live_publishable_key');
            $paystackSecret = getAppSettings('paystack_checkout_live_secret_key');
        }
        $this->paystackKey = $paystackKey;
        $this->paystackSecret = $paystackSecret;
    }

    /**
     * This method use for capturing payment.
     *
     * @param  string  $paymentId
     * @return paymentReceived
     *---------------------------------------------------------------- */
    public function capturePaystackPayment($reference, $manualSubscriptionUid)
    {

        // Paystack secret key
        $paystackSecretKey = $this->paystackSecret; 

        if (!$reference) {
            return response()->json(['error' => 'Reference not provided'], 400);
        }
        // Call Paystack API to verify the transaction
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");
        $transactionData = $response->json();

        // if ($response->failed()) {
        //     return response()->json(['error' => 'Unable to verify transaction'], 500);
        // }
        if ($transactionData['status'] == 'true' && $transactionData['data']['status'] === 'success') {

            return $this->engineSuccessResponse([
                'capturedPaystackData' => $transactionData['data'],
                'txn_reference' => $transactionData['data']['reference'],
                'manual_subscription_uid' => $manualSubscriptionUid,
            ], __tr('Payment verified successfully!'));
        } else {
            // Transaction failed
            return response()->json(['success' => false, 'message' => 'Payment verification failed.']);

        }
    }

    /**
     * get payment response by webhook
     *
     * @return  array
     *-------------------------------------*/
    public function paymentWebhook()
    {
        try {
            //    Paystack secret key
            $paystackSecretKey = $this->paystackSecret;

            // Retrieve the request's body
            $input = @file_get_contents("php://input");
            $sig_header = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'];
            // validate event do all at once to avoid timing attack
            if ($sig_header== hash_hmac('sha512', $input, $paystackSecretKey)) {
                 // parse event (which is json string) as object
            // Do something - that will not take long - with $event
            $data = json_decode($input, true);
            $event = $data['event'] ?? null;
            if ($event === 'charge.success') {
                $transactionData = $data['data'];
                return $this->engineSuccessResponse(['transactionData' =>
                    $transactionData,
                ]);

            } else {
                return response()->json(['error' => 'Unhandled Paystack Event' . $event], 400);

            }
            }
        } catch (\Exception $e) {
              // Invalid signature
              __logDebug('Webhook Error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Server error'], 500);
        }

    }
}
