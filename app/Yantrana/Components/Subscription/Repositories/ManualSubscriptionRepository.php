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
 * ManualSubscriptionRepository.php - Repository file
 *
 * This file is part of the Subscription component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Subscription\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Subscription\Models\ManualSubscriptionModel;
use App\Yantrana\Components\Subscription\Interfaces\ManualSubscriptionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ManualSubscriptionRepository extends BaseRepository implements ManualSubscriptionRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var  object
     */
    protected $primaryModel = ManualSubscriptionModel::class;


    /**
     * Fetch manualSubscription datatable source
     *
     * @return  mixed
     *---------------------------------------------------------------- */
    public function fetchManualSubscriptionDataTableSource($vendorId = null, $isAutoRecurring)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'plan_id',
                'ends_at',
                'status',
                'remarks',
                'charges_frequency',
                'charges',
            ],
            'fieldAlias' => [
                'is_auto_subscription' => 'is_auto_recurring'
            ]
        ];
        
        if ($isAutoRecurring) {
            $manualSubscriptionModel = ManualSubscriptionModel::whereNotNull('is_auto_recurring');
        } else {
            $manualSubscriptionModel = ManualSubscriptionModel::whereNull('is_auto_recurring');
        }
        
        // get Model result for dataTables
        if ($vendorId) {
            $manualSubscriptionModel->where('vendors__id', $vendorId);
        }
        return $manualSubscriptionModel->with('vendor')->dataTables($dataTableConfig)->toArray();
    }

    /**
     * Fetch auto subscription datatable source
     *
     * @return  mixed
     *---------------------------------------------------------------- */
    public function fetchAutoSubscriptionDataTableSource($gateway, $vendorId = null)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'status' => 'manual_subscriptions.status',
                'title',
                'plan_id',
                'ends_at',
                'remarks',
                'charges_frequency',
                'charges',
            ],
            'fieldAlias' => [
                'created_at' => 'manual_subscriptions.created_at',
                'status' => 'manual_subscriptions.status'
            ]
        ];

        $whereCondition = [
            'manual_subscriptions.gateway' => $gateway,
            'manual_subscriptions.is_auto_recurring' => 1
        ];

        if ($vendorId) {
            $whereCondition['manual_subscriptions.vendors__id'] = $vendorId;
        }

        return ManualSubscriptionModel::select(
            __nestedKeyValues([
                'manual_subscriptions' => [
                    '_id',
                    '_uid',
                    'status',
                    'plan_id',
                    'ends_at',
                    'remarks',
                    'charges_frequency',
                    'charges',
                    'gateway_price_id',
                    '__data',
                    'created_at'
                ],
                'vendors' => [
                    '_id AS vendor_id',
                    '_uid AS vendor_uid',
                    'title',
                ]
            ])
        )
            ->leftJoin('vendors', 'manual_subscriptions.vendors__id', '=', 'vendors._id')
            ->where($whereCondition)
            ->dataTables($dataTableConfig)->toArray();
    }

    /**
     * Delete $manualSubscription record and return response
     *
     * @param  object $inputData
     *
     * @return  mixed
     *---------------------------------------------------------------- */

    public function deleteManualSubscription($manualSubscription)
    {
        // Check if $manualSubscription deleted
        if ($manualSubscription->deleteIt()) {
            // if deleted
            return true;
        }
        // if failed to delete
        return false;
    }

    function getCurrentActiveSubscription($vendorId)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId,
            'status' => 'active',
        ])->latest()->first();
    }
}
