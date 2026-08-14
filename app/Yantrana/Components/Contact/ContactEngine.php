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
 * ContactEngine.php - Main component file
 *
 * This file is part of the Contact component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact;

use XLSXWriter;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Facades\DB;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use App\Yantrana\Components\User\Repositories\UserRepository;
use App\Yantrana\Support\Country\Repositories\CountryRepository;
use App\Yantrana\Components\Contact\Repositories\LabelRepository;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;
use App\Yantrana\Components\Contact\Interfaces\ContactEngineInterface;
use App\Yantrana\Components\Contact\Repositories\ContactGroupRepository;
use App\Yantrana\Components\Contact\Repositories\ContactLabelRepository;
use App\Yantrana\Components\Contact\Repositories\GroupContactRepository;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppApiService;
use App\Yantrana\Components\Contact\Repositories\ContactCustomFieldRepository;

class ContactEngine extends BaseEngine implements ContactEngineInterface
{
    /**
     * @var ContactRepository - Contact Repository
     */
    protected $contactRepository;

    /**
     * @var ContactGroupRepository - ContactGroup Repository
     */
    protected $contactGroupRepository;

    /**
     * @var GroupContactRepository - ContactGroup Repository
     */
    protected $groupContactRepository;

    /**
     * @var ContactCustomFieldRepository - ContactGroup Repository
     */
    protected $contactCustomFieldRepository;
    /**
     * @var UserRepository - User Repository
     */
    protected $userRepository;
    /**
     * @var LabelRepository - Label Repository
     */
    protected $labelRepository;
    /**
     * @var ContactLabelRepository - Contact Label Repository
     */
    protected $contactLabelRepository;

    /**
     * @var WhatsAppApiService - WhatsApp API Service
     */
    protected $whatsAppApiService;

    /**
     * @var WhatsAppServiceEngine - WhatsAppService Engine
     */
    protected $whatsAppServiceEngine;

    /**
     * Constructor
     *
     * @param  ContactRepository  $contactRepository  - Contact Repository
     * @param  ContactGroupRepository  $contactGroupRepository  - ContactGroup Repository
     * @param  GroupContactRepository  $groupContactRepository  - Group Contacts Repository
     * @param  ContactCustomFieldRepository  $contactCustomFieldRepository  - Contacts Custom  Fields Repository
     * @param  UserRepository  $userRepository  - User Fields Repository
     * @param  LabelRepository  $labelRepository  - Labels Repository
     * @param  ContactLabelRepository  $contactLabelRepository  - Contact Labels Repository
     * @param  WhatsAppApiService  $whatsAppApiService  - WhatsApp API Service
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        ContactRepository $contactRepository,
        ContactGroupRepository $contactGroupRepository,
        GroupContactRepository $groupContactRepository,
        ContactCustomFieldRepository $contactCustomFieldRepository,
        UserRepository $userRepository,
        LabelRepository $labelRepository,
        ContactLabelRepository $contactLabelRepository,
        WhatsAppApiService $whatsAppApiService,
        WhatsAppServiceEngine $whatsAppServiceEngine
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactGroupRepository = $contactGroupRepository;
        $this->groupContactRepository = $groupContactRepository;
        $this->contactCustomFieldRepository = $contactCustomFieldRepository;
        $this->userRepository = $userRepository;
        $this->labelRepository = $labelRepository;
        $this->contactLabelRepository = $contactLabelRepository;
        $this->whatsAppApiService = $whatsAppApiService;
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
    }

    /**
     * Contact datatable source
     *
     * @return array|Object
     *---------------------------------------------------------------- */
    public function prepareContactDataTableSource($contactGroupUid = null)
    {
        $groupContactIds = [];
        // if for specific group
        if ($contactGroupUid) {
            $vendorId = getVendorId();
            $contactGroup = $this->contactGroupRepository->fetchIt([
                '_uid' => $contactGroupUid,
                'vendors__id' => $vendorId,
            ]);
            if (!__isEmpty($contactGroup)) {
                $groupContacts = $this->groupContactRepository->fetchItAll([
                    'contact_groups__id' => $contactGroup->_id,
                ]);
                $groupContactIds = $groupContacts->pluck('contacts__id')->toArray();
            }
        }
        $contactCollection = $this->contactRepository->fetchContactDataTableSource($groupContactIds, $contactGroupUid);

        $listOfCountries = getCountryPhoneCodes();
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'first_name',
            'last_name',
            'language_code',
            'whatsapp_opt_out' => function ($rowData) {
                return $rowData['whatsapp_opt_out'] ? __tr('Opted Out') : __tr('Opted In');
            },
            'disable_ai_bot' => function ($rowData) {
                return $rowData['disable_ai_bot'] ? __tr('Disabled') : __tr('Enabled');
            },
            'country_name' => function ($rowData) use (&$listOfCountries) {
                return Arr::get($listOfCountries, $rowData['countries__id'] . '.name');
            },
            'phone_number' => function ($rowData) {
                return maskString($rowData['wa_id'], 'phone');
            },
            'is_direct_message_delivery_window_opened' => function ($rowData) {
                return (! __isEmpty($rowData['last_incoming_message']) and (Carbon::parse(data_get($rowData, 'last_incoming_message.messaged_at'))->diffInHours() < 24));
            },
            'email' => function ($rowData) {
                return maskString($rowData['email'], 'email');
            },
            'is_blocked' => function ($rowData) {
                return (!__isEmpty($rowData['wa_blocked_at']));
            },
            'created_at' => function ($rowData) {
                return formatDateTime($rowData['created_at']);
            },
            'groups' => function ($rowData) {
                // Extract 'title' from each group using array_column
                $titles = array_unique(array_column($rowData['groups'], 'title'));
                // Return all titles as a comma-separated string, or 'No Groups' if empty
                return !__isEmpty($titles) ? implode(', ', $titles) : '-';
            },
        ];

        $filterData = getVendorSettings('contact_advance_filter_data', getUserUID());
        $contactCount = $contactCollection['total'];
        updateClientModels([
            'isFilterApplied' => (!__isEmpty($filterData)) ? true : false,
            'contactCount' => $contactCount,
            'countString' => __tr('Total __contactCount__ contact(s) found.', ['__contactCount__' => $contactCount])
        ]);

