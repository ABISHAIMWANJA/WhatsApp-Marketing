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
 * BotReplyRepository.php - Repository file
 *
 * This file is part of the BotReply component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\BotReply\Repositories;

use App\Yantrana\Base\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use App\Yantrana\Components\BotReply\Models\BotReplyModel;
use App\Yantrana\Components\BotReply\Interfaces\BotReplyRepositoryInterface;

class BotReplyRepository extends BaseRepository implements BotReplyRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var  object
     */
    protected $primaryModel = BotReplyModel::class;


    /**
     * Fetch botReply datatable source
     *
     * @return  mixed
     *---------------------------------------------------------------- */
    public function fetchBotReplyDataTableSource()
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'name',
                'reply_text',
                'trigger_type',
                'reply_trigger'
            ]
        ];
        // get Model result for dataTables
        return BotReplyModel::where([
            'vendors__id' => getVendorId()
        ])->whereNull('bot_flows__id')
        ->where('trigger_type', '!=', 'NT_CAMPAIGN_MESSAGE') // it should not be non template campaign message preset
        ->dataTables($dataTableConfig)->toArray();
    }
    /**
      * Fetch botReply datatable source
      *
      * @return  mixed
      *---------------------------------------------------------------- */
    public function fetchNonTemplateCampaignMessagePresetsDataTableSource()
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'name',
                'reply_text',
                'trigger_type',
                'reply_trigger'
            ]
        ];
        // get Model result for dataTables
        return BotReplyModel::query()
                ->where('vendors__id', getVendorId())
                ->whereNull('bot_flows__id')
                ->where('trigger_type', 'NT_CAMPAIGN_MESSAGE')
                ->dataTables($dataTableConfig)->toArray();
    }
    /**
      * Fetch botReply for non template campaign message presets
      *
      * @return  mixed
      *---------------------------------------------------------------- */
    public function fetchNonTemplateCampaignMessagePresets()
    {
        // get Model result for dataTables
        return BotReplyModel::query()
                ->where('vendors__id', getVendorId())
                ->whereNull('bot_flows__id')
                ->where('trigger_type', 'NT_CAMPAIGN_MESSAGE')
                ->get();
    }

    /**
     * Fetch botReply count
     *
     * @return  number
     *---------------------------------------------------------------- */
    public function fetchBotReplyCount($vendorId = null)
    {
        // get Model result for dataTables
        $vendorId = $vendorId ?: getVendorId();
        return BotReplyModel::query()
            ->where('vendors__id', $vendorId)
            ->whereNull('bot_flows__id')
            ->count();
    }

    /**
     * Fetch botReply count
     *
     * @return  number
     *---------------------------------------------------------------- */
    public function fetchBotReplyCountForDashboard($vendorId)
    {
        return BotReplyModel::query()
            ->where('vendors__id', $vendorId)
            ->where(function($query) {
                $query->where('status', 1)
                    ->orWhereNull('status');

            })
            ->whereNull('bot_flows__id')
            ->where('trigger_type', '!=', 'NT_CAMPAIGN_MESSAGE')
            ->count();
    }

    /**
     * Delete $botReply record and return response
     *
     * @param  object $inputData
     *
     * @return  mixed
     *---------------------------------------------------------------- */

    public function deleteBotReply($botReply)
    {
        // Check if $botReply deleted
        if ($botReply->deleteIt()) {
            // if deleted
            return true;
        }
        // if failed to delete
        return false;
    }

    /**
     * Store new botReply record and return response
     *
     * @param  array $inputData
     *
     * @return  mixed
     *---------------------------------------------------------------- */

    public function storeBotReply($inputData)
    {
        // prepare data to store
        $keyValues = [
            'name',
            'reply_text',
            'trigger_type',
            'reply_trigger',
            'vendors__id',
            'bot_flows__id',
            'status',
            '__data',
        ];
        return $this->storeIt($inputData, $keyValues);
    }

    /**
     * Update List message & buttons as our json array:extend keeps existing records even if we want to delete it
     *
     * @param int $botReplyId
     * @param array $updateData
     * @return bool|int
     * @note - as updateIt creates problems for delete null values we have used update
     */
    public function updateForListAndButtonMessage($botReplyId, $updateData)
    {
        return $this->primaryModel::where('_id', $botReplyId)->update($updateData);
    }

    /**
     * Reset bot triggers to null
     *
     * @param array $botUids - array of _uids
     * @return mixed
     */
    function resetBotTriggers($botUids = [])
    {
        return $this->primaryModel::whereIn('_uid', $botUids)->update([
            'reply_trigger' => null
        ]);
    }

    /**
     * Get Related Or Welcome Bots for vendor
     *
     * @param int $vendorId
     * @return Eloquent
     */
    function getRelatedOrWelcomeBots($whereConditions = [])
    {
        return $this->primaryModel::query()->select([
            '_id',
            'reply_trigger',
            'reply_text',
            'trigger_type',
            'priority_index',
            '__data',
            'bot_flows__id',
            'status',
        ])->where($whereConditions)->get();
    }

    /**
     * Fetch Reply Bot
     *
     * @param int $vendorId
     * @return Eloquent
     */
    function fetchBotReplies()
    {
        return BotReplyModel::query()
                ->where('vendors__id', getVendorId())
                ->where('status', 1)
                ->where('trigger_type', '!=', 'NT_CAMPAIGN_MESSAGE')
                ->select(
                    '_id',
                    '_uid',
                    'name',
                    'status'
                )
                ->get();
    }
}
