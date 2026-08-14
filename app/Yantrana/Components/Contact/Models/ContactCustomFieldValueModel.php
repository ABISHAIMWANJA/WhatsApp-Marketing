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
* ContactCustomFieldValue.php - Model file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Models;

use App\Yantrana\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactCustomFieldValueModel extends BaseModel
{
    /**
     * @var  string $table - The database table used by the model.
     */
    protected $table = "contact_custom_field_values";

    /**
     * @var  array $casts - The attributes that should be casted to native types.
     */
    protected $casts = [
        'contact_custom_fields__id' => 'integer',
        'contacts__id' => 'integer',
    ];

    /**
     * @var  array $fillable - The attributes that are mass assignable.
     */
    protected $fillable = [
    ];

    /**
     * Get Custom field related to Field Value
     *
     * @return BelongTo
     */
    function customField():BelongsTo  {
        return $this->belongsTo(ContactCustomFieldModel::class, 'contact_custom_fields__id', '_id');
    }
}