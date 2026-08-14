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
* ManualSubscription.php - Model file
*
* This file is part of the Subscription component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Subscription\Models;

use App\Yantrana\Base\BaseModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualSubscriptionModel extends BaseModel
{
    /**
     * @var  string $table - The database table used by the model.
     */
    protected $table = "manual_subscriptions";

    /**
     * @var  array $casts - The attributes that should be casted to native types.
     */
    protected $casts = [
        'ends_at' => 'datetime',
        '__data' => 'array'
    ];

    /**
     * Let the system knows Text columns treated as JSON
     *
     * @var array
     *----------------------------------------------------------------------- */
    protected $jsonColumns = [
        '__data' => [
            'prepared_plan_details' => 'array',
            'manual_txn_details' => 'array:extend',
            'txn_data' => 'array',
            'auto_recurring_data' => 'array:extend',
            'authenticated' => 'array',
            'activated' => 'array',
            'charged' => 'array',
            'completed' => 'array',
            'cancelled' => 'array',
            'updated' => 'array'
        ],
    ];

    /**
     * @var  array $fillable - The attributes that are mass assignable.
     */
    protected $fillable = [
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorModel::class, 'vendors__id', '_id');
    }
}
