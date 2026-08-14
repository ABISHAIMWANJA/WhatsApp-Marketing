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
* WhatsAppTemplateRepository.php - Repository file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppTemplateRepositoryInterface;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppTemplateModel;

class WhatsAppTemplateRepository extends BaseRepository implements WhatsAppTemplateRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = WhatsAppTemplateModel::class;

    public function syncTemplates($templatesData)
    {
        $vendorId = getVendorId();
        $currentTemplateIds = $this->fetchItAll([
            'vendors__id' => $vendorId,
        ], ['template_id'])->pluck('template_id')->toArray();
        $templatesToBeDeleted = array_diff($currentTemplateIds, collect($templatesData)->pluck('template_id')->toArray());
        if (! __isEmpty($templatesToBeDeleted)) {
            $this->deleteItAll($templatesToBeDeleted, 'template_id');
        }
        if(empty($templatesData)) {
            return false;
        }
        return $this->primaryModel::bunchInsertUpdate($templatesData, 'template_id');
    }

    /**
     * Fetch templates datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchTemplatesDataTableSource()
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            // searchable columns
            'searchable' => [
                'template_name',
                'language',
                'category',
                'updated_at',
            ],
        ];

        // get Model result for dataTables
        return $this->primaryModel::where([
            'vendors__id' => getVendorId()
        ])->dataTables($dataTableConfig)->toArray();
    }

    /**
     * Get Approved templates in latest order
     *
     * @return Eloquent
     */
    function getApprovedTemplatesByNewest() {
        return $this->primaryModel::where([
            'status' => app('gairniyojitChachpasani')() ? 'APPROVED' : 'APPROVED_TEMPLATES',
            'vendors__id' => getVendorId(),
        ])->latest()->get();
    }

    public function fetchTemplateListPaginatedData()
    {
        $paginateCount = request()->get('page_size') ?? 100;
        $searchTerm = request()->get('search_term');

        return $this->primaryModel::where('vendors__id', getVendorId())->where(function ($q) use ($searchTerm) {
            $q->where('template_name', 'like', "%{$searchTerm}%")
                ->orWhere('template_id', 'like', "%{$searchTerm}%")
                ->orWhere('category', 'like', "%{$searchTerm}%")
                ->orWhere('language', 'like', "%{$searchTerm}%");
        })
            ->where('status', 'APPROVED')
            ->select('_id', '_uid', 'status', 'template_name', 'updated_at', 'created_at', 'template_id', 'category', 'language')
            ->paginate($paginateCount);
    }
}
