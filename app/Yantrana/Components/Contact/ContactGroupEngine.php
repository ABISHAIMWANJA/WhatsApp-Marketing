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
 * ContactGroupEngine.php - Main component file
 *
 * This file is part of the Contact component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\Contact\Interfaces\ContactGroupEngineInterface;
use App\Yantrana\Components\Contact\Repositories\ContactGroupRepository;
use App\Yantrana\Components\Campaign\Repositories\CampaignRepository;
use App\Yantrana\Components\Contact\Repositories\GroupContactRepository;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;

class ContactGroupEngine extends BaseEngine implements ContactGroupEngineInterface
{
    /**
     * @var ContactGroupRepository - ContactGroup Repository
     */
    protected $contactGroupRepository;

    /**
     * @var CampaignRepository - Campaign Repository
     */
    protected $campaignRepository;

    /**
     * @var GroupContactRepository - ContactGroup Repository
     */
    protected $groupContactRepository;

    /**
     * @var ContactRepository - Contact Repository
     */
    protected $contactRepository;

    /**
     * Constructor
     *
     * @param  ContactGroupRepository  $contactGroupRepository  - ContactGroup Repository
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        ContactGroupRepository $contactGroupRepository,
        CampaignRepository $campaignRepository,
        GroupContactRepository $groupContactRepository,
        ContactRepository $contactRepository
    ) {
        $this->contactGroupRepository = $contactGroupRepository;
        $this->campaignRepository = $campaignRepository;
        $this->groupContactRepository = $groupContactRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Group datatable source
     *
     * @return array
     *---------------------------------------------------------------- */
    public function prepareGroupDataTableSource($status)
    {
        $groupCollection = $this->contactGroupRepository->fetchGroupDataTableSource($status);
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'title',
            'description',
            'status',
        ];

