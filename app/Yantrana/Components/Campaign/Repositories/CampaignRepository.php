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
 * CampaignRepository.php - Repository file
 *
 * This file is part of the Campaign component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Campaign\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Campaign\Interfaces\CampaignRepositoryInterface;
use App\Yantrana\Components\Campaign\Models\CampaignModel;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageQueueModel;
use Illuminate\Support\Facades\DB;

class CampaignRepository extends BaseRepository implements CampaignRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = CampaignModel::class;

    /**
     * Fetch campaign datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchCampaignDataTableSource($status)
    {
        if ($status == "archived") {
            $status = [5];
        } else {
            $status = [1, 6];
        }

        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'title',
                'whatsapp_templates__id',
                'scheduled_at',
            ],
            'fieldAlias' => [
                'contacts_count' => '__data->total_contacts'
            ]
        ];
        $vendorId = getVendorId();
        // get Model result for dataTables
        return $this->primaryModel::query()
            ->where('vendors__id', $vendorId)
            ->whereIn('status', $status)
            ->withExists([
                'messageLog as message_log_count',
                'queuePendingMessages as queue_pending_messages_count',
                'queueProcessingMessages as queue_processing_messages_count',
                'queueFailedMessages as queue_failed_messages_count',
            ])->dataTables($dataTableConfig)->toArray();
    }

    /**
     * Get the campaign data
     *
     * @param int $campaignId
     * @return Eloquent
     */
    public function fetchCampaignData($campaignId)
    {
        return $this->primaryModel::where([
            'vendors__id' => getVendorId(),
            '_id' => $campaignId,
        ])
        ->withCount([
            'queuePendingMessages',
            'queueProcessingMessages'
        ])
        ->with([
            'messageLog' => function ($query) {
                $query->whereNull('is_system_message');
            },
            'queueMessages'
        ])->first();
    }

    /**
     * Get the campaign data
     *
     * @param int $campaignId
     * @return Eloquent
     */
    public function getCampaignData($campaignId)
    {
        return $this->primaryModel::where([
            'vendors__id' => getVendorId(),
            '_uid' => $campaignId,
        ])
            ->withCount('messageLog')->withCount([
                'queuePendingMessages',
                'queueProcessingMessages',
                'queueFailedMessages',
            ])->with([
                'messageLog' => function ($query) {
                    $query->whereNull('is_system_message');
                },
                'queueMessages'
            ])->first();
    }

    /**
     * Delete $campaign record and return response
     *
     * @param  object  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function deleteCampaign($campaign)
    {
        // Check if $campaign deleted
        if ($campaign->deleteIt()) {
            // if deleted
            return true;
        }
        // if failed to delete
        return false;
    }

    /**
     * Store new campaign record and return response
     *
     * @param  array  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function storeCampaign($inputData)
    {
        // prepare data to store
        $keyValues = [
            'title',
            'template_name',
            'whatsapp_templates__id' => $inputData['whatsapp_template'],
            'scheduled_at' => $inputData['schedule_at'],
        ];
        return $this->storeIt($inputData, $keyValues);
    }

    /**
     * Fetch campaign queue log datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchCampaignQueueLogTableSource($campaignId, $logStatus)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'full_name',
                'updated_at',
                'status',
                'phone_with_country_code',
            ],
            'fieldAlias' => [
                'formatted_status' => 'status',
            ]
        ];
        // search name in json
        request()->merge([
            'search' => [
                'value' => strtolower(request()->search['value'] ?? ''),
                'regex' => request()->search['regex'] ?? null
            ]
        ]);

        // Get Model result for dataTables
        return WhatsAppMessageQueueModel::where('campaigns__id', $campaignId)
            ->where(function ($query) use ($logStatus) {
                if ($logStatus != 'all') {
                    $query->where('status', $logStatus);
                }
            })
            ->whereNotIn('status', [5]) // Expired
            ->dataTables($dataTableConfig)
            ->toArray();
    }
    /**
     * Fetch campaign datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchCampaignExecutedLogTableSource($campaignId, $logStatus)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'full_name',
                'contact_wa_id',
                'messaged_at',
                'updated_at',
                'status',
            ],
        ];
        // search name in json
        request()->merge([
            'search' => [
                'value' => strtolower(request()->search['value'] ?? ''),
                'regex' => request()->search['regex'] ?? null
            ]
        ]);
        $vendorId = getVendorId();
        // get Model result for dataTables
        return WhatsAppMessageLogModel::query()->where('vendors__id', $vendorId)
            ->where('campaigns__id', $campaignId)
            ->where(function ($query) use ($logStatus) {
                if ($logStatus != 'all') {
                    $query->where('status', $logStatus);
                }
            })
            ->dataTables($dataTableConfig)
            ->toArray();
    }
    /**
     * Fetch campaign datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchCampaignExpiredLogTableSource($campaignId)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'full_name',
                'updated_at',
                'status',
                'phone_with_country_code',
            ],
        ];
        // search name in json
        request()->merge([
            'search' => [
                'value' => request()->search['value'] ?? '',
                'regex' => request()->search['regex'] ?? null
            ]
        ]);
        $vendorId = getVendorId();
        // Get Model result for dataTables
        return WhatsAppMessageQueueModel::query()
            ->where('vendors__id', $vendorId)
            ->where('campaigns__id', $campaignId)
            ->where('status', 5) // Expired
            ->dataTables($dataTableConfig)
            ->toArray();
    }
    /**
     * Get the campaign  Executed data
     *
     * @param int  $campaignId
     *
     * @return LazyCollection
     */
    public function fetchCampaignExecutedDataLazily($campaignId, $callback)
    {
        return WhatsAppMessageLogModel::where('campaigns__id', $campaignId)->lazy()->each($callback);
    }
    /**
     * Get the campaign queue log data
     *
     * @param int $campaignId

     * @return LazyCollection
     */
    public function fetchCampaignQueueLogDataLazily($campaignId, $callback)
    {
        return WhatsAppMessageQueueModel::where('campaigns__id', $campaignId)
            ->whereNotIn('status', [5]) // Not Expired
            ->lazy()
            ->each($callback);
    }
    /**
     * Get the campaign expired log data
     *
     * @param int $campaignId

     * @return LazyCollection
     */
    public function fetchCampaignExpiredLogDataLazily($campaignId, $callback)
    {
        return WhatsAppMessageQueueModel::where('campaigns__id', $campaignId)
            ->where('status', 5) // Expired
            ->lazy()
            ->each($callback);
    }

    /**
     * Get the campaign expired log data
     *
     * @param int $campaignId

     * @return LazyCollection
     */
    public function fetchFailedCampaignByType($campaignId, $failedCampaignType)
    {
        if ($failedCampaignType == 'queue') {
            return WhatsAppMessageQueueModel::where('campaigns__id', $campaignId)
                ->select('_id', 'campaigns__id', 'contacts__id', 'status')
                ->whereNotIn('status', [5]) // Expired
                ->get();
        } elseif ($failedCampaignType == 'expired') {
            return WhatsAppMessageQueueModel::where('campaigns__id', $campaignId)
                ->select('_id', 'campaigns__id', 'contacts__id', 'status')
                ->where('status', 5) // Expired
                ->get();
        } elseif ($failedCampaignType == 'executed') {
            return WhatsAppMessageLogModel::where('campaigns__id', $campaignId)
                ->select('_id', 'campaigns__id', 'contacts__id')
                ->get();
        }
    }

    public function fetchTotalCampaignContacts($campaignId)
    {
        $messageQueueData = WhatsAppMessageQueueModel::where('campaigns__id', $campaignId)
            ->select('_id', 'campaigns__id', 'contacts__id')
            ->get();

        $messageLogData = WhatsAppMessageLogModel::where('campaigns__id', $campaignId)
            ->select('_id', 'campaigns__id', 'contacts__id')
            ->get();

        return $messageQueueData->merge($messageLogData);
    }

    public function fetchCampaignListPaginatedData()
    {
        $paginateCount = request()->get('page_size') ?? 100;
        $searchTerm = request()->get('search_term');

        return $this->primaryModel::where('vendors__id', getVendorId())->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('template_name', 'like', "%{$searchTerm}%")
                ->orWhere('template_language', 'like', "%{$searchTerm}%")
                ->orWhere('timezone', 'like', "%{$searchTerm}%");
        })
            ->select('_id', '_uid', 'title', 'status', 'template_name', 'updated_at', 'created_at', 'whatsapp_templates__id', 'scheduled_at', 'users__id', 'vendors__id', 'template_language', 'timezone', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(__data, '$.total_contacts')) as total_contacts"))
            ->orderByDesc('updated_at')
            ->paginate($paginateCount);
    }
}
