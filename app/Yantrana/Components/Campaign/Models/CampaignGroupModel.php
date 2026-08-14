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
* CampaignGroup.php - Model file
*
* This file is part of the Campaign component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Campaign\Models;

use App\Yantrana\Base\BaseModel;

class CampaignGroupModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'campaign_groups';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
    ];
}
