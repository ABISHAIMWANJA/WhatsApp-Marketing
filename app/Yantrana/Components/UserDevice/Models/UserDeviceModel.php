<?php
/**
* UserDeviceModel.php - Model file
*
* This file is part of the Notification component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\UserDevice\Models;

use App\Yantrana\Base\BaseModel;

class UserDeviceModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'user_devices';

    /**
     * @var array - The attributes that should be casted to native types..
     */
    protected $casts = [
        'id' => 'integer',
        'users__id' => 'integer',
    ];
}
