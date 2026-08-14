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
* VendorUser.php - Model file
*
* This file is part of the Vendor component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Vendor\Models;

use App\Yantrana\Base\BaseModel;

class VendorUserModel extends BaseModel
{
    /**
     * @var  string $table - The database table used by the model.
     */
    protected $table = "vendor_users";

/**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '__data' => 'array'
    ];

    /**
     * Let the system knows Text columns treated as JSON
     *
     * @var array
     *----------------------------------------------------------------------- */
    protected $jsonColumns = [
        '__data' => [
            'permissions' => 'array:extend',
        ],
    ];

    /**
     * @var  array $fillable - The attributes that are mass assignable.
     */
    protected $fillable = [
    ];
}