        // prepare data for the DataTables
        return $this->dataTableResponse($contactCollection, $requireColumns);
    }

    /**
     * Contact delete process
     *
     * @param  mix  $contactIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processContactDelete($contactIdOrUid)
    {
        // fetch the record
        $contact = $this->contactRepository->fetchIt($contactIdOrUid);
        // check if the record found
        if (__isEmpty($contact)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Contact not found'));
        }

        if (getVendorSettings('test_recipient_contact') == $contact->_uid) {
            return $this->engineFailedResponse([], __tr('Record set as Test Contact for Campaign, Set another contact for test before deleting it.'));
        }

        // ask to delete the record
        if ($this->contactRepository->deleteIt($contact)) {
            // if successful
            return $this->engineSuccessResponse([], __tr('Contact deleted successfully'));
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Contact'));
    }
    /**
     * Contact remove process
     *
     * @param  mix  $contactIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processContactRemove($contactIdOrUid, $groupUid)
    {
        $currentGroup = $this->contactGroupRepository->fetchIt($groupUid);
        // fetch the record
        $contact = $this->contactRepository->fetchIt($contactIdOrUid);
        // check if the record found
        if (__isEmpty($contact)) {
            // if not found
            return $this->engineResponse(18, null, __tr('Contact not found'));
        }

        // ask to delete the record
        if ($this->groupContactRepository->removeFromAssignedGroup($contact['_id'], $currentGroup->_id)) {
            // if successful
            return $this->engineSuccessResponse([], __tr('Contact remove successfully'));
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to remove Contact'));
    }
    /**
     * Contact delete process
     *
     * @param  BaseRequest  $request
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processSelectedContactsDelete($request)
    {
        $selectedContactUids = $request->get('selected_contacts');
        $message = '';
        // check for test number
        if (in_array(getVendorSettings('test_recipient_contact'), $selectedContactUids)) {
            $message .= __tr('However one of these contact is set as Test Contact, which can not be deleted.');
            if (($key = array_search(getVendorSettings('test_recipient_contact'), $selectedContactUids)) !== false) {
                unset($selectedContactUids[$key]);
            }
            if (empty($selectedContactUids)) {
                return $this->engineFailedResponse([], __tr('As selected is test contact it can not be deleted.'));
            }
        }
        if (empty($selectedContactUids)) {
            return $this->engineFailedResponse([], __tr('Nothing to delete'));
        }

        $isContactRemoveOrDeleted = false;
        $groupUid = $request->get('group_uid');
        $message = '';
        // Check if group uid exists
        if (!__isEmpty($groupUid)) {
            $currentGroup = $this->contactGroupRepository->fetchIt($groupUid);

            // Check if group exists
            if (__isEmpty($currentGroup)) {
                return $this->engineFailedResponse([], __tr('Group does not exists.'));
            }

            $selectedContactIds = $this->contactRepository->fetchItAll($selectedContactUids, [], '_uid')->pluck('_id');

            if ($this->groupContactRepository->removeGroupContacts($currentGroup->_id, $selectedContactIds)) {
                return $this->engineSuccessResponse([
                    'reloadDatatableId' => '#lwContactList'
                ], __tr('Contacts removed successfully.'));
            }
        }

        // ask to delete the record
        if ($this->contactRepository->deleteSelectedContacts($selectedContactUids)) {
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwContactList'
            ], __tr('Contacts deleted successfully.') . $message);
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to delete Contacts'));
    }

    /**
     * Process Delete All Contact
     *
     * @param  BaseRequest  $request
     *
     * @return array
     *---------------------------------------------------------------- */
    public function processDeleteAllContact($inputData)
    {
        $inputData = array_merge([
            'group_id' => null,
        ], $inputData);
        $isContactsDeleted = false;
        // Check if group contact id exists or not
        if (__isEmpty($inputData['group_id'])) {
            $testContactUid = getVendorSettings('test_recipient_contact');
            // Delete All Contacts except test contact
            if ($this->contactRepository->deleteAllContact(getVendorId(), $testContactUid)) {
                $isContactsDeleted = true;
            }
        } elseif (!__isEmpty($inputData['group_id'])) {
            $contactGroup = $this->contactGroupRepository->fetchIt($inputData['group_id']);
            // Check if contact group exists
            if (__isEmpty($contactGroup)) {
                return $this->engineFailedResponse([], __tr('Contact group does not exists.'));
            }
            if ($this->groupContactRepository->deleteGroupContactByGroupId($contactGroup->_id)) {
                $isContactsDeleted = true;
            }
        }
        if ($isContactsDeleted) {
            // if successful
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwContactList'
            ], __tr('All Contacts deleted successfully.'));
        }
        // if failed to delete
        return $this->engineResponse(14, [], __tr('Nothing to delete.'));
    }

    /**
     * Contact create
     *
     * @param  array  $inputData
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function processContactCreate($inputData)
    {
        $vendorId = getVendorId();
        // check the feature limit
        $vendorPlanDetails = vendorPlanDetails('contacts', $this->contactRepository->countIt([
            'vendors__id' => $vendorId
        ]), $vendorId);
        if (!$vendorPlanDetails['is_limit_available']) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }

        $customInputFields = isset($inputData['custom_input_fields']) ? $inputData['custom_input_fields'] : [];
        $customInputFieldUidsAndValues = [];
        // ask to add record
        if ($contactCreated = $this->contactRepository->storeContact($inputData)) {
            // if external api request
            if ($contactCreated and isExternalApiRequest()) {
                // prepare group ids needs to be assign to the contact
                $contactGroupsTitles = array_filter(array_unique(explode(',', $inputData['groups'] ?? '') ?? []));
                if (!empty($contactGroupsTitles)) {
                    // prepare group titles needs to be assign to the contact
                    $groupsToBeAdded = $this->contactGroupRepository->fetchItAll($contactGroupsTitles, [], 'title', [
                        'where' => [
                            'vendors__id' => $vendorId
                        ]
                    ]);
                    $groupsToBeCreatedTitles = array_diff($contactGroupsTitles, $groupsToBeAdded->pluck('title')->toArray());
                    $groupsToBeCreated = [];
                    if (!empty($groupsToBeCreatedTitles)) {
                        foreach ($groupsToBeCreatedTitles as $groupsToBeCreatedTitle) {
                            if (strlen($groupsToBeCreatedTitle) > 255) {
                                abortIf(strlen($groupsToBeCreatedTitle) > 1, null, __tr('Group title should not be greater than 255 characters'));
                            }
                            $groupsToBeCreated[] = [
                                'title' => $groupsToBeCreatedTitle,
                                'vendors__id' => $vendorId,
                                'status' => 1,
                            ];
                        }
                        if (!empty($groupsToBeCreated)) {
                            $newlyCreatedGroupIds = $this->contactGroupRepository->storeItAll($groupsToBeCreated, true);
                            if (!empty($newlyCreatedGroupIds)) {
                                $newlyCreatedGroups = $this->contactGroupRepository->fetchItAll(array_values($newlyCreatedGroupIds));
                                if (!__isEmpty($groupsToBeAdded)) {
                                    $groupsToBeAdded->merge($newlyCreatedGroups);
                                }
                            }
                        }
                    }
                    $assignGroups = [];
                    // prepare to assign if needed
                    if (! empty($groupsToBeAdded)) {
                        foreach ($groupsToBeAdded as $groupToBeAdded) {
                            if ($groupToBeAdded->vendors__id != $vendorId) {
                                continue;
                            }
                            $assignGroups[] = [
                                'contact_groups__id' => $groupToBeAdded->_id,
                                'contacts__id' => $contactCreated->_id,
                            ];
                        }
                        $this->groupContactRepository->storeItAll($assignGroups);
                    }
                }

                // custom fields from External API
                $customInputFields = isset($inputData['custom_fields']) ? $inputData['custom_fields'] : [];
                // check if custom fields
                if (!empty($customInputFields)) {
                    $customInputFieldsFromDb = $this->contactCustomFieldRepository->fetchItAll(array_keys(
                        $customInputFields
                    ), [], 'input_name', [
                        'where' => [
                            'vendors__id' => $vendorId
                        ]
                    ])->keyBy('input_name');
                    // loop though items
                    foreach ($customInputFields as $customInputFieldKey => $customInputFieldValue) {
                        $customInputFieldFromDb = null;
                        if (isset($customInputFieldsFromDb[$customInputFieldKey])) {
                            $customInputFieldFromDb = $customInputFieldsFromDb[$customInputFieldKey];
                        }
                        // if invalid
                        if (!$customInputFieldFromDb or ($customInputFieldFromDb->vendors__id != $vendorId)) {
                            continue;
                        }
                        // if data verified
                        $customInputFieldUidsAndValues[] = [
                            'contact_custom_fields__id' => $customInputFieldFromDb->_id,
                            'contacts__id' => $contactCreated->_id,
                            'field_value' => $customInputFieldValue,
                        ];
                    }
                }
                if (!empty($customInputFieldUidsAndValues)) {
                    $this->contactCustomFieldRepository->storeCustomValues($customInputFieldUidsAndValues);
                }

                // call webhook
                dispatchVendorWebhook($vendorId, [
                    'contact' => array_merge([
                        'status' => 'new',
                        'phone_number' => $contactCreated->wa_id,
                        'uid' => $contactCreated->_uid,
                    ], $contactCreated->only([
                        'first_name',
                        'last_name',
                        'email',
                        'language_code',
                    ]), [
                        'country' => $contactCreated->country?->name,
                    ]),
                    'message' => [],
                    'whatsapp_webhook_payload' => []
                ]);

                return $this->engineSuccessResponse([
                    'contact' => $contactCreated
                ], __tr('Contact created'));
            }
            if (!empty($inputData['contact_groups'])) {
                // prepare group ids needs to be assign to the contact
                $groupsToBeAdded = $this->contactGroupRepository->fetchItAll($inputData['contact_groups'], [], '_id');
                $assignGroups = [];
                // prepare to assign if needed
                if (! empty($groupsToBeAdded)) {
                    foreach ($groupsToBeAdded as $groupToBeAdded) {
                        if ($groupToBeAdded->vendors__id != $vendorId) {
                            continue;
                        }
                        $assignGroups[] = [
                            'contact_groups__id' => $groupToBeAdded->_id,
                            'contacts__id' => $contactCreated->_id,
                        ];
                    }
                    $this->groupContactRepository->storeItAll($assignGroups);
                }
            }
            // check if custom fields
            if (!empty($customInputFields)) {
                $customInputFieldsFromDb = $this->contactCustomFieldRepository->fetchItAll(array_keys(
                    $customInputFields
                ), [], '_uid', [
                    'where' => [
                        'vendors__id' => $vendorId
                    ]
                ])->keyBy('_uid');
                // loop though items
                foreach ($inputData['custom_input_fields'] as $customInputFieldKey => $customInputFieldValue) {
                    $customInputFieldFromDb = null;
                    if (isset($customInputFieldsFromDb[$customInputFieldKey])) {
                        $customInputFieldFromDb = $customInputFieldsFromDb[$customInputFieldKey];
                    }
                    // if invalid
                    if (!$customInputFieldFromDb or ($customInputFieldFromDb->vendors__id != $vendorId)) {
                        continue;
                    }
                    // if data verified
                    $customInputFieldUidsAndValues[] = [
                        'contact_custom_fields__id' => $customInputFieldFromDb->_id,
                        'contacts__id' => $contactCreated->_id,
                        'field_value' => $customInputFieldValue,
                    ];
                }
            }
            if (!empty($customInputFieldUidsAndValues)) {
                $this->contactCustomFieldRepository->storeCustomValues($customInputFieldUidsAndValues);
            }
            return $this->engineSuccessResponse([], __tr('Contact added.'));
        }
        return $this->engineFailedResponse([], __tr('Contact not added.'));
    }

    /**
     * Contact prepare update data
     *
     * @param  mix  $contactIdOrUid
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function prepareContactUpdateData($contactIdOrUid)
    {
        $contact = $this->contactRepository->with(['groups', 'customFieldValues', 'country'])->fetchIt($contactIdOrUid);
        // Check if $contact not exist then throw not found
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }
        $contact->wa_id = maskString($contact->wa_id, 'phone');
        $contact->email = maskString($contact->email, 'phone');
        $existingGroupIds = $contact->groups->pluck('_id')->toArray();
        $contactArray = $contact->toArray();

        $vendorContactCustomFields = $this->contactCustomFieldRepository->fetchItAll([
            'vendors__id' => $contact->vendors__id,
        ]);

        return $this->engineSuccessResponse(array_merge($contactArray, [
            'existingGroupIds' => json_encode($existingGroupIds),
        ], [
            'vendorContactCustomFields' => $vendorContactCustomFields
        ]));
    }

    /**
     * Process toggle ai bot for contact
     *
     * @param  mixed  $contactIdOrUid
     * @return array
     *---------------------------------------------------------------- */
    public function processToggleAiBot($contactIdOrUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->with('groups')->fetchIt([
            '_uid' => $contactIdOrUid,
            'vendors__id' => $vendorId,
        ]);
        // Check if $contact not exist then throw not found
        // exception
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }
        $isAiBotDisabled = $contact->disable_ai_bot ? 0 : 1;
        if ($this->contactRepository->updateIt($contact, [
            'disable_ai_bot' => $isAiBotDisabled
        ])) {
            updateClientModels([
                'isAiChatBotEnabled' => !$isAiBotDisabled
            ]);
            if (!$isAiBotDisabled) {
                return $this->engineSuccessResponse([], __tr('AI bot enabled for this contact.'));
            }
            return $this->engineSuccessResponse([], __tr('AI bot disabled for this contact.'));
        }
        return $this->engineResponse(14, [], __tr('AI bot disabled for this contact.'));
    }
    /**
     * Contact process update
     *
     * @param  mixed  $contactIdOrUid
     * @param  array  $inputData
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function processContactUpdate($contactIdOrUid, $inputData)
    {
        $vendorId = getVendorId();
        $contactWhereClause = [
            'vendors__id' => $vendorId,
        ];
        // if api request
        if (isExternalApiRequest()) {
            $contactWhereClause['wa_id'] = $contactIdOrUid;
        } else {
            $contactWhereClause['_uid'] = $contactIdOrUid;
        }
        $contact = $this->contactRepository->with('groups')->fetchIt($contactWhereClause);
        // Check if $contact not exist then throw not found
        // exception
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }
        $contactIdOrUid = $contact->_uid;
        $updateData = [];
        if (isExternalApiRequest()) {
            if (array_key_exists('enable_ai_bot', $inputData)) {
                $updateData['disable_ai_bot'] = $inputData['enable_ai_bot'] ? 0 : 1;
            }
            if (array_key_exists('whatsapp_opt_out', $inputData)) {
                $updateData['whatsapp_opt_out'] = $inputData['whatsapp_opt_out'] ? 1 : null;
            }
        } else {
            $updateData = [
                'whatsapp_opt_out' => (array_key_exists('whatsapp_opt_out', $inputData) and $inputData['whatsapp_opt_out']) ? 1 : null,
                'disable_ai_bot' => (array_key_exists('enable_ai_bot', $inputData) and $inputData['enable_ai_bot']) ? 0 : 1,
                'disable_reply_bot' => (array_key_exists('enable_reply_bot', $inputData) and $inputData['enable_reply_bot']) ? 0 : 1,
            ];
        }
        if (array_key_exists('first_name', $inputData)) {
            $updateData['first_name'] = $inputData['first_name'];
        }
        if (array_key_exists('last_name', $inputData)) {
            $updateData['last_name'] = $inputData['last_name'];
        }
        if (array_key_exists('language_code', $inputData)) {
            $updateData['language_code'] = $inputData['language_code'];
        }
        if (array_key_exists('email', $inputData)) {
            $updateData['email'] = $inputData['email'];
        }
        if (array_key_exists('country', $inputData)) {
            $updateData['countries__id'] = isExternalApiRequest() ? findRequestedCountryId($inputData['country']) : $inputData['country'];
        }
        $customInputFields = array_key_exists('custom_input_fields', $inputData) ? $inputData['custom_input_fields'] : [];
        $customInputFieldUidsAndValues = [];
        // if external api request
        if (isExternalApiRequest() and isset($inputData['groups']) and $inputData['groups']) {
            // prepare group ids needs to be assign to the contact
            $contactGroupsTitles = array_filter(array_unique(explode(',', $inputData['groups'] ?? '') ?? []));
            if (!empty($contactGroupsTitles)) {
                // prepare group titles needs to be assign to the contact
                $groupsToBeAdded = $this->contactGroupRepository->fetchItAll($contactGroupsTitles, [], 'title', [
                    'where' => [
                        'vendors__id' => $vendorId
                    ]
                ]);

                $groupsToBeCreatedTitles = array_diff($contactGroupsTitles, $groupsToBeAdded->pluck('title')->toArray());
                $groupsToBeCreated = [];
                if (!empty($groupsToBeCreatedTitles)) {
                    foreach ($groupsToBeCreatedTitles as $groupsToBeCreatedTitle) {
                        if (strlen($groupsToBeCreatedTitle) > 255) {
                            abortIf(strlen($groupsToBeCreatedTitle) > 1, null, __tr('Group title should not be greater than 255 characters'));
                        }
                        $groupsToBeCreated[] = [
                            'title' => $groupsToBeCreatedTitle,
                            'vendors__id' => $vendorId,
                            'status' => 1,
                        ];
                    }
                    if (!empty($groupsToBeCreated)) {
                        $newlyCreatedGroupIds = $this->contactGroupRepository->storeItAll($groupsToBeCreated, true);
                        if (!empty($newlyCreatedGroupIds)) {
                            $newlyCreatedGroups = $this->contactGroupRepository->fetchItAll(array_values($newlyCreatedGroupIds), [], '_id');
                            if (!__isEmpty($groupsToBeAdded)) {
                                $groupsToBeAdded->merge($newlyCreatedGroups);
                                $isUpdated = true;
                            }
                        }
                    }
                }
            }
            $inputData['contact_groups'] = !__isEmpty($groupsToBeAdded) ? $groupsToBeAdded->pluck('_id')->toArray() : [];
        }
        // extract exiting group ids
        $existingGroupIds = $contact->groups->pluck('_id')->toArray();
        // prepare group ids needs to be assign to the contact
        $groupsToBeAddedIds = array_diff($inputData['contact_groups'] ?? [], $existingGroupIds);
        // prepare group ids needs to be remove from the contact
        $groupsToBeDeleted = array_diff($existingGroupIds, $inputData['contact_groups'] ?? []);
        $isUpdated = false;
        // process to delete if needed
        if (! empty($groupsToBeDeleted)) {
            if ($this->groupContactRepository->deleteAssignedGroups($groupsToBeDeleted, $contact->_id)) {
                $isUpdated = true;
            }
        }

        if (isExternalApiRequest()) {
            $customInputFields = array_key_exists('custom_fields', $inputData) ? $inputData['custom_fields'] : [];
            // check if custom fields
            if (!empty($customInputFields)) {
                $customInputFieldsFromDb = $this->contactCustomFieldRepository->fetchItAll(array_keys($customInputFields), [], 'input_name', [
                    'where' => [
                        'vendors__id' => $vendorId
                    ]
                ])->keyBy('input_name');
                // loop though items
                foreach ($inputData['custom_fields'] as $customInputFieldKey => $customInputFieldValue) {
                    $customInputFieldFromDb = null;
                    if (isset($customInputFieldsFromDb[$customInputFieldKey])) {
                        $customInputFieldFromDb = $customInputFieldsFromDb[$customInputFieldKey];
                    }
                    // if invalid
                    if (!$customInputFieldFromDb or ($customInputFieldFromDb->vendors__id != $vendorId)) {
                        continue;
                    }
                    // if data verified
                    $customInputFieldUidsAndValues[] = [
                        'contact_custom_fields__id' => $customInputFieldFromDb->_id,
                        'contacts__id' => $contact->_id,
                        'field_value' => $customInputFieldValue,
                    ];
                }
            }
            if (!empty($customInputFieldUidsAndValues)) {
                if ($customFieldsUpdated = $this->contactCustomFieldRepository->storeCustomValues($customInputFieldUidsAndValues, 'contact_custom_fields__id', [
                    'key' => 'contacts__id',
                    'value' => $contact->_id,
                ])) {
                    $isUpdated = true;
                }
            }
        }
        // prepare to assign if needed
        if (! empty($groupsToBeAddedIds)) {
            // prepare group ids needs to be assign to the contact
            $groupsToBeAdded = $this->contactGroupRepository->fetchItAll($groupsToBeAddedIds, [], '_id');
            $assignGroups = [];
            foreach ($groupsToBeAdded as $groupToBeAdded) {
                if ($groupToBeAdded->vendors__id != $vendorId) {
                    continue;
                }
                $assignGroups[] = [
                    'contact_groups__id' => $groupToBeAdded->_id,
                    'contacts__id' => $contact->_id,
                ];
            }
            if ($this->groupContactRepository->storeItAll($assignGroups)) {
                $isUpdated = true;
            }
        }
        // Check if Contact updated
        if ($this->contactRepository->updateIt($contact, $updateData)) {
            $isUpdated = true;
        }

        // check if custom fields
        if (!isExternalApiRequest() and !empty($customInputFields)) {
            $customInputFieldsFromDb = $this->contactCustomFieldRepository->fetchItAll(array_keys($customInputFields), [], '_uid')->keyBy('_uid');
            // loop though items
            foreach ($inputData['custom_input_fields'] as $customInputFieldKey => $customInputFieldValue) {
                $customInputFieldFromDb = null;
                if (isset($customInputFieldsFromDb[$customInputFieldKey])) {
                    $customInputFieldFromDb = $customInputFieldsFromDb[$customInputFieldKey];
                }
                // if invalid
                if (!$customInputFieldFromDb or ($customInputFieldFromDb->vendors__id != $vendorId)) {
                    continue;
                }
                // if data verified
                $customInputFieldUidsAndValues[] = [
                    'contact_custom_fields__id' => $customInputFieldFromDb->_id,
                    'contacts__id' => $contact->_id,
                    'field_value' => $customInputFieldValue,
                ];
            }
        }
        if (!empty($customInputFieldUidsAndValues)) {
            if ($customFieldsUpdated = $this->contactCustomFieldRepository->storeCustomValues($customInputFieldUidsAndValues, 'contact_custom_fields__id', [
                'key' => 'contacts__id',
                'value' => $contact->_id,
            ])) {
                $isUpdated = true;
            }
        }

        if ($isUpdated) {

            if (isExternalApiRequest()) {
                // call webhook
                dispatchVendorWebhook($vendorId, [
                    'contact' => array_merge([
                        'status' => 'updated',
                        'phone_number' => $contact->wa_id,
                        'uid' => $contact->_uid,
                    ], $contact->only([
                        'first_name',
                        'last_name',
                        'email',
                        'language_code',
                    ]), [
                        'country' => $contact->country?->name,
                    ]),
                    'message' => [],
                    'whatsapp_webhook_payload' => []
                ]);
            }

            return $this->engineSuccessResponse(
                [
                    'contactIdOrUid' => $contactIdOrUid,
                    'contact' => $contact->fresh(),
                ],
                __tr('Contact details updated.')
            );
        }

        return $this->engineResponse(14, [
            'contactIdOrUid' => $contactIdOrUid,
        ], __tr('Nothing to update contact information.'));
    }

    /**
     * Prepare Contact Required data
     *
     * @param string|null $groupUid
     * @return EnginResponse
     */
    public function prepareContactRequiredData($groupUid = null)
    {
        $vendorId = getVendorId();

        if ($groupUid) {
            $group = $this->contactGroupRepository->fetchIt([
                '_uid' => $groupUid,
                'vendors__id' => $vendorId,
            ]);
            abortIf(__isEmpty($group));
        }

        $vendorContactCustomFields = $this->contactCustomFieldRepository->fetchItAll([
            'vendors__id' => $vendorId,
        ]);
        // contact groups
        $vendorContactGroups = $this->contactGroupRepository->fetchItAll([
            'vendors__id' => $vendorId,
        ]);

        return $this->engineSuccessResponse([
            'groupUid' => $groupUid,
            'vendorContactGroups' => $vendorContactGroups,
            'vendorContactCustomFields' => $vendorContactCustomFields,
        ]);
    }

    /**
     * Export Template with or without Data
     *
     * @param string $exportType
     * @return Download File
     */
    public function processExportContacts($exportType = 'blank')
    {
        $header = [];
        $vendorId = getVendorId();
        $header = array_merge($header, [
            'First Name' => 'string',
            'Last Name' => 'string',
            'Mobile Number' => 'string',
            'Language Code' => 'string',
            'Country' => 'string',
            'Email' => 'string',
            'Groups' => 'string',
        ]);
        // required data like fields and groups
        $contactsRequiredData = $this->prepareContactRequiredData();
        // get vendor custom fields
        $vendorContactCustomFields = $contactsRequiredData->data('vendorContactCustomFields');
        // create header array
        foreach ($vendorContactCustomFields as $vendorContactCustomField) {
            $header[$vendorContactCustomField->input_name] = 'string';
        }
        $data = [];
        // create temp path for store excel file
        $tempFile = tempnam(sys_get_temp_dir(), "exported_contacts_{$vendorId}.xlsx");
        $writer = new XLSXWriter();
        $writer->writeSheetHeader('Contacts', $header);
        if ($exportType == 'data') {
            if (isDemo() and isDemoVendorAccount()) {
                abort(403, __tr('Exporting Contacts data has been disabled for demo'));
            }
            // country repository
            $countryRepository = new CountryRepository();
            $countries = $countryRepository->fetchItAll([
                [
                    'phone_code',
                    '!=',
                    0
                ],
                [
                    'phone_code',
                    '!=',
                    null
                ],
            ], ['_id', 'name'])->keyBy('_id')->toArray();
            // contacts
            $this->contactRepository->getAllContactsForTheVendorLazily($vendorId, function (object $contact) use (&$countries, &$writer) {
                $dataItem = [
                    $contact->first_name,
                    $contact->last_name,
                    // phone number
                    $contact->wa_id,
                    $contact->language_code,
                    $countries[$contact->countries__id]['name'] ?? null,
                    $contact->email,
                ];
                // group
                if ($contact->groups) {
                    $groupItems = [];
                    foreach ($contact->groups as $group) {
                        $groupItems[] = $group->title;
                    }
                    $dataItem[] = implode(',', $groupItems);
                    unset($groupItems);
                }
                // custom fields
                if ($contact->customFieldValues) {
                    foreach ($contact->customFieldValues as $customFieldValue) {
                        $dataItem[] = $customFieldValue->field_value;
                    }
                }
                // write to sheet
                $writer->writeSheetRow('Contacts', $dataItem);
                unset($dataItem);
            });
        }
        // write to file
        $writer->writeToFile($tempFile);
        // file name
        $dateTime = str_slug(now()->format('Y-m-d-H-i-s'));
        // get back with response
        return response()->download($tempFile, "contacts-{$exportType}-{$dateTime}.xlsx", [
            'Content-Transfer-Encoding: binary',
            'Content-Type: application/octet-stream',
        ])->deleteFileAfterSend();
    }

    public function processExportCSVContacts($exportType = 'blank')
    {
        $vendorId = getVendorId();

        $header = [
            'First Name',
            'Last Name',
            'Mobile Number',
            'Language Code',
            'Country',
            'Email',
            'Groups',
        ];

        // Required data like fields and groups
        $contactsRequiredData = $this->prepareContactRequiredData();

        // Custom fields
        $vendorContactCustomFields = $contactsRequiredData->data('vendorContactCustomFields');
        foreach ($vendorContactCustomFields as $customField) {
            $header[] = $customField->input_name;
        }

        // Temp CSV path
        $tempFile = tempnam(sys_get_temp_dir(), "exported_contacts_{$vendorId}.csv");
        $fp = fopen($tempFile, 'w');
        // Write UTF-8 BOM so Excel recognizes Arabic properly
        fwrite($fp, "\xEF\xBB\xBF");
        // Write header row
        fputcsv($fp, $header);

        if ($exportType === 'data') {
            if (isDemo() && isDemoVendorAccount()) {
                abort(403, __tr('Exporting Contacts data has been disabled for demo'));
            }

            $countryRepository = new CountryRepository();
            $countries = $countryRepository->fetchItAll([
                ['phone_code', '!=', 0],
                ['phone_code', '!=', null],
            ], ['_id', 'name'])->keyBy('_id')->toArray();

            // Stream contact rows
            $this->contactRepository->getAllContactsForTheVendorLazily($vendorId, function (object $contact) use (&$countries, $fp) {
                $row = [
                    $contact->first_name,
                    $contact->last_name,
                    $contact->wa_id,
                    $contact->language_code,
                    $countries[$contact->countries__id]['name'] ?? '',
                    $contact->email,
                ];
                // groups
                if ($contact->groups) {
                    $groupItems = [];
                    foreach ($contact->groups as $group) {
                        $groupItems[] = $group->title;
                    }
                    $row[] = implode(',', array_unique($groupItems));
                    unset($groupItems);
                }
                // custom fields
                if ($contact->customFieldValues) {
                    foreach ($contact->customFieldValues as $customFieldValue) {
                        $row[] = $customFieldValue->field_value;
                    }
                }

                fputcsv($fp, array_map(function ($value) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto'); // ensure UTF-8
                    // Only wrap long numeric values
                    return (string) (((is_numeric($value) && strlen($value) >= 11)
                        ? '="' . $value . '"'
                        : $value) ?: '');
                }, $row));
            });
        }

        fclose($fp);

        $dateTime = str_slug(now()->format('Y-m-d-H-i-s'));

        return response()->download($tempFile, "contacts-{$exportType}-{$dateTime}.csv", [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Transfer-Encoding' => 'binary',
        ])->deleteFileAfterSend();
    }


    /**
     * Import contacts using Excel sheet
     *
     * @param BaseRequest|string $request OR document name
     * @return EngineResponse
     */
    public function processImportContacts($request, $freshRequest = true)
    {
        // Initialize database connection with optimizations
        DB::connection()->disableQueryLog();
        DB::connection()->unsetEventDispatcher();
        $existingContactImportRequest = getVendorSettings('contacts_import_process_data');
        $documentName = $request->get('document_name');
        $totalRows = 0;
        if ($existingContactImportRequest) {
            sleep(1); // wait for a while
            if ($documentName !== 'existing') {
                deleteTempUploadedFile($documentName);
                return $this->engineFailedResponse([], __tr('Existing import contacts request already in progress, please wait until it is completed OR abort it.'));
            }
            $documentName = $existingContactImportRequest['document_name'] ?? null;
            $totalRows = $existingContactImportRequest['total_rows'] ?? 0;
        }
        $vendorId = getVendorId();
        $vendorPlanDetails = vendorPlanDetails(null, null, $vendorId);

        if (!$vendorPlanDetails->hasActivePlan()) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }

        $filePath = getTempUploadedFile($documentName);
        if (!$filePath or !file_exists($filePath)) {
            $this->processAbortImportContacts();
            return $this->engineFailedResponse([], __tr('File not found, please upload again.'));
        }
        $countryRepository = new CountryRepository();
        $countries = $countryRepository->fetchItAll([], ['_id', 'name', 'iso_code', 'name_capitalized', 'iso3_code', 'phone_code'])->keyBy('name')->toArray();
        $contactsRequiredData = $this->prepareContactRequiredData();

        $vendorContactGroups = $contactsRequiredData->data('vendorContactGroups')?->keyBy('title')->toArray() ?: [];
        $vendorContactCustomFields = $contactsRequiredData->data('vendorContactCustomFields')?->keyBy('input_name')->toArray() ?: [];
        $duplicateEntries = [];
        $botSettingsForNewContacts = getVendorSettings('default_enable_flowise_ai_bot_for_users', null, null, $vendorId) ? 0 : 1;

        // $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($filePath);
        $contactsToUpdate = [];
        $customFieldsToUpdate = [];
        $contactGroupsToUpdate = [];
        $phoneNumbers = [];
        $numbersToProcess = [];
        $numbersToProcessCount = 0;
        $customFieldStructure = [];
        $vendorAllContactsCount = $this->contactRepository->countIt([
            'vendors__id' => $vendorId,
        ]);
        $contactsPerRequest = getAppSettings('contacts_import_limit_per_request') ?: 5000;
        $chunkSize = 500;
        $newContactsCount = 0;
        $processingRows = 0;
        $dataStructure = ['first_name', 'last_name', 'wa_id', 'language_code', 'countries__id', 'email'];
        $processedRows = (int) Arr::get($existingContactImportRequest, 'processed_rows', 0);
        try {
            // just for checks
            if ($freshRequest) {
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                        if ($rowIndex === 1) {
                            continue;
                        }
                        if (!$existingContactImportRequest) {
                            $totalRows++;
                        }
                        if ($processedRows > $rowIndex) {
                            continue; // skip already processed rows
                        }
                        if ($numbersToProcessCount <= ($chunkSize + 5)) {
                            $cells = $row->getCells();
                            if (count($cells) < 3) {
                                return $this->engineFailedResponse([], __tr('Invalid file format. Please upload a valid contacts file.'));
                            }
                            foreach ($cells as $cellIndex => $cell) {
                                $cellValue = e($cell->getValue());
                                $cellValue = trim((string)$cellValue);
                                if ($cellIndex <= 5) {
                                    $field = $dataStructure[$cellIndex] ?? null;
                                    if ($field === 'wa_id') {
                                        // Remove wrapping like ="123456"
                                        $cellValue = preg_replace('/^="?([^"]+)"?$/', '$1', $cellValue);
                                        // Keep only numbers
                                        $cellValue = preg_replace('/\D/', '', $cellValue);
                                        if (!$cellValue || !is_numeric($cellValue)) {
                                            if (!$existingContactImportRequest) {
                                                $totalRows--;
                                            }
                                            continue 2;
                                        }
                                        $cellValue = cleanDisplayPhoneNumber($cellValue);
                                        $numbersToProcess[] = $cellValue;
                                        $numbersToProcessCount++;
                                        continue 2; //
                                    }
                                }
                            }
                        } else if ($existingContactImportRequest) {
                            break; // stop processing if we reach the limit
                        }
                    }
                    break; // count only first sheet
                }
            }

            $vendorAllContacts = $this->contactRepository->with(['groups', 'customFieldValues'])->fetchItAll(
                $numbersToProcess,
                ['_id', '_uid', 'wa_id', 'disable_ai_bot'],
                'wa_id',
                [
                    // for this vendor
                    'where' => [
                        'vendors__id' => $vendorId,
                    ]
                ]
            )?->keyBy('wa_id')?->toArray() ?: [];
            if (!$existingContactImportRequest) {
                if ($totalRows > $contactsPerRequest) {
                    $reader->close();
                    return $this->engineFailedResponse([], __tr('Please upload maximum of __contactsPerRequest__ records in single upload', ['__contactsPerRequest__' => $contactsPerRequest]));
                }
                if ($totalRows > $vendorAllContactsCount) {
                    $vendorPlanDetails = vendorPlanDetails('contacts', $totalRows, $vendorId);
                    if (!$vendorPlanDetails['is_limit_available']) {
                        $reader->close();
                        return $this->engineFailedResponse([], $vendorPlanDetails->message());
                    }
                }
                setVendorSettings(
                    'internals',
                    [
                        'contacts_import_process_data' => [
                            'total_rows' => $totalRows,
                            'document_name' => $documentName,
                            'processed_rows' => 0,
                            'new_contacts_processed' => 0,
                            'duplicate_entries' => 0,
                            'progress' => 0,
                            'started_at' => now(),
                            'updated_at' => now(),
                        ]
                    ]
                );
                updateClientModels([
                    // 'progress' => $progressCount,
                    'existingImportRequestData' => [
                        'progress' => 0.01,
                        'estimatedRemainingTime' => 0,
                        'progressCountFormatted' => __tr(0.01 . '%'),
                    ]
                ]);
                $reader->close();
                return $this->engineSuccessResponse([
                    'progressCount' => 0.01,
                    'estimatedRemainingTime' => 0,
                    'progressCountFormatted' => __tr(0.01 . '%'),
                ], __tr('Import is in progress ...'));
            }
            // end first checks
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = $row->getCells();
                    if ($rowIndex === 1) {
                        foreach ($cells as $cellIndex => $cell) {
                            $customFieldStructure[$cellIndex] = trim((string) e($cell->getValue()));
                        }
                        continue;
                    }
                    if ($processedRows > $rowIndex) {
                        continue; // skip already processed rows
                    }

                    $contactRow = ['vendors__id' => $vendorId];
                    $waId = null;
                    $contactId = null;
                    $contactData = null;

                    foreach ($cells as $cellIndex => $cell) {
                        $cellValue = e($cell->getValue());
                        $cellValue = trim((string)$cellValue);
                        if ($cellIndex <= 5) {
                            $field = $dataStructure[$cellIndex] ?? null;
                            if ($field === 'wa_id') {
                                // Remove wrapping like ="123456"
                                $cellValue = preg_replace('/^="?([^"]+)"?$/', '$1', $cellValue);
                                // Keep only numbers
                                $cellValue = preg_replace('/\D/', '', $cellValue);
                                if (!$cellValue || !is_numeric($cellValue)) {
                                    $duplicateEntries[] = $cellValue;
                                    continue 2;
                                }

                                $cellValue = cleanDisplayPhoneNumber($cellValue);
                                if (in_array($cellValue, $phoneNumbers)) {
                                    $duplicateEntries[] = $cellValue;
                                    continue 2;
                                }
                                $contactData = ($vendorAllContacts[$cellValue] ?? []) ?: [];
                                $waId = $cellValue;
                                $contactRow['_uid'] = $contactData['_uid'] ?? (string) Str::uuid();
                                $contactRow['disable_ai_bot'] = $contactData['disable_ai_bot'] ?? $botSettingsForNewContacts;
                                if (empty($contactData)) {
                                    // $contactRow['disable_ai_bot'] = $botSettingsForNewContacts;
                                    $newContactsCount++;
                                    $phoneNumbers[] = $cellValue;
                                } else {
                                    $contactId = $contactData['_id'];
                                }

                                $contactRow[$field] = $cellValue;
                            } elseif ($field === 'countries__id') {
                                $matchedCountry = Arr::first($countries, function ($value) use ($cellValue) {
                                    return in_array(strtolower($cellValue), array_map('strtolower', array_values(Arr::only($value, ['name', 'iso_code', 'name_capitalized', 'iso3_code', 'phone_code']))));
                                });
                                $contactRow[$field] = $matchedCountry['_id'] ?? null;
                            } else {
                                $contactRow[$field] = $cellValue;
                            }
                        } elseif ($cellIndex === 6 && $contactId) {
                            $groups = explode(',', $cellValue);
                            $existingGroups = collect($contactData['groups'] ?? [])->pluck('_id')->flip();

                            foreach ($groups as $group) {
                                $group = Str::limit(trim($group), 250, '');
                                $groupId = $vendorContactGroups[$group]['_id'] ?? null;

                                if (!$groupId and $group) {
                                    $newGroup = $this->contactGroupRepository->storeIt(['title' => $group, 'vendors__id' => $vendorId]);
                                    $vendorContactGroups[$group] = $newGroup->toArray();
                                    $groupId = $newGroup->_id;
                                }

                                if ($groupId && !$existingGroups->has($groupId)) {
                                    $contactGroupsToUpdate[] = ['_uid' => (string) Str::uuid(), 'contact_groups__id' => $groupId, 'contacts__id' => $contactId];
                                }
                            }
                        } elseif ($cellIndex >= 7 && $contactId) {
                            $fieldName = $customFieldStructure[$cellIndex] ?? null;
                            $customField = $vendorContactCustomFields[$fieldName] ?? null;

                            if ($customField) {
                                $existingField = Arr::first($contactData['custom_field_values'] ?? [], fn($v) => $v['contact_custom_fields__id'] === $customField['_id']);
                                $customFieldsToUpdate[] = [
                                    '_uid' => $existingField['_uid'] ?? (string) Str::uuid(),
                                    'contact_custom_fields__id' => $customField['_id'],
                                    'contacts__id' => $contactId,
                                    'field_value' => $cellValue
                                ];
                            }
                        }
                    }
                    $processingRows++;
                    $contactsToUpdate[] = $contactRow;
                    if (count($contactsToUpdate) >= $chunkSize) {
                        $this->flushContactBatch($contactsToUpdate, $customFieldsToUpdate, $contactGroupsToUpdate, $vendorId, $vendorAllContactsCount, $newContactsCount, $freshRequest);
                        if ($freshRequest) {
                            $this->processImportContacts($request, false);
                        }
                        $progressCount = round(($rowIndex / ($totalRows ?: 1)) * 100, 2);
                        // Set vendor settings to process contacts import
                        setVendorSettings(
                            'internals',
                            [
                                'contacts_import_process_data' => [
                                    'total_rows' => $totalRows,
                                    'document_name' => $documentName,
                                    'processed_rows' => $rowIndex,
                                    'new_contacts_processed' => $newContactsCount,
                                    'duplicate_entries_count' => count($duplicateEntries),
                                    'progress' => $progressCount,
                                    'progressCountFormatted' => __tr($progressCount . '%'),
                                    'started_at' => $existingContactImportRequest['started_at'] ?? now(),
                                    'updated_at' => now(),
                                ]
                            ]
                        );

                        try {
                            $startTime = Carbon::parse($existingContactImportRequest['started_at'] ?? now()); // Or use Carbon::now() at start
                            $currentTime = Carbon::parse($existingContactImportRequest['updated_at'] ?? now());
                            $elapsedSeconds = $currentTime->diffInSeconds($startTime);
                            $averageTimePerRow = $processedRows > 0 ? $elapsedSeconds / $processedRows : 0;
                            $remainingRows = $totalRows - $processedRows;
                            $estimatedRemainingSeconds = $averageTimePerRow * $remainingRows;
                            // Format estimated time
                            $estimatedRemainingTime = $estimatedRemainingSeconds ? __tr(Carbon::now()->addSeconds($estimatedRemainingSeconds)->diffForHumans(null, true, false, 3)) : __tr('Calculating...');
                        } catch (\Throwable $th) {
                            //throw $th;
                            $estimatedRemainingTime = __tr('Calculating...');
                        }
                        updateClientModels([
                            'existingImportRequestData' => [
                                'progress' => $progressCount,
                                'estimatedRemainingTime' => $estimatedRemainingTime,
                                'progressCountFormatted' => __tr($progressCount . '%'),
                            ]
                        ]);
                        return $this->engineSuccessResponse([
                            'progressCount' => $progressCount,
                            'progressCountFormatted' => __tr($progressCount . '%'),
                        ]);
                    }
                }
                break; // count only first sheet
            }
            // flush remaining contacts
            $this->flushContactBatch($contactsToUpdate, $customFieldsToUpdate, $contactGroupsToUpdate, $vendorId, $vendorAllContactsCount, $newContactsCount, $freshRequest);
            $reader->close();
            if ($freshRequest) {
                $this->processImportContacts($request, false);
            }
            // update vendor import contact information to null
            setVendorSettings('internals', [
                'contacts_import_process_data' => null
            ]);
            updateClientModels([
                'existingImportRequestData' => [
                    'progress' => 0,
                    'progressCountFormatted' => __tr(0 . '%'),
                ]
            ]);
            // delete file
            deleteTempUploadedFile($documentName);
            $contactsImportData = getVendorSettings('contacts_import_process_data');
            unset($vendorAllContacts);
            // get back with response
            return $this->engineSuccessResponse([
                'progressCount' => 0,
            ], __tr('Contacts has been imported.'));
        } catch (\Throwable $th) {
            $this->processAbortImportContacts();
            if (config('app.debug')) {
                throw $th; // re-throw in debug mode
                return $this->engineFailedResponse([], $th->getMessage() ?: __tr('Error occurred while importing data, please check and correct data and re-upload.'));
            }
            return $this->engineFailedResponse([], __tr('Error occurred while importing data, please check and correct data and re-upload.'));
        }
    }

    /**
     * Abort the contact import process
     *
     * @return EngineResponse
     */
    public function processAbortImportContacts()
    {
        $documentName = getVendorSettings('contacts_import_process_data', 'document_name');
        setVendorSettings('internals', [
            'contacts_import_process_data' => null
        ]);
        deleteTempUploadedFile($documentName);
        // get back with response
        return $this->engineSuccessResponse([
            // 'progressCount' => 100,
        ], __tr('Aborted contact import'));
    }
    /**
     * Flush the contact batch
     *
     * @param array $contactsToUpdate
     * @param array $customFieldsToUpdate
     * @param array $contactGroupsToUpdate
     * @param int $vendorId
     * @param int $vendorAllContactsCount
     * @param int $newContactsCount
     * @return EngineResponse
     */
    private function flushContactBatch(&$contactsToUpdate, &$customFieldsToUpdate, &$contactGroupsToUpdate, $vendorId, $vendorAllContactsCount, $newContactsCount, $freshRequest = true)
    {
        if (!empty($contactsToUpdate) && $freshRequest) {
            $vendorPlanDetails = vendorPlanDetails('contacts', $vendorAllContactsCount + $newContactsCount, $vendorId);
            if (!$vendorPlanDetails['is_limit_available']) {
                $this->processAbortImportContacts();
                throw new \Exception($vendorPlanDetails['message']);
            }
            $this->contactRepository->bunchUpsert($contactsToUpdate, ['_uid']);
            $contactsToUpdate = [];
        }

        if (!empty($customFieldsToUpdate)) {
            // $this->contactCustomFieldRepository->storeCustomValues($customFieldsToUpdate, '_uid');
            $this->contactCustomFieldRepository->upsertCustomValues($customFieldsToUpdate, ['_uid']);
            $customFieldsToUpdate = [];
        }

        if (!empty($contactGroupsToUpdate)) {
            // $this->groupContactRepository->bunchInsertOrUpdate($contactGroupsToUpdate, '_uid');
            $this->groupContactRepository->bunchUpsert($contactGroupsToUpdate, ['contact_groups__id']);
            $contactGroupsToUpdate = [];
        }
        if (function_exists('gc_collect_cycles')) {
            // Collect garbage to free memory
            // This is useful when processing large data sets
            // to avoid memory leaks
            // and ensure that memory is released.
            gc_collect_cycles();
        }
    }


    /**
     * Assign User to Contact for chat
     *
     * @param BaseRequest $request
     * @return EngineResponse
     */
    public function processAssignChatUser($request)
    {
        $contactUid = null;
        $enableAiBot = data_get($request, 'enable_ai_bot');
        $enableReplyBot = data_get($request, 'enable_reply_bot');
        // if api request
        if (isExternalApiRequest()) {
            $contactData = $this->contactRepository->fetchIt([
                'wa_id' => $request->phone_number
            ]);

            // Check if contact not exists
            if (__isEmpty($contactData)) {
                return $this->engineFailedResponse([], __tr('Contact user does not exists.'));
            }

            $whereClause = [
                'email' => $request->username_or_email
            ];

            if (is_numeric($request->username_or_email)) {
                $whereClause = [
                    'mobile_number' => $request->username_or_email
                ];
            }

            // Fetch Team member
            $teamMember = $this->userRepository->fetchIt($whereClause);

            // Check if contact not exists
            if (__isEmpty($teamMember)) {
                return $this->engineFailedResponse([], __tr('Team member does not exists.'));
            }

            $contactUid = $request->contactIdOrUid = $contactData->_uid;
            $request->assigned_users_uid = $teamMember->_uid;
        }

        // check if contact uid is empty
        if (__isEmpty($contactUid)) {
            $contactUid = $request->contactIdOrUid;
        }

        $contactDetails = $this->contactRepository->fetchIt($contactUid);
        $vendorId = getVendorId();
        $systemMessageActions = [];
        if (!$request->assigned_users_uid or ($request->assigned_users_uid == 'no_one')) {

            // Check if AI bot is enable / disable from whatsapp chat
            if ($enableAiBot == $contactDetails->disable_ai_bot) {
                // Check if AI bot is enable
                if (__isEmpty($enableAiBot)) {
                    $systemMessageActions[] = [
                        'action' => 'DISABLE_AI_BOT'
                    ];
                } else {
                    $systemMessageActions[] = [
                        'action' => 'ENABLE_AI_BOT'
                    ];
                }
            }

            // Check if Reply bot is enable / disable from whatsapp chat
            if ($enableReplyBot == $contactDetails->disable_reply_bot) {
                // Check if AI bot is enable
                if (__isEmpty($enableReplyBot)) {
                    $systemMessageActions[] = [
                        'action' => 'DISABLE_REPLY_BOT'
                    ];
                } else {
                    $systemMessageActions[] = [
                        'action' => 'ENABLE_REPLY_BOT'
                    ];
                }
            }

            if ($this->contactRepository->updateIt([
                '_uid' => $request->contactIdOrUid,
                'vendors__id' => $vendorId,
            ], [
                'assigned_users__id' => null,
                'disable_ai_bot' => $enableAiBot ? null : 1,
                'disable_reply_bot' => $enableReplyBot ? null : 1
            ])) {

                if ($contactDetails->assigned_users__id != null) {

                    $systemMessageActions[] = [
                        'action' => 'UNASSIGN_TEAM_MEMBER'
                    ];
                }

                if (!__isEmpty($systemMessageActions)) {
                    foreach ($systemMessageActions as $messageAction) {
                        // Store whatsapp message log
                        storeWhatsAppLogChatHistory([
                            'status' => 'initialize',
                            'contacts__id' => $contactDetails->_id,
                            'vendors__id' => $vendorId,
                            'contact_wa_id' => $contactDetails->wa_id,
                            'is_system_message' => 1,
                            'is_incoming_message' => 0,
                            'messaged_at' => now(),
                            '__data' => [
                                'system_message_data' => [
                                    'action' => $messageAction['action'],
                                    'dynamicKey' => '__dynamicTitle__',
                                    'dynamicValue' => ''
                                ]
                            ]
                        ]);
                    }
                }

                updateClientModels([
                    'whatsappMessageLogs' => $this->whatsAppServiceEngine->contactChatData($contactDetails->_id)->data('whatsappMessageLogs'),
                ], 'prepend');

                return $this->engineSuccessResponse([], __tr('Request Successful.'));
            }
            return $this->engineFailedResponse([], __tr('Nothing to Update.'));
        }
        // get all the messaging vendor users
        $vendorMessagingUserUids = $this->userRepository->getVendorMessagingUsers($vendorId)->pluck('_uid')->toArray();
        // validate the vendor user
        if (!in_array($request->assigned_users_uid, $vendorMessagingUserUids)) {
            return $this->engineFailedResponse([], __tr('Invalid user'));
        }
        // get the user details
        $user = $this->userRepository->fetchIt([
            '_uid' => $request->assigned_users_uid,
        ]);
        if (__isEmpty($user)) {
            return $this->engineFailedResponse([], __tr('Failed to assign user'));
        }
        $systemMessageActions = [];
        // Check if AI bot is enable / disable from whatsapp chat
        if ($enableAiBot == $contactDetails->disable_ai_bot) {
            // Check if AI bot is enable
            if (__isEmpty($enableAiBot)) {
                $systemMessageActions[] = [
                    'action' => 'DISABLE_AI_BOT',
                    'dynamicValue' => ''
                ];
            } else {
                $systemMessageActions[] = [
                    'action' => 'ENABLE_AI_BOT',
                    'dynamicValue' => ''
                ];
            }
        }

        // Check if Reply bot is enable / disable from whatsapp chat
        if ($enableReplyBot == $contactDetails->disable_reply_bot) {
            // Check if AI bot is enable
            if (__isEmpty($enableReplyBot)) {
                $systemMessageActions[] = [
                    'action' => 'DISABLE_REPLY_BOT',
                    'dynamicValue' => ''
                ];
            } else {
                $systemMessageActions[] = [
                    'action' => 'ENABLE_REPLY_BOT',
                    'dynamicValue' => ''
                ];
            }
        }

        if ($this->contactRepository->updateIt([
            '_uid' => $request->contactIdOrUid,
            'vendors__id' => $vendorId,
        ], [
            'assigned_users__id' => $user->_id,
            'disable_ai_bot' => $enableAiBot ? null : 1,
            'disable_reply_bot' => $enableReplyBot ? null : 1
        ])) {

            if ($user->_id != $contactDetails->assigned_users__id) {
                $systemMessageActions[] = [
                    'action' => 'ASSIGN_TEAM_MEMBER',
                    'dynamicValue' => $user->full_name
                ];
            }

            if (!__isEmpty($systemMessageActions)) {
                foreach ($systemMessageActions as $messageAction) {
                    // Store whatsapp message log
                    storeWhatsAppLogChatHistory([
                        'status' => 'initialize',
                        'contacts__id' => $contactDetails->_id,
                        'vendors__id' => $vendorId,
                        'contact_wa_id' => $contactDetails->wa_id,
                        'is_system_message' => 1,
                        'is_incoming_message' => 0,
                        'messaged_at' => now(),
                        '__data' => [
                            'system_message_data' => [
                                'action' => $messageAction['action'],
                                'dynamicKey' => '__dynamicTitle__',
                                'dynamicValue' => $messageAction['dynamicValue']
                            ]
                        ]
                    ]);
                }
            }

            updateClientModels([
                'whatsappMessageLogs' => $this->whatsAppServiceEngine->contactChatData($contactDetails->_id)->data('whatsappMessageLogs'),
            ], 'prepend');

            return $this->engineSuccessResponse([
                'contact_uid' => $contactUid
            ],  __tr('Request Successful.'));
        }
        return $this->engineResponse(14, [], __tr('No changes'));
    }

    /**
     * Assign Groups to selected contacts
     *
     * @param BaseRequest $request
     * @return EngineResponse
     */
    public function processAssignGroupsToSelectedContacts($request)
    {
        $selectedGroups = $request->get('selected_groups');
        $selectedContacts = $request->get('selected_contacts');

        // Check if request for external API 
        if (isExternalApiRequest()) {
            $selectedContacts = $request->get('contacts_uid');
            $groupsUid = $request->get('groups_uid');
            $groupCollection = $this->contactGroupRepository->fetchItAll($groupsUid, [], '_uid');
            // Check if groups exists
            if (__isEmpty($groupCollection)) {
                return $this->engineResponse(2, [], __tr('Invalid groups uid.'));
            }
            $selectedGroups = $groupCollection->pluck('_id')->toArray();
        }

        $groups = $this->contactGroupRepository->fetchItAll($selectedGroups, [], '_id');
        $contacts = $this->contactRepository->with(['groups'])->fetchItAll($selectedContacts, [], '_uid');
        $contactGroupsToUpdate = [];
        foreach ($contacts as $contact) {
            $contactGroups = collect($contact['groups'] ?? [])->pluck('_id');
            $newGroupIds = array_diff($groups->pluck('_id')->toArray(), $contactGroups->toArray());
            foreach ($newGroupIds as $newGroupId) {
                $contactGroupsToUpdate[] = [
                    'contact_groups__id' => $newGroupId,
                    'contacts__id' => $contact->_id,
                ];
            }
        }
        if (!empty($contactGroupsToUpdate)) {
            $this->groupContactRepository->bunchInsertOrUpdate($contactGroupsToUpdate, '_uid');
            return $this->engineSuccessResponse([
                'reloadDatatableId' => '#lwContactList',
                'modalId' => '#lwAssignGroups',
            ], __tr('Groups assigned successfully.'));
        }
        return $this->engineResponse(14, [], __tr('No changes'));
    }

    /**
     * Api Process Unassign Groups From Contacts
     *
     * @param BaseRequest $request
     * @return EngineResponse
     */
    public function apiProcessUnassignGroupsFromContacts($request)
    {
        $groupsUid = $request->get('groups_uid');
        $selectedContacts = $request->get('contacts_uid');
        $groupCollection = $this->contactGroupRepository->fetchItAll($groupsUid, [], '_uid');
        // Check if groups exists
        if (__isEmpty($groupCollection)) {
            return $this->engineResponse(2, [], __tr('Invalid groups uid.'));
        }
        $contactCollection = $this->contactRepository->fetchItAll($selectedContacts, [], '_uid');
        // Check if contacts exists
        if (__isEmpty($contactCollection)) {
            return $this->engineResponse(2, [], __tr('Invalid contacts uid.'));
        }

        $groupIds = $groupCollection->pluck('_id')->toArray();
        $contactIds = $contactCollection->pluck('_id')->toArray();
        // Remove from DB
        if ($this->groupContactRepository->deleteGroupsByGroupAndContactIds($groupIds, $contactIds)) {
            return $this->engineSuccessResponse([], __tr('Groups unassigned successfully.'));
        }
        return $this->engineResponse(14, [], __tr('No changes'));
    }

    /**
     * Assign Labels to selected contacts
     *
     * @param BaseRequest $request
     * @return EngineResponse
     */
    public function apiProcessAssignLabelsToContacts($request)
    {
        $labelsUid = $request->get('labels_uid');
        $contactsUid = $request->get('contacts_uid');

        $labels = $this->labelRepository->fetchItAll($labelsUid, [], '_uid');
        // Check if contacts exists
        if (__isEmpty($labels)) {
            return $this->engineResponse(2, [], __tr('Invalid labels uid.'));
        }

        $contacts = $this->contactRepository->with(['labels'])->fetchItAll($contactsUid, [], '_uid');
        // Check if contacts exists
        if (__isEmpty($contacts)) {
            return $this->engineResponse(2, [], __tr('Invalid contacts uid.'));
        }

        $contactLabelsToUpdate = [];
        foreach ($contacts as $contact) {
            $contactLabels = collect($contact['labels'] ?? [])->pluck('_id', 'title');
            $newLabelIds = array_diff($labels->pluck('_id', 'title')->toArray(), $contactLabels->toArray());
            foreach ($newLabelIds as $labelTitle => $newLabelId) {
                $contactLabelsToUpdate[] = [
                    'labels__id' => $newLabelId,
                    'contacts__id' => $contact->_id,
                ];
                logSystemVendorChatMessage($contact, 'LABEL_ADDED', $labelTitle);
            }
        }
        if (!empty($contactLabelsToUpdate)) {
            $this->contactLabelRepository->bunchInsertOrUpdate($contactLabelsToUpdate, '_uid');
            return $this->engineSuccessResponse([], __tr('Labels assigned successfully.'));
        }
        return $this->engineResponse(14, [], __tr('No changes'));
    }

    /**
     * Unassign Labels to selected contacts
     *
     * @param BaseRequest $request
     * @return EngineResponse
     */
    public function apiProcessUnassignLabelsFromContacts($request)
    {
        $labelsUid = $request->get('labels_uid');
        $contactsUid = $request->get('contacts_uid');

        $labels = $this->labelRepository->fetchItAll($labelsUid, [], '_uid');
        // Check if contacts exists
        if (__isEmpty($labels)) {
            return $this->engineResponse(2, [], __tr('Invalid labels uid.'));
        }

        $contacts = $this->contactRepository->with(['labels'])->fetchItAll($contactsUid, [], '_uid');
        // Check if contacts exists
        if (__isEmpty($contacts)) {
            return $this->engineResponse(2, [], __tr('Invalid contacts uid.'));
        }

        $labelIds = $labels->pluck('_id')->toArray();
        $contactIds = $contacts->pluck('_id')->toArray();
        // Remove from DB
        if ($this->contactLabelRepository->deleteLabelsByLabelAndContactIds($labelIds, $contactIds)) {
            foreach ($contacts as $contact) {
                foreach ($labels as $label) {
                    logSystemVendorChatMessage($contact, 'LABEL_REMOVED', $label->title);
                }
            }
            return $this->engineSuccessResponse([], __tr('Labels unassigned successfully.'));
        }
        return $this->engineResponse(14, [], __tr('No changes'));
    }

    /**
     * Contact notes process update
     *
     * @param  BaseRequest  $request
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function processUpdateNotes($request)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->fetchIt([
            '_uid' => $request->contactIdOrUid,
            'vendors__id' => $vendorId,
        ]);
        // Check if $contact not exist then throw not found
        // exception
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }

        if ($this->contactRepository->updateIt($contact, [
            '__data' => [
                'contact_notes' => $request->contact_notes ?: '',
            ]
        ])) {
            return $this->engineSuccessResponse([], __tr('Notes updated'));
        }
        return $this->engineFailedResponse([], __tr('Notes does not updated'));
    }

    /**
     * Get all the labels
     *
     * @param string $contactUid
     * @return EngineResponse
     */
    public function getLabelsData($contactUid)
    {
        // $this->labelRepository = $labelRepository;
        // $this->contactLabelRepository = $contactLabelRepository;
        $vendorId = getVendorId();
        $listOfAllLabels = $this->labelRepository->fetchItAll([
            'vendors__id' => $vendorId
        ]);
        return $this->engineSuccessResponse([
            'contact_uid' => $contactUid,
            'listOfAllLabels' => $listOfAllLabels
        ]);
    }

    /**
     * Get all the labels for api request
     *
     * @param string $contactUid
     * @return EngineResponse
     */
    public function getLabelsDataForApi($contactUid)
    {
        $vendorId = getVendorId();
        $listOfAllLabels = $this->labelRepository->fetchItAll([
            'vendors__id' => $vendorId
        ]);
        // get the vendor users having messaging permission
        $vendorMessagingUsers = $this->userRepository->getVendorMessagingUsers($vendorId);
        return $this->engineSuccessResponse([
            'contact_uid' => $contactUid,
            'listOfAllLabels' => $listOfAllLabels,
            'vendorMessagingUsers' => $vendorMessagingUsers,
        ]);
    }
    /**
     * Create new label for the vendor
     *
     * @param BaseRequestTwo $request
     * @return EngineResponse
     */
    public function createLabelProcess($request)
    {
        $vendorId = getVendorId();
        if ($createdLabel = $this->labelRepository->storeIt([
            'vendors__id' => $vendorId,
            'title' => $request->title,
            'text_color' => $request->text_color,
            'bg_color' => $request->bg_color,
            'status' => 1,
        ])) {
            // get all the labels
            $allLabels = $this->labelRepository->fetchItAll([
                'vendors__id' => $vendorId
            ]);

            updateClientModels([
                'allLabels' => $allLabels
            ]);

            return $this->engineSuccessResponse([
                'createdLabel' => $createdLabel
            ], __tr('Label created'));
        }
        return $this->engineFailedResponse([], __tr('Failed to create label'));
    }

    /**
     * Assign contact lables
     *
     * @param BaseRequestTwo $request
     * @return EngineResponse
     */
    public function assignContactLabelsProcess($request)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->with('groups')->fetchIt([
            '_uid' => $request->contactUid,
            'vendors__id' => $vendorId,
        ]);
        // Check if $contact not exist then throw not found
        // exception
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }
        $inputData = $request->all();
        // extract exiting label ids
        $existingLabelIds = $contact->labels->pluck('_id')->toArray();
        // prepare group ids needs to be assign to the contact
        $labelsToBeAddedIds = array_diff($inputData['contact_labels'] ?? [], $existingLabelIds);
        // prepare group ids needs to be remove from the contact
        $labelsToBeDeleted = array_diff($existingLabelIds, $inputData['contact_labels'] ?? []);
        $isUpdated = false;
        $labelChatHistoryMessages = [];
        // process to delete if needed
        if (! empty($labelsToBeDeleted)) {

            $labelsToBeDeletedCollection = $this->labelRepository->fetchItAll($labelsToBeDeleted, [], '_id');
            foreach ($labelsToBeDeletedCollection as $deletedLabel) {
                $deleteHistoryMessage = $deletedLabel->title . ' label deleted from this chat.';
                storeWhatsAppLogChatHistory([
                    'status' => 'initialize',
                    'contacts__id' => $contact->_id,
                    'vendors__id' => $vendorId,
                    'contact_wa_id' => $contact->wa_id,
                    'is_system_message' => 1,
                    'is_incoming_message' => 0,
                    'messaged_at' => now(),
                    '__data' => [
                        'system_message_data' => [
                            'action' => 'LABEL_REMOVED',
                            'dynamicKey' => '__dynamicTitle__',
                            'dynamicValue' => $deletedLabel->title
                        ]
                    ]
                ]);
                $labelChatHistoryMessages[] = $deleteHistoryMessage;
            }

            if ($this->contactLabelRepository->deleteAssignedLabels($labelsToBeDeleted, $contact->_id)) {
                $isUpdated = true;
            }
        }
        // prepare to assign if needed
        if (! empty($labelsToBeAddedIds)) {
            // prepare group ids needs to be assign to the contact
            $labelsToBeAdded = $this->labelRepository->fetchItAll($labelsToBeAddedIds, [], '_id');
            $assignLabels = [];
            foreach ($labelsToBeAdded as $labelToBeAdded) {
                if ($labelToBeAdded->vendors__id != $vendorId) {
                    continue;
                }
                $assignLabels[] = [
                    'labels__id' => $labelToBeAdded->_id,
                    'contacts__id' => $contact->_id,
                ];

                storeWhatsAppLogChatHistory([
                    'status' => 'initialize',
                    'contacts__id' => $contact->_id,
                    'vendors__id' => $vendorId,
                    'contact_wa_id' => $contact->wa_id,
                    'is_system_message' => 1,
                    'is_incoming_message' => 0,
                    'messaged_at' => now(),
                    '__data' => [
                        'system_message_data' => [
                            'action' => 'LABEL_ADDED',
                            'dynamicKey' => '__dynamicTitle__',
                            'dynamicValue' => $labelToBeAdded->title
                        ]
                    ]
                ]);
            }
            if (!empty($assignLabels) and $this->contactLabelRepository->storeItAll($assignLabels)) {
                $isUpdated = true;
            }
        }
        if ($isUpdated) {
            // Update chat history on chat container
            updateClientModels([
                'whatsappMessageLogs' => $this->whatsAppServiceEngine->contactChatData($contact->_id)->data('whatsappMessageLogs'),
            ], 'prepend');

            return $this->engineSuccessResponse([], __tr('Labels updated'));
        }
        return $this->engineResponse(14, null, __tr('Nothing to update'));
    }

    /**
     * Delete label
     *
     * @param string $labelUid
     * @return EngineResponse
     */
    public function processDeleteLabel($labelUid)
    {
        $vendorId = getVendorId();
        if ($this->labelRepository->deleteIt([
            '_uid' => $labelUid,
            'vendors__id' => $vendorId
        ])) {
            // get all the labels
            $allLabels = $this->labelRepository->fetchItAll([
                'vendors__id' => $vendorId
            ]);

            updateClientModels([
                'allLabels' => $allLabels
            ]);

            return $this->engineSuccessResponse([
                'labelUid' => $labelUid
            ], __tr('Label deleted'));
        }
        return $this->engineResponse(14, null, __tr('nothing deleted'));
    }
    /**
     * Update Label
     *
     * @param BaseRequestTwo $labelUid
     * @return EngineResponse
     */
    public function processUpdateLabel($request)
    {
        $vendorId = getVendorId();
        $labelItem = $this->labelRepository->fetchIt([
            '_uid' => $request->labelUid,
            'vendors__id' => $vendorId,
        ]);
        if (__isEmpty($labelItem)) {
            return $this->engineResponse(2, null, __tr('Invalid label'));
        }
        if ($this->labelRepository->updateIt($labelItem, [
            'title' => $request->title,
            'text_color' => $request->text_color,
            'bg_color' => $request->bg_color,
        ])) {

            // get all the labels
            $allLabels = $this->labelRepository->fetchItAll([
                'vendors__id' => $vendorId
            ]);

            updateClientModels([
                'allLabels' => $allLabels
            ]);

            return $this->engineSuccessResponse([
                'labelUid' => $request->labelUid
            ], __tr('Label updated'));
        }
        return $this->engineResponse(14, null, __tr('nothing updated'));
    }

    /**
     * Block Contact
     *
     * @param BaseRequestTwo $labelUid
     * @return EngineResponse
     */
    public function processBlockContact($contactIdOrUid)
    {
        $vendorId = getVendorId();
        $contactWhereClause = [
            'vendors__id' => $vendorId,
        ];
        // if api request
        if (isExternalApiRequest()) {
            $contactWhereClause['wa_id'] = $contactIdOrUid;
        } else {
            $contactWhereClause['_uid'] = $contactIdOrUid;
        }
        $contact = $this->contactRepository->fetchIt($contactWhereClause);
        // Check if $contact not exist then throw not found
        // exception
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }

        $blockedData = $this->whatsAppApiService->blockContact($contact->wa_id);

        // block contact
        if ($blockedData and !Arr::has($blockedData, "errors")) {

            if ($updatedDAta = $this->contactRepository->updateIt($contact, [
                'wa_blocked_at' => now()
            ])) {

                updateClientModels([
                    'contact' => $contact
                ]);

                return $this->engineSuccessResponse([], __tr('Contact blocked'));
            }
        } elseif ($blockedData and Arr::has($blockedData, "errors")) {
            $errorMessage = Arr::get($blockedData, 'errors.error_data.details', __tr('Failed to block Contact'));

            return $this->engineFailedResponse([], $errorMessage);
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to block Contact'));
    }

    /**
     * Block Contact
     *
     * @param BaseRequestTwo $labelUid
     * @return EngineResponse
     */
    public function processUnblockContact($contactIdOrUid)
    {
        $vendorId = getVendorId();
        $contactWhereClause = [
            'vendors__id' => $vendorId,
        ];
        // if api request
        if (isExternalApiRequest()) {
            $contactWhereClause['wa_id'] = $contactIdOrUid;
        } else {
            $contactWhereClause['_uid'] = $contactIdOrUid;
        }
        $contact = $this->contactRepository->fetchIt($contactWhereClause);
        // Check if $contact not exist then throw not found
        // exception
        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }

        $unblockedData = $this->whatsAppApiService->unBlockContact($contact->wa_id);

        // block contact
        if ($unblockedData and !Arr::has($unblockedData, "errors")) {
            // if successful

            if ($this->contactRepository->updateIt($contact, [
                'wa_blocked_at' => null
            ])) {

                updateClientModels([
                    'contact' => $contact
                ]);

                return $this->engineSuccessResponse([], __tr('Contact unblocked'));
            }
        }

        // if failed to delete
        return $this->engineFailedResponse([], __tr('Failed to unblock Contact'));
    }

    /**
     * Prepare team member list data
     *
     * @return  array
     *---------------------------------------------------------------- */

    public function prepareTeamMemberListData($contactIdOrUid)
    {
        $contact = null;

        // Check if request for bulk action
        if ($contactIdOrUid != 'bulk_action') {
            $contact = $this->contactRepository->fetchContactWithAssignUser($contactIdOrUid);

            // Check if contact exists
            if (__isEmpty($contact)) {
                return $this->engineResponse(18, [], __tr('Contact does not exist.'), true);
            }
        }

        $teamMembers = $this->userRepository->fetchTeamMembers();

        return $this->engineResponse(1, [
            'teamMembers' => $teamMembers->toArray(),
            'userUID' => getUserUID(),
            'contact' => $contact,
            'is_bulk_action' => ($contactIdOrUid == 'bulk_action') ?? false
        ]);
    }

    /**
     * Process assign team member
     *
     * @param $inputData
     *
     * @return  array
     *---------------------------------------------------------------- */

    public function processAssignTeamMemberInBulk($inputData)
    {
        $contactUids = explode(',', $inputData['contactIdOrUid']);

        $dynamicTitle = '';
        $action = '';
        if ($inputData['assigned_users_uid'] == 'no_one') {
            $inputData['assign_user_id'] = null;
            $action = 'UNASSIGN_TEAM_MEMBER';
        } else {
            $teamMemberData = $this->userRepository->fetchIt($inputData['assigned_users_uid']);

            // Check if team member exists
            if (__isEmpty($teamMemberData)) {
                return $this->engineResponse(18, [], __tr('Team member does not exists.'));
            }
            $inputData['assign_user_id'] = $teamMemberData->_id;

            $action = 'ASSIGN_TEAM_MEMBER';
            $dynamicTitle = $teamMemberData->full_name;
        }

        $contactCollection = $this->contactRepository->fetchItAll($contactUids, [], '_uid');

        foreach ($contactCollection as $contactDetails) {
            storeWhatsAppLogChatHistory([
                'status' => 'initialize',
                'contacts__id' => $contactDetails->_id,
                'vendors__id' => getVendorId(),
                'contact_wa_id' => $contactDetails->wa_id,
                'is_system_message' => 1,
                'is_incoming_message' => 0,
                'messaged_at' => now(),
                '__data' => [
                    'system_message_data' => [
                        'action' => $action,
                        'dynamicKey' => '__dynamicTitle__',
                        'dynamicValue' => $dynamicTitle
                    ]
                ]
            ]);
        }

        if ($this->contactRepository->assignTeamMemberToContacts($contactUids, $inputData)) {
            return $this->engineResponse(1, [], __tr('Team member assigned successfully.'));
        }

        return $this->engineResponse(14, [], __tr('Nothing Update.'), true);
    }

    /**
     * Prepare Contact List Paginate Data
     *
     * @return  Object
     *---------------------------------------------------------------- */
    public function apiPrepareContactListData()
    {
        $contactListData = $this->contactRepository->fetchContactListPaginatedData();

        // if successful
        return $this->engineSuccessResponse([
            'contactList' => $contactListData
        ], __tr('Contacts List.'));
    }

    /**
     * Prepare Contact
     *
     * @return  Object
     *---------------------------------------------------------------- */
    public function apiPrepareContact()
    {
        $phoneNumberOrEmail = request()->get('phone_number_or_email');

        // Check if phone number or email exists
        if (__isEmpty($phoneNumberOrEmail)) {
            return $this->engineFailedResponse([], __tr('Please enter phone number or email.'));
        }

        $contactListData = $this->contactRepository->fetchContactByPhoneNumberOrEmail($phoneNumberOrEmail);

        // if successful
        return $this->engineSuccessResponse([
            'contact' => $contactListData
        ], __tr('Contact details for __phoneNumberOrEmail__.', [
            '__phoneNumberOrEmail__' => $phoneNumberOrEmail
        ]));
    }

    /**
     * Prepare Contact Group List Paginate Data
     *
     * @return  Object
     *---------------------------------------------------------------- */
    public function apiPrepareContactGroupListData()
    {
        $contactListData = $this->contactGroupRepository->fetchContactGroupListPaginatedData();

        // if successful
        return $this->engineSuccessResponse([
            'contactList' => $contactListData
        ], __tr('Contacts List.'));
    }

    /**
     * Prepare Contact Labels and Tags List Paginate Data
     *
     * @return  Object
     *---------------------------------------------------------------- */
    public function apiPrepareContactLabelsAndTagsListData()
    {
        $contactListData = $this->labelRepository->fetchContactLabelsAndTagsListPaginatedData();

        // if successful
        return $this->engineSuccessResponse([
            'contactList' => $contactListData
        ], __tr('Contacts List.'));
    }

    /**
     * Prepare Contact Filter Support Data
     *
     * @return  Object
     *---------------------------------------------------------------- */
    public function prepareContactFilterSupportData()
    {
        $vendorId = getVendorId();
        $filterData = getVendorSettings('contact_advance_filter_data', getUserUID());
        $vendorContactCustomFields = $this->contactCustomFieldRepository->fetchItAll([
            'vendors__id' => $vendorId
        ]);

        $allLabels = $this->labelRepository->fetchItAll([
            'vendors__id' => $vendorId
        ]);

        return $this->engineSuccessResponse([
            'teamMembers' => $this->userRepository->fetchTeamMembers(),
            'groupData' => $this->contactGroupRepository->fetchItAll(['vendors__id' => getVendorId()]),
            'vendorContactCustomFields' => $vendorContactCustomFields,
            'allLabels' => $allLabels,
            'filterData' => $filterData,
            'isFilterApplied' => (!__isEmpty($filterData)) ? true : false
        ]);
    }

    /**
     * Store contact filter process
     * 
     * @param Array $inputData
     *
     * @return  Object
     *---------------------------------------------------------------- */
    public function storeContactFilterProcess($inputData)
    {
        unset($inputData['_token']);

        $cleanInputData = $this->arrayFilterRecursive($inputData);

        // Try to filter a contact list with blank values
        if (__isEmpty($cleanInputData)) {
            return $this->engineFailedResponse([], __tr('Nothing to filter.'));
        }

        $contactAdvanceFilterData = getVendorSettings('contact_advance_filter_data');

        // Get current user uid
        $userUid = getUserUID();
        // Check if request for clear filter
        if (data_get($inputData, 'clear_filter')) {
            // If clear filter key exists then remove filter
            if (isset($contactAdvanceFilterData[$userUid])) {
                unset($contactAdvanceFilterData[$userUid]);
                // Store vendor settings
                setVendorSettings('internals', [
                    'contact_advance_filter_data' => $contactAdvanceFilterData
                ]);
            }
            return $this->engineSuccessResponse([], __tr('Filter Clear Successfully.'));
        }

        if (!__isEmpty($contactAdvanceFilterData) and is_array($contactAdvanceFilterData)) {
            $contactAdvanceFilterData[$userUid] = $cleanInputData;
        } else {
            $contactAdvanceFilterData = [
                $userUid => $cleanInputData
            ];
        }

        // if request for store filter data then store it in vendor settings
        setVendorSettings('internals', [
            'contact_advance_filter_data' => $contactAdvanceFilterData
        ]);

        // Success response
        return $this->engineSuccessResponse([], __tr('Filter Applied.'));
    }

    /**
     * Filter multidimensional array
     * 
     * @param Array $inputData
     *
     * @return  Object
     *---------------------------------------------------------------- */
    protected function arrayFilterRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            // If value is an array, clean it recursively
            if (is_array($value)) {
                $array[$key] = $this->arrayFilterRecursive($value);

                // If the sub-array became empty, remove it
                if (empty($array[$key])) {
                    unset($array[$key]);
                }
            } else {
                // If scalar value is null OR blank string, remove it
                if (is_null($value) || (is_string($value) && trim($value) === '')) {
                    unset($array[$key]);
                }
            }
        }

        return $array;
    }
}
