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
* ContactCustomFieldRepository.php - Repository file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Contact\Models\ContactCustomFieldModel;
use App\Yantrana\Components\Contact\Models\ContactCustomFieldValueModel;
use App\Yantrana\Components\Contact\Interfaces\ContactCustomFieldRepositoryInterface;

class ContactCustomFieldRepository extends BaseRepository implements ContactCustomFieldRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var  object
     */
    protected $primaryModel = ContactCustomFieldModel::class;


    /**
      * Fetch customField datatable source
      *
      * @return  mixed
      *---------------------------------------------------------------- */
    public function fetchCustomFieldDataTableSource()
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'input_name',
                'input_type'

            ]
        ];
        // get Model result for dataTables
        return ContactCustomFieldModel::where([
            'vendors__id' => getVendorId()
        ])->dataTables($dataTableConfig)->toArray();
    }

    /**
      * Delete $customField record and return response
      *
      * @param  object $inputData
      *
      * @return  mixed
      *---------------------------------------------------------------- */

    public function deleteCustomField($customField)
    {
        // Check if $customField deleted
        if ($customField->deleteIt()) {
            // if deleted
            return true;
        }
        // if failed to delete
        return false;
    }

    /**
      * Store new customField record and return response
      *
      * @param  array $inputData
      *
      * @return  mixed
      *---------------------------------------------------------------- */

    public function storeCustomField($inputData)
    {
        // prepare data to store
        $keyValues = [
            'input_name',
            'input_type',
            'vendors__id' => getVendorId(),
        ];
        return $this->storeIt($inputData, $keyValues);
    }

    function storeCustomValues($values, $index = null, $whereIn = null) {
        if($index) {
            return (new ContactCustomFieldValueModel())->bunchInsertUpdate($values, $index, $whereIn);
        }
        return (new ContactCustomFieldValueModel())->prepareAndInsert($values);
    }

    function upsertCustomValues($data, $uniqueBy = []) {
        return (new ContactCustomFieldValueModel())->upsert($data, uniqueBy: $uniqueBy, update: array_keys($data[0]) ?: []);
    }
}
