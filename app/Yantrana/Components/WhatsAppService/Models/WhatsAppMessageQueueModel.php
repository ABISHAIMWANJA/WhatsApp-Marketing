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
* WhatsAppMessageQueue.php - Model file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Models;

use Illuminate\Support\Arr;
use App\Yantrana\Base\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WhatsAppMessageQueueModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'whatsapp_message_queue';

    /**
     * Let the system knows Text columns treated as JSON
     *
     * @var array
     *----------------------------------------------------------------------- */
    protected $jsonColumns = [
        '__data' => [
            'process_response' => 'array:extend',
            'contact_data' => 'array:extend',
            'campaign_data' => 'array:extend',
            'expiry_at' => 'string',
            'campaign_type' => 'string',
            'preset_message_id' => 'string', // bot reply id for preset message
        ],
    ];

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '__data' => 'array',
        'scheduled_at' => 'datetime',
        'status' => 'integer',
        'retries' => 'integer',
        '__data->expiry_at' => 'datetime'
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = ['status'];

    protected $appends = [
        'whatsapp_message_error',
        'formatted_updated_time',
        'formatted_scheduled_time',
    ];

    /**
     * error message if any
     */
    protected function whatsappMessageError(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return str_replace(
                    [
                        'Recipient phone number not in allowed list  Recipient',
                    ]
                , [
                    'Recipient',
                ], Arr::get(json_decode($attributes['__data'], true), 'process_response.error_message'));
            }
        );
    }

    /**
     * formatted updated at
     */
    protected function formattedUpdatedTime(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => formatDateTime($attributes['updated_at'], null, $attributes['vendors__id']),
        );
    }
    /**
     * formatted scheduled at
     */
    protected function formattedScheduledTime(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => formatDiffForHumans($attributes['scheduled_at'], null, $attributes['vendors__id']),
        );
    }
}
