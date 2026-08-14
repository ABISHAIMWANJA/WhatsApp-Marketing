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


namespace App\Yantrana\Components\Subscription\Support;

use ArrayObject;

/**
 * Subscription Plan details Response class
 */
class SubscriptionPlanDetails extends ArrayObject
{
    // public $has_active_plan;

    public function __construct($array = [])
    {
        parent::__construct($array, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Check if the vendor has active plan or not
     *
     * @return bool
     */
    public function hasActivePlan()
    {
        return $this->has_active_plan ?? null;
    }

    public function planType()
    {
        return $this->plan_type ?? null;
    }
    public function currentUsage()
    {
        return $this->current_usage ?? null;
    }
    public function isLimitAvailable()
    {
        return $this->is_limit_available ?? null;
    }
    public function featureLimit()
    {
        return $this->plan_feature_limit ?? null;
    }
    public function message()
    {
        return $this->message ?? '';
    }
    public function planTitle()
    {
        return $this->plan_title ?? null;
    }
    public function isAuto()
    {
        return $this->subscription_type == 'auto';
    }
}
