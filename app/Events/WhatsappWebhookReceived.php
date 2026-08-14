<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WhatsappWebhookReceived
{
    use Dispatchable;

    public $webhookData;

    public $vendorUid;

    /**
     * Create a new event instance.
     */
    public function __construct(array $webhookData, string $vendorUid)
    {
        $this->webhookData = $webhookData;
        $this->vendorUid = $vendorUid;
    }
}
