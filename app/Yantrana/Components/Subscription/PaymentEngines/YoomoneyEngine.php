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
use Illuminate\Http\Request;
use App\Yantrana\Components\Subscription\Repositories\ManualSubscriptionRepository;
use YooKassa\Model\Notification\NotificationSucceeded;
    use YooKassa\Model\Notification\NotificationWaitingForCapture;
    use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Client;


/**
 * This MailService class for manage globally -
 * mail service in application.
 *---------------------------------------------------------------- */
class YoomoneyEngine extends BaseEngine
{

 /**
     * @var  ManualSubscriptionRepository $manualSubscriptionRepository - ManualSubscription Repository
     */
    protected $manualSubscriptionRepository;
     protected $yoomoneyKey;
    protected $yoomoneySecret;
    protected $yoomoneyVatKey;
   

    /**
     * Constructor.
     *  * @param  ManualSubscriptionRepository $manualSubscriptionRepository - ManualSubscription Repository
     *-----------------------------------------------------------------------*/
    public function __construct(  ManualSubscriptionRepository $manualSubscriptionRepository,)
    {
        //check paystack test mode is on
        if (getAppSettings('use_test_yoomoney')) {
            $yoomoneyKey = getAppSettings('yoomoney_testing_shop_id');
            $yoomoneySecret = getAppSettings('yoomoney_testing_secret_key');
           
          
        } else {
            $yoomoneyKey = getAppSettings('yoomoney_live_shop_id');
            $yoomoneySecret = getAppSettings('yoomoney_live_secret_key');
            $yoomoneyVatKey = getAppSettings('yoomoney_live_vat_id');  //no need for testing default 1
            $this->yoomoneyVatKey = $yoomoneyVatKey;
        }
        $this->yoomoneyKey = $yoomoneyKey;
        $this->yoomoneySecret = $yoomoneySecret;
      
        $this->manualSubscriptionRepository = $manualSubscriptionRepository;
     

    }

