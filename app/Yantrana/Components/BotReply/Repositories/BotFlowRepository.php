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
* BotFlowRepository.php - Repository file
*
* This file is part of the BotReply component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\BotReply\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\BotReply\Models\BotFlowModel;
use App\Yantrana\Components\BotReply\Interfaces\BotFlowRepositoryInterface;

class BotFlowRepository extends BaseRepository implements BotFlowRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var  object
     */
    protected $primaryModel = BotFlowModel::class;


    /**
      * Fetch botFlow datatable source
      *
      * @return  mixed
      *---------------------------------------------------------------- */
    public function fetchBotFlowDataTableSource()
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'title',
                'start_trigger',
            ]
        ];
        // get Model result for dataTables
        return BotFlowModel::where([
            'vendors__id' => getVendorId()
        ])->dataTables($dataTableConfig)->toArray();
    }

    /**
      * Delete $botFlow record and return response
      *
      * @param  object $inputData
      *
      * @return  mixed
      *---------------------------------------------------------------- */

    public function deleteBotFlow($botFlow)
    {
        // Check if $botFlow deleted
        if ($botFlow->deleteIt()) {
            // if deleted
            return true;
        }
        // if failed to delete
        return false;
    }

    /**
      * Store new botFlow record and return response
      *
      * @param  array $inputData
      *
      * @return  mixed
      *---------------------------------------------------------------- */

    public function storeBotFlow($inputData)
    {
        // prepare data to store
        $keyValues = [
            'title',
            'start_trigger',
            'status' => 2, // unpublished / inactive
            'vendors__id' => getVendorId(),
        ];
        return $this->storeIt($inputData, $keyValues);
    }

    /**
     * Update flow data links etc which is in JSON
     *
     * @param [type] $botFlowId
     * @param [type] $updateData
     * @return bool|int
     */
    function updateBotFlowData($botFlowId, $updateData) {
        return $this->primaryModel::where('_id', $botFlowId)->update($updateData);
    }
}
