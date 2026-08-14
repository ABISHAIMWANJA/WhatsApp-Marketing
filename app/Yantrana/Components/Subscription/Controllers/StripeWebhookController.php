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
 * StripeWebhookController.php - Controller file
 *
 * This file is part of the Subscription component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Subscription\Controllers;

use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    /*
    customer.subscription.created
    customer.subscription.updated
    customer.subscription.deleted
    customer.updated
    customer.deleted
    invoice.payment_action_required
    */

    /**
     * Handle invoice payment succeeded.
     * invoice.payment_succeeded
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        // Handle the incoming event...
    }

    /**
     * Handle subscription deleted
     * invoice.payment_succeeded
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function customerSubscriptionDeleted($payload)
    {
        __logDebug($payload);
        // Handle the incoming event...
    }

    public function handleWebhook(Request $request)
    {
        $webhookResponse = parent::handleWebhook($request);

        return $webhookResponse;
    }
}