        // prepare data for the DataTables
        return $this->dataTableResponse($groupCollection, $requireColumns);
    }

    /**
     * Group delete process
     *
     * @param  mix  $contactGroupIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processGroupDelete($contactGroupIdOrUid)
    {
        // fetch the record
        $group = $this->contactGroupRepository->fetchIt($contactGroupIdOrUid);
        // check if the record found
        if (__isEmpty($group)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Group not found'));
        }
        // ask to delete the record
        if ($this->contactGroupRepository->deleteIt($group)) {
            // if successful
            return $this->engineSuccessResponse([], __tr('Group deleted successfully'));
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Group'));
    }
    /**
     * Group archive process
     *
     * @param  mix  $contactGroupIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processGroupArchive($contactGroupIdOrUid)
    {
        // fetch the record
        $group = $this->contactGroupRepository->fetchIt($contactGroupIdOrUid);
        // check if the record found
        if (__isEmpty($group)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Group not found'));
        }
        // Prepare Update Package data
        $updateData = [
            'status' => 5,
        ];
        //Check if package archive
        if ($this->contactGroupRepository->updateIt($group, $updateData)) {
            return $this->engineSuccessResponse([], __tr('Group Archived successfully'));
        }

        // if failed to archive
        return $this->engineFailedResponse([], __tr('Failed to Archive Group'));
    }
    /**
     * Group archive process
     *
     * @param  mix  $contactGroupIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processGroupUnarchive($contactGroupIdOrUid)
    {
        // fetch the record
        $group = $this->contactGroupRepository->fetchIt($contactGroupIdOrUid);
        // check if the record found
        if (__isEmpty($group)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Group not found'));
        }
        // Prepare Update Package data
        $updateData = [
            'status' => 1,
        ];
        //Check if package unarchive
        if ($this->contactGroupRepository->updateIt($group, $updateData)) {
            return $this->engineSuccessResponse([], __tr('Group Unarchived successfully'));
        }

        // if failed to unarchive
        return $this->engineFailedResponse([], __tr('Failed to Unarchive Group'));
    }

    /**
     * Group create
     *
     * @param  array  $inputData
     * @return object
     *---------------------------------------------------------------- */
    public function processGroupCreate($inputData)
    {
        $transactionResponse = $this->contactGroupRepository->processTransaction(function () use ($inputData) {
            // ask to add record
            if ($newGroup = $this->contactGroupRepository->storeGroup($inputData)) {
                $campaignId = $inputData['campaign_id'] ?? null;
                $groupContactInsertData = [];
                if ($campaignId) {
                    $failedCampaignType = $inputData['failed_campaign_type'] ?? null;
                    $reCampaignType = $inputData['recampaign_type'] ?? null;
                    if ($failedCampaignType) {
                        $campaignLogCollection = $this->campaignRepository->fetchFailedCampaignByType($campaignId, $failedCampaignType);
                        foreach ($campaignLogCollection as $groupContact) {
                            if(!$groupContact->contacts__id) {
                                continue;
                            }
                            $groupContactInsertData[] = [
                                'contact_groups__id' => $newGroup->_id,
                                'contacts__id' => $groupContact->contacts__id
                            ];
                        }
                    }
                    if ($reCampaignType) {
                        $campaign = $this->campaignRepository->fetchCampaignData($campaignId);
                        
                        if ($campaign->queue_pending_messages_count > 0 or $campaign->queue_processing_messages_count > 0) {
                            return $this->contactGroupRepository->transactionResponse(2, [], __tr('A contact group is generated only when a campaign has been executed.'));
                        }

                        $messageLog = $campaign->messageLog;
                        $queueMessages = $campaign->queueMessages;
                        $campaignLogData = null;
                        switch ($reCampaignType) {
                            case 'total':
                                $campaignLogData = $this->campaignRepository->fetchTotalCampaignContacts($campaignId)->all();
                                break;

                            case 'delivered':
                                $totalRead = $messageLog->where('status', 'read')->all();
                                $totalDelivered = $messageLog->where('status', 'delivered')->all();
                                $campaignLogData = array_merge($totalRead, $totalDelivered);
                                break;

                            case 'read':
                                $campaignLogData = $messageLog->where('status', 'read')->all();
                                break;

                            case 'failed':
                                $failedQueue = $queueMessages->where('status', 2)->all();
                                $failedData = $messageLog->where('status', 'failed')->all();
                                $campaignLogData = array_merge($failedQueue, $failedData);
                                break;

                            case 'expired':
                                $campaignLogData = $queueMessages->where('status', 5)->all();
                                break;

                            case 'sent':
                                $campaignLogData = $messageLog->where('status', 'sent')->all();
                                break;

                            case 'in_queue':
                                $campaignLogData = $queueMessages->where('status', 1)->all();
                                break;

                            case 'accepted':
                                $campaignLogData = $messageLog->where('status', 'accepted')->all();
                                break;

                            default:
                                return $this->campaignRepository->transactionResponse(2, [], __tr('Something went wrong.'));
                                break;
                        }

                        if (!__isEmpty($campaignLogData)) {
                            foreach ($campaignLogData as $campaignContact) {
                                // Check if contact exists
                                if (__isEmpty($campaignContact['contacts__id'])) {
                                    continue;
                                }
                                $groupContactInsertData[] = [
                                    'contact_groups__id' => $newGroup->_id,
                                    'contacts__id' => $campaignContact['contacts__id']
                                ];
                            }
                        }
                    }
                }
                // Check if request user try to create group from contact advance filter
                if (!__isEmpty(data_get($inputData, 'request_from') == 'CONTACT_ADVANCE_FILTER')) {
                    $filteredContactData = $this->contactRepository->fetchContactDataTableSource(null, null, false);
                    if (!__isEmpty($filteredContactData)) {
                        $contactIds = $filteredContactData->pluck('_id');
                        foreach ($contactIds as $contactId) {
                            $groupContactInsertData[] = [
                                'contact_groups__id' => $newGroup->_id,
                                'contacts__id' => $contactId
                            ];
                        }
                    }
                }

                if (!__isEmpty($groupContactInsertData)) {
                    foreach (array_chunk($groupContactInsertData, 500) as $groupContactInsertDataChunk) {
                        $this->groupContactRepository->storeItAll($groupContactInsertDataChunk);
                    }
                    return $this->contactGroupRepository->transactionResponse(1, [], __tr('Group created and added contacts to it.'));
                }
                return $this->contactGroupRepository->transactionResponse(1, [], __tr('Group created.'));
            }
            return $this->contactGroupRepository->transactionResponse(2, [], __tr('Failed to create group.'));
        });

        return $this->engineResponse($transactionResponse);
    }

    /**
     * Group prepare update data
     *
     * @param  mix  $contactGroupIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function prepareGroupUpdateData($contactGroupIdOrUid)
    {
        $group = $this->contactGroupRepository->fetchIt($contactGroupIdOrUid);

        // Check if $group not exist then throw not found
        // exception
        if (__isEmpty($group)) {
            return $this->engineResponse(18, null, __tr('Group not found.'));
        }

        return $this->engineSuccessResponse($group->toArray());
    }

    /**
     * Group process update
     *
     * @param  mixed  $contactGroupIdOrUid
     * @param  array  $inputData
     * @return array
     *---------------------------------------------------------------- */
    public function processGroupUpdate($contactGroupIdOrUid, $inputData)
    {
        $group = $this->contactGroupRepository->fetchIt($contactGroupIdOrUid);

        // Check if $group not exist then throw not found
        // exception
        if (__isEmpty($group)) {
            return $this->engineResponse(18, null, __tr('Group not found.'));
        }

        $updateData = [
            'title' => $inputData['title'],
            'description' => $inputData['description'],

        ];

        // Check if Group updated
        if ($this->contactGroupRepository->updateIt($group, $updateData)) {

            return $this->engineSuccessResponse([], __tr('Group updated.'));
        }

        return $this->engineResponse(14, null, __tr('Group not updated.'));
    }
    /**
     * Contact group delete process
     *
     * @param  BaseRequest  $request
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processSelectedContactGroupsDelete($request)
    {
        $selectedContactGroupsUids = $request->get('selected_groups');

        $message = '';

        if (empty($selectedContactGroupsUids)) {
            return $this->engineFailedResponse([], __tr('Nothing to delete'));
        }
        // ask to delete the record
        if ($this->contactGroupRepository->deleteSelectedContactGroups($selectedContactGroupsUids)) {
            // if successful
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwGroupList'
            ], __tr('Groups deleted successfully.') . $message);
        }
        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Groups'));
    }

    /**
     * Contact group archive process
     *
     * @param  BaseRequest  $request
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processSelectedContactGroupsArchive($request)
    {
        $selectedContactGroupsUids = $request->get('selected_groups');
        $contactGroups = $this->contactGroupRepository->fetchItAll($request->get('selected_groups'), [], '_uid');

        $message = '';
        if (empty($selectedContactGroupsUids)) {
            return $this->engineFailedResponse([], __tr('Nothing to archive'));
        }
        $contactGroupsToUpdate = [];
        // Prepare Update Package data
        foreach ($contactGroups as $newGroup) {
            $contactGroupsToUpdate[] = [
                '_uid' => $newGroup['_uid'],
                'title' => $newGroup['title'],
                'status' => 5,
            ];
        }
        //process to archived groups
        if (!empty($contactGroupsToUpdate)) {
            $this->contactGroupRepository->bunchInsertOrUpdate($contactGroupsToUpdate, '_uid');
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwGroupList'
            ], __tr('Groups archived successfully.') . $message);
        }
        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to archive Groups'));
    }
    /**
     * Contact group unarchive process
     *
     * @param  BaseRequest  $request
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processSelectedContactGroupsUnarchive($request)
    {
        $selectedContactGroupsUids = $request->get('selected_groups');
        $contactGroups = $this->contactGroupRepository->fetchItAll($request->get('selected_groups'), [], '_uid');
        $message = '';
        if (empty($selectedContactGroupsUids)) {
            return $this->engineFailedResponse([], __tr('Nothing to unarchive'));
        }
        $contactGroupsToUpdate = [];
        // Prepare Update Package data
        foreach ($contactGroups as $newGroup) {
            $contactGroupsToUpdate[] = [
                '_uid' => $newGroup['_uid'],
                'title' => $newGroup['title'],
                'status' => 1,
            ];
        }

        //process to archived groups
        if (!empty($contactGroupsToUpdate)) {
            $this->contactGroupRepository->bunchInsertOrUpdate($contactGroupsToUpdate, '_uid');
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwGroupList'
            ], __tr('Groups unarchived successfully.') . $message);
        }
        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to unarchive Groups'));
    }
}
