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
 * WhatsAppMessageLog.php - Model file
 *
 * This file is part of the WhatsAppService component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Models;

use App\Yantrana\Base\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;

class WhatsAppMessageLogModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'whatsapp_message_logs';

    /**
     * Let the system knows Text columns treated as JSON
     *
     * @var array
     *----------------------------------------------------------------------- */
    protected $jsonColumns = [
        '__data' => [
            'contact_data' => 'array',
            'initial_response' => 'array',
            'media_values' => 'array',
            'template_proforma' => 'array',
            'template_components' => 'array',
            'template_component_values' => 'array',
            'webhook_responses' => 'array:extend',
            'options' => 'array:extend',
            'interaction_message_data' => 'array:extend',
            'other_message_data' => 'array:extend',
            'system_message_data' => 'array',
            'campaign_type' => 'string',
            'preset_message_id' => 'string', // bot reply id for preset message
            'send_message_via_marketing_message_api' => 'boolean',
        ],
    ];

    /**
     * @var array - The attributes that should be casted to native types.
     */
    protected $casts = [
        '__data' => 'array',
        'timestamp' => 'datetime',
        'messaged_at' => 'datetime',
        'is_incoming_message' => 'integer',
    ];

    /**
     * @var array - The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $appends = [
        'formatted_message_time',
        'formatted_message_ago_time',
        'whatsapp_message_error',
        'formatted_updated_time',
    ];

    /**
     * prepare and get contact full name
     */
    protected function formattedMessageTime(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => isset($attributes['messaged_at']) ? formatDateTime($attributes['messaged_at'], null, $attributes['vendors__id']) : null,
        );
    }

    /**
     * prepare and get contact full name
     */
    protected function formattedMessageAgoTime(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => isset($attributes['messaged_at']) ? formatDiffForHumans($attributes['messaged_at'], 6, $attributes['vendors__id']) : null,
        );
    }
    /**
     * formatted updated at
     */
    protected function formattedUpdatedTime(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => formatDateTime($attributes['updated_at'], null, $attributes['vendors__id']),
        );
    }

    /**
     * error message if any
     */
    protected function whatsappMessageError(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $dataArray = json_decode($attributes['__data'], true);
                $errorMessage = Arr::get($dataArray, 'webhook_responses.failed.0.changes.0.value.statuses.0.errors.0.error_data.details') ?: Arr::get($dataArray, 'webhook_responses.incoming.0.changes.0.value.messages.0.errors.0.error_data.details');
                // for incoming message, if message type is not unsupported, then we can ignore delivered, read, played status as they are not error and can be due to delay in webhook or something else. If message type is unsupported, then we will show message type in error for better understanding of issue.
                if (Arr::get($dataArray, 'webhook_responses.incoming.0.changes.0.value.messages.0.type') != 'unsupported') {
                    if (in_array($attributes['status'], [
                        'delivered',
                        'read',
                        'played',
                    ])) {
                        return '';
                    }
                } else {
                    // for unsupported message type, show message type in error for better understanding of issue
                    $unsupportedMessageType = Arr::get($dataArray, 'webhook_responses.incoming.0.changes.0.value.messages.0.unsupported.type');
                    $errorMessage .= $unsupportedMessageType ? ' - ' . $unsupportedMessageType : '';
                }
                return $errorMessage;
            }
        );
    }
}
