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


namespace App\Yantrana\Services\PushBroadcast;

use Exception;
use Pusher\Pusher;

/**
 * PushBroadcast
 *
 *
 *--------------------------------------------------------------------------- */

/**
 * This PushBroadcast class.
 *---------------------------------------------------------------- */
class PushBroadcast
{
    /**
     * $pusher - pusher object
     *-----------------------------------------------------------------------*/
    private $pusher = null;

    /**
     * __construct
     *-----------------------------------------------------------------------*/
    public function __construct()
    {
        /**
         * pusher details
         */
        if (getAppSettings('allow_pusher')) {
            $pusherAppId = getAppSettings('pusher_app_id');
            $pusherKey = getAppSettings('pusher_app_key');
            $pusherSecret = getAppSettings('pusher_app_secret');
            // Pusher call
            $this->pusher = new Pusher(
                $pusherKey,
                $pusherSecret,
                $pusherAppId,
                [
                    'cluster' => getAppSettings('pusher_app_cluster_key'),
                    'useTLS' => true,
                ]
            );
        }
    }

    /**
     * trigger pusher services
     *-----------------------------------------------------------------------*/
    public function trigger($channels, $event, $data)
    {
        try {
            //trigger channel event to pusher instance
            if (getAppSettings('allow_pusher')) {
                $this->pusher->trigger($channels, $event, $data);
            }
        } catch (Exception $e) {
            //log error message
            __logDebug($e->getMessage());
        }
    }

    /**
     * account trigger
     *-----------------------------------------------------------------------*/
    public function accountTrigger($event, $data)
    {
        return $this->trigger('channel-'.$data['userUid'], $event, $data);
    }

    /**
     * push via notification request
     *-----------------------------------------------------------------------*/
    public function notifyViaPusher($eventId, $data)
    {
        return $this->accountTrigger($eventId, $data);
    }
}
