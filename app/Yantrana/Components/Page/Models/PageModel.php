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
 * Page.php - Model file
 *
 * This file is part of the Page component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Page\Models;

use App\Yantrana\Base\BaseModel;

class PageModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'pages';

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        'type' => 'integer',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',        'show_in_menu',        'content',        'type',
    ];
}
