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
* ContactGroupRepository.php - Repository file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Repositories;

use App\Yantrana\Base\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use App\Yantrana\Components\Contact\Models\ContactGroupModel;
use App\Yantrana\Components\Contact\Interfaces\ContactGroupRepositoryInterface;

class ContactGroupRepository extends BaseRepository implements ContactGroupRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = ContactGroupModel::class;

    /**
     * Fetch group datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchGroupDataTableSource($status)
    {
        if ($status == "archived") {
            $status = 5;
        } else {
            $status = 1;
        }
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'title',
                'description',
            ],
        ];

        // get Model result for dataTables
        $query = $this->primaryModel::where([
           'vendors__id' => getVendorId()
        ])->where(function (Builder $query) use (&$status) {
            $query->where('status', $status);
            if($status == 1) {
                $query->orWhereNull('status');
            }
        });
        return $query->dataTables($dataTableConfig)->toArray();
    }

    /**
     * Delete $group record and return response
     *
     * @param  object  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function deleteGroup($group)
    {
        // Check if $group deleted
        if ($group->deleteIt()) {
            // if deleted
            return true;
        }

        // if failed to delete
        return false;
    }

    /**
     * Store new group record and return response
     *
     * @param  array  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function storeGroup($inputData)
    {
        // prepare data to store
        $keyValues = [
            'title',
            'description',
            'vendors__id' => getVendorId(),
            'status' => 1,
        ];

        return $this->storeIt($inputData, $keyValues);
    }

    /**
     * Delete the selected contacts group based on uids provided
     * for the logged in vendor
     *
     * @param array $contactUids
     * @param integer|null $vendorId
     * @return mixed
     */
    public function deleteSelectedContactGroups(array  $contactGroupsUids, int|null $vendorId = null)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId ?: getVendorId()
        ])->whereIn('_uid', $contactGroupsUids)->delete();
    }

    /**
     * Get active groups
     *
     * @return void
     */
    function getActiveGroups($vendorId = null) {
        $status = 1;
        $query = $this->primaryModel::where([
            'vendors__id' => $vendorId ?: getVendorId()
         ])->where(function (Builder $query) use (&$status) {
             $query->where('status', $status);
             if($status == 1) {
                 $query->orWhereNull('status');
             }
         });
         return $query->get();
    }

    /**
     * Fetch Contact Group List Paginated Data
     *
     * @return void
     */
    public function fetchContactGroupListPaginatedData() 
    {
        $paginateCount = request()->get('page_size') ?? 100;
        $searchTerm = request()->get('search_term');

        return $this->primaryModel::where('vendors__id', getVendorId())
            ->orderBy('created_at', 'desc')
            ->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            })
            ->paginate($paginateCount);
    }
}
