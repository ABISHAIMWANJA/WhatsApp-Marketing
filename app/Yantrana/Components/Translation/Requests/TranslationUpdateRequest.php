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
* TranslationUpdateRequest.php - Request file
*
* This file is part of the Translation component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Translation\Requests;

use App\Yantrana\Base\BaseRequest;

class TranslationUpdateRequest extends BaseRequest
{
    /**
     * Loosely sanitize fields.
     *------------------------------------------------------------------------ */
    protected $looseSanitizationFields = [];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     *-----------------------------------------------------------------------*/
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the add author client post request.
     *
     * @return bool
     *-----------------------------------------------------------------------*/
    public function rules()
    {
        $rules = [
            'message_id' => 'required',
            'message_str' => '',
            'language_id' => 'required|alpha_dash',
        ];

        return $rules;
    }
}
