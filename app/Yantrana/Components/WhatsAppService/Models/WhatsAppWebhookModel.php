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

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookModel extends BaseModel
{
    /**
     * @var string - The database table used by the model.
     */
    protected $table = 'whatsapp_webhook_queue';
    protected $fillable = ['headers', 'payload', 'status', 'attempted_at', 'vendors__id'];
    protected $casts = [
        'headers' => 'array',
        'payload' => 'json',
        'attempted_at' => 'datetime',
    ];
}
