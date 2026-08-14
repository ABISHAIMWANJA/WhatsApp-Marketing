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
 * VendorRepository.php - Repository file
 *
 * This file is part of the Vendor component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Dashboard\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Category\Models\ItemModel;

class DashboardRepository extends BaseRepository
{
    public function fetchItItems()
    {
        return ItemModel::join('categories', 'items.categories__id', '=', 'categories._id')
            ->select('items.*', 'categories.vendors__id as vendor')
            ->where([
                'items.status' => 1,
                'categories.vendors__id' => getVendorId(),
            ])
            ->count();
    }

    public function outOfStockItemsCount()
    {
        return ItemModel::join('categories', 'items.categories__id', '=', 'categories._id')
            ->select('items.*', 'categories.vendors__id as vendor')
            ->where([
                'items.status' => 1,
                'categories.vendors__id' => getVendorId(),
                'items.is_out_of_stock' => 1,
            ])->count();
    }
}
