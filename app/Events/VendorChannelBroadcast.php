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


namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class VendorChannelBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $message;

    public $vendorUid;

    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct(string $vendorUid, array $data)
    {
        //
        $this->message = 'vendor-broadcast';
        $this->vendorUid = $vendorUid;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('vendor-channel.'.$this->vendorUid),
        ];
    }

    public function broadcastAs()
    {
        return 'VendorChannelBroadcast';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        if (! empty($this->data)) {
            return $this->data;
        }

        return [];
    }
}
