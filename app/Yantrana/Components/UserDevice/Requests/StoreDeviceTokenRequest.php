<?php
/**
* StoreDeviceTokenRequest.php - Request file
*
* This file is part of the UserDevice component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\UserDevice\Requests;

use App\Yantrana\Base\BaseRequest;
use Illuminate\Validation\Rule;

class StoreDeviceTokenRequest extends BaseRequest
{
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
        return [
            'device_token' => 'required|string|max:255',
            'device_id' => 'required|string|max:255',
            'device_type' => 'required|string|max:20'
        ];
    }
}