    /**
     * This method use for creating payment.
     *
     * @param  string  $paymentId
     * @return paymentReceived
     *---------------------------------------------------------------- */
    public function captureYoomoneyPayment($manualSubscriptionUid)
    {   
      
        $vendorData=getUserAuthInfo();
        $vatId = $this->yoomoneyVatKey ?? '1'; // set 1 for testing
        if (empty($vendorData['profile']['full_name']) || empty($vendorData) ) {
            return $this->engineFailedResponse(['show_message' => true], __tr('Missing required payment information.'));
        }
        $fullName='';
        $email='';
        if (!empty($vendorData) && isset($vendorData['profile']['full_name'])) {
            $fullName = $vendorData['profile']['full_name'];
            $email=$vendorData['profile']['email'];
        }
        $vendorId = getVendorId();
        $currency = getAppSettings('currency_value');
        $subscriptionRequestRecord = $this->manualSubscriptionRepository->fetchIt([
            'vendors__id' => $vendorId,
            'status' => 'initiated',
            '_uid' => $manualSubscriptionUid,
        ]);
         // Validate subscription record
        if (!$subscriptionRequestRecord ) {
            return $this->engineFailedResponse(['show_message' => true], __tr('Subscription record not found.'));
        }

        $amount = $subscriptionRequestRecord['charges'] ?? ' ';
        // 1058960 ,test_9A2VPY_hTfwKzRXEMG7g_5dLF4n6x6kpIhyqaU04u9E
        try {
            $client = new Client();
           
            $client->setAuth(
                (int)$this->yoomoneyKey,
                $this->yoomoneySecret
            );
            $orderId = uniqid('', true);
            $payment = $client->createPayment(
                [
                    'amount' => [
                        'value' => $amount , // Amount in RUB
                        'currency' => $currency,
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => route('yoomoney.capture.payment', [
                            'manualSubscriptionUid' => $manualSubscriptionUid
                        ]),
                    ],
                    'capture' => true,
                    'description' => 'Order No.' . $orderId,
                    'metadata' => [
                        'order_id' => $orderId,
                        'manual_subscription_uid'=> $manualSubscriptionUid,
                    ],
                    'receipt' => [
                        'customer' => [
                            'full_name' => $fullName, // Or get from user input
                            'email' => $email, // Required for most payments
                        ],
                       'items' => [
                [
                    'description' => 'Subscription Plan',
                    'quantity' => 1.0,
                    'amount' => [
                        'value' => $amount,
                        'currency' => $currency,
                    ],
                    'vat_code' =>$vatId,
                    'payment_mode' => 'full_payment',       
                    'payment_subject' => 'service',          
                ],
            ],
                    ],
                ],
                $orderId
            );
         
        
            if (!$payment || !$payment->getConfirmation()) {
                return $this->engineFailedResponse(['show_message' => true], __tr('Payment initiation failed.'));
            }
            $paymentUrl = $payment->getConfirmation()->getConfirmationUrl();

        if ($this->manualSubscriptionRepository->updateIt($subscriptionRequestRecord, [
            '__data' => [
                'txn_data' => $payment->getId(),
            ]
        ])) {
            return $this->engineSuccessResponse([
                'success' => true,
            'payment_url' => $paymentUrl,
            'payment_id' => $payment->getId()
            ]);
        }
    } catch (\YooKassa\Common\Exceptions\ApiException $e) {
        return $this->engineFailedResponse(['show_message' => false], __tr('Invalid Credentials'));
        // API exception (e.g., invalid credentials, invalid request)
        echo 'API Error: ' . $e->getMessage();
    } 
    catch (\Exception $e) {
        __logDebug('Error', ['message' => $e->getMessage()]);
        return $this->engineFailedResponse(['show_message' => false], __tr('Invalid Data'));
    }
    }
 /**
     * This method use for capturing payment.
     *
     * @param  string  $paymentId
     * @return paymentReceived
    *---------------------------------------------------------------- */
    public function captureYoomoney($paymentId,$manualSubscriptionUid)
    {
        $client = new Client();
        $client->setAuth(
            $this->yoomoneyKey,
            $this->yoomoneySecret
        );
      
        // 2f775554-000f-5000-b000-1aa481c09865
        $paymentData = $client->getPaymentInfo($paymentId);
        $client->setAuthToken($paymentId);
       
        $paymentStatus = $paymentData->getStatus();
       
        if ($paymentStatus == 'succeeded') {

            return $this->engineSuccessResponse([
                'capturedYoomoneyData' =>  $paymentData,
                'txn_reference' => $paymentData->getId(),
                'manual_subscription_uid' => $manualSubscriptionUid,
                'txn_date' =>$paymentData->getCapturedAt()->format('Y-m-d H:i:s'),
            ], __tr('Payment completed successfully!'));
        } else {
            // Transaction failed
            return $this->engineFailedResponse(['show_message' => true], __tr('Purchase failed'));

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
        $source = file_get_contents('php://input');
        $requestData= json_decode($source, true);
     
        $event = $requestData['event'] ?? null;
      
        if ($event === 'payment.succeeded') {
            $notification = ($requestData['event'] === NotificationEventType::PAYMENT_SUCCEEDED)
            ? new NotificationSucceeded($requestData)
            : new NotificationWaitingForCapture($requestData);
            $transactionData = $notification->getObject();
          
            return $this->engineSuccessResponse(['transactionData' =>
            $requestData['object'],
            ]);

        } else {
            return $this->engineFailedResponse(['show_message' => true], __tr('Unhandled yoomoney Event'));
          

        }
     
    } catch (\Exception $e) {
          // Invalid signature
          __logDebug('Webhook Error', ['message' => $e->getMessage()]);
        return $this->engineFailedResponse(['show_message' => true], __tr('Payment Failed'));
    }
    
       
    }

}
