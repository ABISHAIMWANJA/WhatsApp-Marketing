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
* BotReply.php - Model file
*
* This file is part of the BotReply component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\BotReply\Models;

use App\Yantrana\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Yantrana\Components\BotReply\Models\BotFlowModel;

class BotReplyModel extends BaseModel
{
    /**
     * @var  string $table - The database table used by the model.
     */
    protected $table = "bot_replies";

    /**
     * @var  array $casts - The attributes that should be casted to native types.
     */
    protected $casts = [
        '__data' => 'array',
        'status' => 'integer',
    ];

    /**
     * @var  array $fillable - The attributes that are mass assignable.
     */
    protected $fillable = [
    ];

    /**
     * Let the system knows Text columns treated as JSON
     *
     * @var array
     *----------------------------------------------------------------------- */
    protected $jsonColumns = [
        '__data' => [
            // stores the interactive message data
            'interaction_message' => 'array:extend',
            // store the media message data
            'media_message' => 'array:extend',
            // store the template message data
            'template_message' => 'array:extend',
            // store bot actions
            'bot_actions' => 'array:extend'
        ],
    ];

    /**
     * Connected Bot flow
     *
     * @return BelongsTo
     */
    function botFlow():BelongsTo {
        return $this->belongsTo(BotFlowModel::class, 'bot_flows__id', '_id');
    }
}
