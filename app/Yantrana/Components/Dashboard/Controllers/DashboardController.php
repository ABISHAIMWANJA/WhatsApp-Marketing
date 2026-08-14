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
 * DashboardController.php - Controller file
 *
 * This file is part of the Dashboard component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Dashboard\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Dashboard\DashboardEngine;
use App\Yantrana\Support\CommonRequest;

class DashboardController extends BaseController
{
    /**
     * @var DashboardEngine - Dashboard Engine
     */
    protected $dashboardEngine;

    /**
     * Constructor
     *
     * @param  DashboardEngine  $dashboardEngine  - Dashboard Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(DashboardEngine $dashboardEngine)
    {
        $this->dashboardEngine = $dashboardEngine;
    }

    /**
     * Dashboard View
     */
    public function dashboardView()
    {

        return $this->loadView(
            'dashboard',
            $this->dashboardEngine->prepareDashboardData()
        );
    }

    /**
     * Dashboard View
     */
    public function vendorDashboardView()
    {
        return $this->loadView(
            'vendors.vendor-dashboard',
            $this->dashboardEngine->prepareVendorDashboardData()
        );
    }

    /**
     * Dashboard Stats Data Filter
     *
     *
     * @return json object
     */
    public function dashboardStatsDataFilter(CommonRequest $request, $vendorUid = null)
    {
        $request->validate([
            'daterange' => [
                'required',
            ],
        ]);
        // Update client side Alpine Bindings
        updateClientModels(array_merge(['isDurationFilterActivated' => false]));

        return $this->processResponse(1, [], [], true);
    }

    /**
     * Get vendor quick view data
     *
     * @param  mix  $vendorIdOrUid
     * @return array
     */
    public function getVendorQuickViewData($vendorIdOrUid)
    {
        // ask engine to process the request
        $processReaction = $this->dashboardEngine->prepareVendorQuickViewData($vendorIdOrUid);

        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
}
