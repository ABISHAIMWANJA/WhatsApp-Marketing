<?php

/**
 * UserDeviceRepository.php - Repository file
 *
 * This file is part of the UserDevice component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\UserDevice\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\UserDevice\Models\UserDeviceModel;
use App\Yantrana\Components\User\Models\User as UserModel;

class UserDeviceRepository extends BaseRepository
{
    /**    
     *
     * @var object
     */
    protected $primaryModel = UserDeviceModel::class;  

    /**
     * Constructor.
     *
     * @param  Page
     *-----------------------------------------------------------------------*/
    public function __construct()
    {}
}