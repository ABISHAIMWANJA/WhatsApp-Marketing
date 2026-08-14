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
 * ActivityLogRepository.php - Repository file
 *
 * This file is part of the User component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\User\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\User\Interfaces\ActivityLogRepositoryInterface;
use App\Yantrana\Components\User\Models\ActivityLogModel;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = ActivityLogModel::class;
}
