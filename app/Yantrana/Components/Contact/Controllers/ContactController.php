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
 * ContactController.php - Controller file
 *
 * This file is part of the Contact component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Controllers;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use Illuminate\Support\Facades\Gate;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\Contact\ContactEngine;

class ContactController extends BaseController
{
    /**
     * @var ContactEngine - Contact Engine
     */
    protected $contactEngine;

    /**
     * Constructor
     *
     * @param  ContactEngine  $contactEngine  - Contact Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(ContactEngine $contactEngine)
    {
        $this->contactEngine = $contactEngine;
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showContactView($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        $contactsRequiredEngineResponse = $this->contactEngine->prepareContactRequiredData($groupUid);

        // load the view
        return $this->loadView('contact.list', $contactsRequiredEngineResponse->data());
    }

    /**
     * list of Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareContactList($groupUid = null)
    {
        validateVendorAccess('manage_contacts');
        // respond with dataTables preparations
        return $this->contactEngine->prepareContactDataTableSource($groupUid);
    }

    /**
     * Contact process delete
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactDelete($contactIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_contacts', 'delete_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactDelete($contactIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Contact process remove from group
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactRemoveFromGroup($contactIdOrUid, $groupUid, BaseRequest $request)
    {

        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactRemove($contactIdOrUid, $groupUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Selected Contacts delete process
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function selectedContactsDelete(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'selected_contacts' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processSelectedContactsDelete($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Process Delete All Contacts
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function deleteAllContact(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');

        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        // ask engine to process the request
        $processReaction = $this->contactEngine->processDeleteAllContact($request->all());

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Selected Contacts delete process
     *
     * @param  BaseRequest  $request
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function assignGroupsToSelectedContacts(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        $request->validate([
            'selected_contacts' => 'required|array',
            'selected_groups' => 'required|array'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignGroupsToSelectedContacts($request);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact create process
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts', 'add_edit_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'language_code' => 'nullable|alpha_dash',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'max_digits:20',
                'min:1',
                'doesnt_start_with:+,0',
                Rule::unique('contacts', 'wa_id')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'email' => 'nullable|email',
        ]);

        if (str_starts_with($request->get('phone_number'), '0') or str_starts_with($request->get('phone_number'), '+')) {
            return $this->processResponse(2, __tr('Mobile number should be numeric value without prefixing 0 or +'));
        }

        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactCreate($request->all());

        // get back with response
        return $this->processResponse($processReaction);
    }
    /**
     * Contact create process by API
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiProcessContactCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            // 'first_name' => 'required',
            // 'last_name' => 'required',
            // 'country' => 'required',
            'language_code' => 'nullable|alpha_dash',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'min:1',
                'doesnt_start_with:+,0',
                Rule::unique('contacts', 'wa_id')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'email' => 'nullable|email',
        ]);
        abortIf(str_starts_with($request->get('phone_number'), '0') or str_starts_with($request->get('phone_number'), '+'), null, 'phone number should be numeric value without prefixing 0 or +');
        // ask engine to process the request
        $inputData = $request->all();
        $inputData['country'] = getCountryIdByName($inputData['country'] ?? null);
        $processReaction = $this->contactEngine->processContactCreate($inputData);
        $contact = $processReaction->data('contact');
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $contact?->_uid,
            'first_name' => $contact?->first_name,
            'last_name' => $contact?->last_name,
            'phone_number' => $contact?->wa_id,
            'language_code' => $contact?->language_code,
            'country' => $contact?->country?->name,
        ]);
    }

    /**
     * API Contact process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiProcessContactUpdate(BaseRequest $request, $vendorUid, $phoneNumber)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'email' => 'nullable|email',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactUpdate($phoneNumber, $request->all());
        if ($processReaction->success()) {
            $contact = $processReaction->data('contact');
            // get back with response
            return $this->processApiResponse($processReaction, [
                'contact_uid' => $contact?->_uid,
            ]);
        }
        return $this->processApiResponse($processReaction, [
            'contact_uid' => $processReaction->data('contactIdOrUid'),
        ]);
    }

    /**
     * API Assign Team Member to Contact
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function apiAssignTeamMemberToContact(BaseRequest $request, $vendorUid)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'username_or_email' => 'required',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'min:1',
                'doesnt_start_with:+,0'
            ]
        ]);
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignChatUser($request);
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API Get Contact List
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetContactList()
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiPrepareContactListData();
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API Get Contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetContact()
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiPrepareContact();
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API Get Contact Group List
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetContactGroupList()
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiPrepareContactGroupListData();
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API Get Contact Labels and Tags List
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetContactLabelsAndTagsList()
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiPrepareContactLabelsAndTagsListData();
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction, $processReaction->data());
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API assign group to contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiAssignGroupsToContact(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules

        $request->validate([
            'contacts_uid' => 'required|array|min:1',
            "groups_uid" => 'required|array|min:1'
        ]);
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->processAssignGroupsToSelectedContacts($request);
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction);
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API assign group to contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiUnassignGroupsFromContact(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules

        $request->validate([
            'contacts_uid' => 'required|array|min:1',
            "groups_uid" => 'required|array|min:1'
        ]);
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiProcessUnassignGroupsFromContacts($request);
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction);
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API assign labels to contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiAssignLabelsToContact(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules

        $request->validate([
            'contacts_uid' => 'required|array|min:1',
            "labels_uid" => 'required|array|min:1'
        ]);
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiProcessAssignLabelsToContacts($request);
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction);
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * API unassign labels from contact
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiUnassignLabelsFromContact(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules

        $request->validate([
            'contacts_uid' => 'required|array|min:1',
            "labels_uid" => 'required|array|min:1'
        ]);
        
        // ask engine to process the request
        $processReaction = $this->contactEngine->apiProcessUnassignLabelsFromContacts($request);
        if ($processReaction->success()) {
            // get back with response
            return $this->processApiResponse($processReaction);
        }

        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * Contact get update data
     *
     * @param  mix  $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateContactData($contactIdOrUid)
    {
        validateVendorAccess([
            'messaging', 
            'assigned_chats_only',
            'manage_contacts'
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareContactUpdateData($contactIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact process update
     *
     * @param  mix @param  mix $contactIdOrUid
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function processContactUpdate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts', 'add_edit_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required',
            'email' => 'nullable|email',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processContactUpdate($request->get('contactIdOrUid'), $request->all());

        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Toggle AI Bot for COntact
     *
     * @param  int|string $contactIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function toggleAiBot(BaseRequest $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        // ask engine to process the request
        $processReaction = $this->contactEngine->processToggleAiBot($contactIdOrUid);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Export Contacts
     *
     * @param string $exportType
     * @return file
     */
    public function exportContacts($exportType = null, $fileType = null)
    {

        validateVendorAccess('manage_contacts', 'export_contacts');
        if ($fileType == 'csv') {
            return $this->contactEngine->processExportCSVContacts($exportType);
        }
        return $this->contactEngine->processExportContacts($exportType);
    }

