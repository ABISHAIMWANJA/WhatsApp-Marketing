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
* LabelRepository.php - Repository file
*
* This file is part of the Contact component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\Contact\Models\LabelModel;
use App\Yantrana\Components\Contact\Interfaces\LabelRepositoryInterface;

class LabelRepository extends BaseRepository
                          implements LabelRepositoryInterface
{
   /**
    * primary model instance
    *
    * @var  object
    */
   protected $primaryModel = LabelModel::class;

   /**
     * Fetch Contact Group List Paginated Data
     *
     * @return void
   */
   public function fetchContactLabelsAndTagsListPaginatedData() 
   {
      $paginateCount = request()->get('page_size') ?? 100;
      $searchTerm = request()->get('search_term');

      return $this->primaryModel::where('vendors__id', getVendorId())
         ->orderBy('created_at', 'desc')
         ->where(function ($q) use ($searchTerm) {
               $q->where('title', 'like', "%{$searchTerm}%");
         })
         ->paginate($paginateCount);
   }
}