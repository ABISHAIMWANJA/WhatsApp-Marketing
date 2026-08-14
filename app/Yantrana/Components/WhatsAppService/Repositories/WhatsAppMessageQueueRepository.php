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
* WhatsAppMessageQueueRepository.php - Repository file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppMessageQueueRepositoryInterface;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageQueueModel;

class WhatsAppMessageQueueRepository extends BaseRepository implements WhatsAppMessageQueueRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = WhatsAppMessageQueueModel::class;

    /**
     * Stuck message processing
     *
     * @return int
     */
    public function stuckInProcessing()
    {
        $where = [
            'status' => 3, // processing
            [
                'scheduled_at', '<=', now()
            ],
            [
                'updated_at', '<=', now()->subMinutes(5)
            ]
        ];
        $stuckItemsCount = $this->primaryModel::where($where)->count();
        if ($stuckItemsCount) {
            // stuck queue items in processing more than 5 minutes
            $this->updateItAll($where, [
                'status' => 6, // processed & response awaited
                '__data' => [
                    'process_response' => [
                        'error_status'  => 'awaited_response_error',
                        'error_message' => 'Responses awaited from WhatsApp',
                    ]
                    
                ]
            ]);
        }
        return $stuckItemsCount;
    }

    /**
     * Take the items from database for message process
     *
     * @return Eloquent Objects
     */
    public function getQueueItemsForProcess()
    {
        $this->updateItAll([
            'status' => 1,
            [
                '__data->expiry_at', '<=', now()
            ]
        ], [
            'status' => 5, // Expired
            '__data' => [
                'process_response' => [
                    'error_message' => 'message expired',
                    'error_status' => 'campaign_expired_error',
                ]
            ]
        ]);

        // go grab queue records for processing
        return $this->primaryModel::select([
            '_id',
            'status',
            'scheduled_at',
        ])->where([
            // waiting for processing
            'status' => 1,
            [
                // time has passed on
                'scheduled_at', '<=', now()
            ],
        ])
        // ->oldest()
        ->take((getAppSettings('cron_process_messages_per_lot') ?: 60))->inRandomOrder()->get();
    }
    /**
     * Queued messages count
     * $campaignId - Campaign Id
     *
     * @return int
     */
    public function campaignQueueItemsCount($campaignId)
    {
        return $this->primaryModel::where([
            'status' => 1,
            'campaigns__id' => $campaignId,
        ])->count();
    }

    /**
     * Get in queue messages in chunk
     * $campaignId - Campaign Id
     *
     * @return int
     */
    public function fetchInQueueMessageInChunks($campaignId)
    {
        return $this->primaryModel::where([
            'status' => 1,
            'campaigns__id' => $campaignId,
        ])->update([
            'status' => 7
        ]);
    }

    /**
     * Get in queue messages in chunk
     * $campaignId - Campaign Id
     *
     * @return int
     */
    public function storeCampaignMessageQueData($storeData)
    {
        return $this->primaryModel::insert($storeData);
    }
}