    /**
     * Import Contacts
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function importContacts(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts', 'import_contacts');
        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'document_name' => 'required'
        ]);
        return $this->processResponse(
            $this->contactEngine->processImportContacts($request),
            [],
            [],
            true
        );
    }

    /**
     * Import Contacts
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function abortImportContacts(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_contacts');
        // restrict demo user
        if (isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        return $this->processResponse(
            $this->contactEngine->processAbortImportContacts($request),
            [],
            [],
            true
        );
    }

    /**
     * Contact process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function assignChatUser(BaseRequest $request)
    {
        validateVendorAccess(['messaging', 'manage_contacts']);
        
        $isBulkAction = data_get($request, 'bulk_action');
        
        if ($isBulkAction == true) {
            // process the validation based on the provided rules
            $request->validate([
                'contactIdOrUid' => 'string',
            ]);
            // ask engine to process the request
            $processReaction = $this->contactEngine->processAssignTeamMemberInBulk($request->all());
        } else {
            // process the validation based on the provided rules
            $request->validate([
                'contactIdOrUid' => 'required|uuid',
            ]);
            // ask engine to process the request
            $processReaction = $this->contactEngine->processAssignChatUser($request);
        }
        
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Contact notes process update
     *
     * @param  object BaseRequest $request
     * @return json object
     *---------------------------------------------------------------- */
    public function updateNotes(BaseRequest $request)
    {
        validateVendorAccess('messaging');
        // process the validation based on the provided rules
        $request->validate([
            'contactIdOrUid' => 'required|uuid',
            // 'contact_notes' => 'nullable',
        ]);
        // ask engine to process the request
        $processReaction = $this->contactEngine->processUpdateNotes($request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get all the labels
     *
     * @param [type] $contactUid
     * @return void
     */
    public function getLabels($contactUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->getLabelsData($contactUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Get all the labels api request
     *
     * @param [type] $contactUid
     * @return void
     */
    public function getLabelsForApi($contactUid)
    {
        validateVendorAccess('messaging');
        $processReaction = $this->contactEngine->getLabelsDataForApi($contactUid);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Create new label for vendor
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function createLabel(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'title' => [
                'required',
                'max:45',
                Rule::unique('labels')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            'text_color' => [
                'nullable',
                'string',
                'max:10',
            ],
            'bg_color' => [
                'nullable',
                'string',
                'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->createLabelProcess($request);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Update label for vendor
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function updateLabel(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'labelUid' => [
                'required',
                'uuid'
            ],
            'title' => [
                'required',
                'max:45',
                Rule::unique('labels')->where(fn(Builder $query) => $query->where('vendors__id', getVendorId()))->ignore($request->labelUid, '_uid')
            ],
            'text_color' => [
                'nullable',
                'string',
                'max:10',
            ],
            'bg_color' => [
                'nullable',
                'string',
                'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->processUpdateLabel($request);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Assign labels to contact
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    public function assignContactLabels(BaseRequestTwo $request)
    {
        validateVendorAccess('messaging');
        $request->validate([
            'contactUid' => [
                'required',
                'uuid',
            ],
            'contact_labels' => [
                'nullable',
                'array',
                // 'max:10',
            ],
        ]);
        $processReaction = $this->contactEngine->assignContactLabelsProcess($request);
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Delete label
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function deleteLabelProcess(BaseRequestTwo $request, $labelUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'labelUid' => $request->labelUid
        ]);
        $request->validate([
            'labelUid' => [
                'required',
                'uuid',
            ],
        ]);
        $processReaction = $this->contactEngine->processDeleteLabel($labelUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Block contact
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function processContactBlock(BaseRequestTwo $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'contactIdOrUid' => $request->contactIdOrUid
        ]);
        $request->validate([
            'contactIdOrUid' => [
                'required',
                'uuid',
            ],
        ]);

        $processReaction = $this->contactEngine->processBlockContact($contactIdOrUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Unblock contact
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function processContactUnblock(BaseRequestTwo $request, $contactIdOrUid)
    {
        validateVendorAccess('messaging');
        $request->merge([
            'contactIdOrUid' => $request->contactIdOrUid
        ]);
        $request->validate([
            'contactIdOrUid' => [
                'required',
                'uuid',
            ],
        ]);

        $processReaction = $this->contactEngine->processUnblockContact($contactIdOrUid);
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
    * Prepare team member list data
    *
    * @return  json object
    *---------------------------------------------------------------- */

    public function prepareTeamMemberList($contactIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareTeamMemberListData($contactIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
    * Get Contact filter support data
    *
    * @return  json object
    *---------------------------------------------------------------- */

    public function getContactFilterSupportData()
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactEngine->prepareContactFilterSupportData();
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
    * Store contact filter data
    *
    * @return  json object
    *---------------------------------------------------------------- */

    public function processStoreContactFilter(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        
        if (!$request->has('clear_filter')) {
            $request->validate([
                'email' => 'nullable|email',
                'msg_start_date' => 'nullable|required_with:msg_end_date|before_or_equal:msg_end_date|date',
                'msg_end_date' => 'nullable|required_with:msg_start_date|after_or_equal:msg_start_date|date'
            ]);
        }

        // ask engine to process the request
        $processReaction = $this->contactEngine->storeContactFilterProcess($request->all());
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * App API Prepare Contact Add Support Data
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function appApiPrepareContactAddSupportData()
    {
        validateVendorAccess('manage_contacts');
        $processReaction = $this->contactEngine->prepareContactRequiredData();

        $processReaction['data']['countries'] = getCountryPhoneCodes();
        $processReaction['data']['languages'] = include app_path('Yantrana/Support/languages.php');;

        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * App API Get Contact Edit Data
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetContactEditData($contactIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        $processReaction = $this->contactEngine->prepareContactUpdateData($contactIdOrUid);
        
        if ($processReaction->success()) {
            $processReaction['data']['countries'] = getCountryPhoneCodes();
            $processReaction['data']['languages'] = include app_path('Yantrana/Support/languages.php');;
        }

        return $this->processResponse($processReaction, [], [], true);
    }
}
