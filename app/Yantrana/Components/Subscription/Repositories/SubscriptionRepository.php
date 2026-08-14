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
 * SubscriptionRepository.php - Repository file
 *
 * This file is part of the Subscription component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Subscription\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Subscription\Interfaces\SubscriptionRepositoryInterface;
use App\Yantrana\Components\Subscription\Models\SubscriptionModel;
use Illuminate\Support\Facades\DB;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = SubscriptionModel::class;

    public function fetchSubscriptionDataTableSource()
    {
        $dataTableConfig = [
            'fieldAlias' => [
                'created_at' => 'subscriptions.created_at',
                'stripe_id' => 'subscriptions.stripe_id',
                'plan_type' => 'subscriptions.type',
            ],
            'searchable' => [
                'title',
                'subscriptions.type',
                'subscriptions.stripe_id',
                'stripe_price',
            ],
        ];

        return $this->primaryModel::select(DB::raw('vendors.*, subscriptions.*, subscriptions.type AS plan_type'))
        ->leftJoin('vendors', 'subscriptions.vendor_model__id', '=', 'vendors._id')
        ->dataTables($dataTableConfig)->toArray();
    }
}
