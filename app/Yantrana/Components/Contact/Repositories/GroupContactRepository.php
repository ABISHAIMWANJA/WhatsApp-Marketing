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
* GroupContactRepository.php - Repository file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Contact\Interfaces\GroupContactRepositoryInterface;
use App\Yantrana\Components\Contact\Models\GroupContactModel;

class GroupContactRepository extends BaseRepository implements GroupContactRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = GroupContactModel::class;

    /**
     * Delete Selected Assigned groups from contacts
     *
     * @param array $groupIds
     * @param int $contactId
     * @return mixed
     */
    function deleteAssignedGroups($groupIds, $contactId) {
        return $this->primaryModel::whereIn('contact_groups__id', $groupIds)->where([
            'contacts__id' => $contactId
        ])->deleteIt();
    }

    /**
     * Remove Selected contact  from assign group
     *
     * @param array $groupIds
     * @param int $contactId
     * @return mixed
     */
    function removeFromAssignedGroup($contactId,$groupId) {
        return $this->primaryModel::where('contact_groups__id', $groupId)->where([
            'contacts__id' => $contactId
        ])->deleteIt();
    }

    /**
     * Delete group contact by group Id
     *
     * @param array $groupId
     * @return mixed
     */
    function deleteGroupContactByGroupId($groupId) {
        return $this->primaryModel::where('contact_groups__id', $groupId)->deleteIt();
    }

    /**
     * Delete Selected Assigned groups from contacts
     *
     * @param array $groupIds
     * @param int $contactIds
     * @return mixed
     */
    function removeGroupContacts($groupId, $contactIds) {
        return $this->primaryModel::whereIn('contacts__id', $contactIds)->where([
            'contact_groups__id' => $groupId
        ])->deleteIt();
    }

    /**
     * Delete Selected Assigned groups from contacts
     *
     * @param array $groupIds
     * @param int $contactIds
     * @return mixed
     */
    function deleteGroupsByGroupAndContactIds($groupIds, $contactIds) {
        return $this->primaryModel::whereIn('contacts__id', $contactIds)
            ->whereIn('contact_groups__id', $groupIds)
            ->deleteIt();
    }
}
