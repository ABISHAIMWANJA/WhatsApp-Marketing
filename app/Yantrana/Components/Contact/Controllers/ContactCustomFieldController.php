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
* ContactCustomFieldController.php - Controller file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Controllers;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Base\BaseController;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Support\CommonPostRequest;
use App\Yantrana\Components\Contact\ContactCustomFieldEngine;

class ContactCustomFieldController extends BaseController
{       /**
     * @var  ContactCustomFieldEngine $contactCustomFieldEngine - ContactCustomField Engine
     */
    protected $contactCustomFieldEngine;

    /**
      * Constructor
      *
      * @param  ContactCustomFieldEngine $contactCustomFieldEngine - ContactCustomField Engine
      *
      * @return  void
      *-----------------------------------------------------------------------*/
    public function __construct(ContactCustomFieldEngine $contactCustomFieldEngine)
    {
        $this->contactCustomFieldEngine = $contactCustomFieldEngine;
    }


    /**
      * list of CustomField
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function showCustomFieldView()
    {
        validateVendorAccess('manage_contacts', 'add_edit_delete_custom_contact_fields');
        // load the view
        return $this->loadView('contact.contact-custom-field.custom-field-list');
    }
    /**
      * list of CustomField
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function prepareCustomFieldList()
    {
        validateVendorAccess('manage_contacts', 'add_edit_delete_custom_contact_fields');
        // respond with dataTables preparations
        return $this->contactCustomFieldEngine->prepareCustomFieldDataTableSource();
    }

    /**
        * CustomField process delete
        *
        * @param  mix $contactCustomFieldIdOrUid
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function processCustomFieldDelete($contactCustomFieldIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactCustomFieldEngine->processCustomFieldDelete($contactCustomFieldIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
      * CustomField create process
      *
      * @param  object BaseRequest $request
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function processCustomFieldCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            "input_name" => [
                "required",
                "alpha_dash",
                Rule::unique('contact_custom_fields')->where(fn (Builder $query) => $query->where('vendors__id', getVendorId()))
            ],
            "input_type" => "required",
        ]);
        // ask engine to process the request
        $processReaction = $this->contactCustomFieldEngine->processCustomFieldCreate($request->all());
        // get back with response
        return $this->processResponse($processReaction);
    }

    /**
      * CustomField get update data
      *
      * @param  mix $contactCustomFieldIdOrUid
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function updateCustomFieldData($contactCustomFieldIdOrUid)
    {
        validateVendorAccess('manage_contacts');
        // ask engine to process the request
        $processReaction = $this->contactCustomFieldEngine->prepareCustomFieldUpdateData($contactCustomFieldIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
      * CustomField process update
      *
      * @param  mix @param  mix $contactCustomFieldIdOrUid
      * @param  object BaseRequest $request
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function processCustomFieldUpdate(BaseRequest $request)
    {
        validateVendorAccess('manage_contacts');
        // process the validation based on the provided rules
        $request->validate([
            'contactCustomFieldIdOrUid' => 'required',
            "input_name" => [
                "required",
                "alpha_dash",
                Rule::unique('contact_custom_fields')->where(fn (Builder $query) => $query->where('vendors__id', getVendorId()))->ignore($request->input_name, 'input_name')
            ],
            "input_type" => "required",
        ]);
        // ask engine to process the request
        $processReaction = $this->contactCustomFieldEngine->processCustomFieldUpdate($request->get('contactCustomFieldIdOrUid'), $request->all());
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
}
