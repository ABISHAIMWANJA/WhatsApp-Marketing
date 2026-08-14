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
* CampaignController.php - Controller file
*
* This file is part of the Campaign component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Campaign\Controllers;

use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\BotReply\BotReplyEngine;
use App\Yantrana\Components\Campaign\CampaignEngine;

class CampaignController extends BaseController
{
    /**
     * @var CampaignEngine - Campaign Engine
     */
    protected $campaignEngine;

    /**
     * @var  BotReplyEngine $botReplyEngine - BotReply Engine
     */
    protected $botReplyEngine;

    /**
     * Constructor
     *
     * @param  CampaignEngine  $campaignEngine  - Campaign Engine
     * @param  BotReplyEngine $botReplyEngine - BotReply Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
            CampaignEngine $campaignEngine,
            BotReplyEngine $botReplyEngine
        )
    {
        $this->campaignEngine = $campaignEngine;
        $this->botReplyEngine = $botReplyEngine;
    }

    /**
     * list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showCampaignView()
    {
        validateVendorAccess('manage_campaigns');
        // load the view
        return $this->loadView('campaign.list');
    }
    /**
     * list of Non Campaigns
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showNonTemplatePresetMessages()
    {
        validateVendorAccess('manage_templates');
        // load the view
        return $this->loadView('campaign.non-template-list', [
            'dynamicFields' => $this->botReplyEngine->preDataForBots()->data('dynamicFields'),
            'nonTemplateCampaign' => true,
        ]);
    }

    /**
     * Campaign process delete
     *
     * @param  mix  $campaignUid
     * @return json object
     *---------------------------------------------------------------- */
    public function campaignStatusData($campaignUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->prepareCampaignData($campaignUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareCampaignList($status)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignDataTableSource($status);
    }
    /**
     * Non template list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function nonTemplateCampaignMessagePresetsList($status)
    {
        validateVendorAccess('manage_templates');
        // respond with dataTables preparations
        return $this->botReplyEngine->prepareBotReplyDataTableSource([
            'reply_bot_usages' => 'NT_CAMPAIGN_MESSAGE'
        ]);
    }
    /**
     * Non template list of Campaign
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareNonTemplateCampaignList($status)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignDataTableSource($status);
    }

    /**
     * Campaign process delete
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function processCampaignDelete($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignDelete($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
    * Campaign process archive
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function processCampaignArchive($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignArchive($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
    * Campaign process unarchive
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function processCampaignUnarchive($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignUnarchive($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
    * Campaign process abort
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function processCampaignAbort($campaignIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->processCampaignAbort($campaignIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Campaign get update data
     *
     * @param  mix  $campaignIdOrUid
     * @return json object
     *---------------------------------------------------------------- */
    public function updateCampaignData($campaignIdOrUid)
    {
        validateVendorAccess('manage_campaigns');
        $processReaction = $this->campaignEngine->prepareCampaignUpdateData($campaignIdOrUid);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
    * Campaign get status view
    *
    * @param  mix  $campaignIdOrUid
    * @return json object
    *---------------------------------------------------------------- */
    public function campaignStatusView($campaignUid, $pageType = null, $logStatus = 'all')
    {
        validateVendorAccess('manage_campaigns');
        $campaignDataResponse = $this->campaignEngine->prepareCampaignData($campaignUid);

        // Check if request received from mobile app
        if (isMobileAppRequest()) {
            return $this->processResponse($campaignDataResponse, [], [], true);
        }

        $gotoPage = 'queue';
        if(!$pageType and ($campaignDataResponse->data('campaignStatus') == 'executed') or ($pageType == 'executed')) {
            $gotoPage = 'executed';
        } elseif ($pageType == 'expired') {
            $gotoPage = 'expired';
        }

        $campaignDataResponse->updateData(
            'pageType',
            $gotoPage
        );
        return $this->loadView('whatsapp.campaign-status', $campaignDataResponse->data());
    }

    /**
      * list of campaign queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function campaignQueueLogListView($campaignIdOrUid, $logStatus = 'all')
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignQueueLogList($campaignIdOrUid, $logStatus);
    }

    /**
      * list of executed queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function campaignExecutedLogListView($campaignIdOrUid, $logStatus = 'all')
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignExecutedLogList($campaignIdOrUid, $logStatus);
    }

    /**
      * list of expired queue log
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function campaignExpiredLogListView($campaignIdOrUid)
    {
        validateVendorAccess('manage_campaigns');
        // respond with dataTables preparations
        return $this->campaignEngine->prepareCampaignExpiredLogList($campaignIdOrUid);
    }

    /**
         * campaign Executed report
         *
         * @param string $campaignUid
         * @return file
         */
    public function processCampaignExecutedReportGenerate($campaignUid)
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignEngine->processGenerateCampaignExecutedReport($campaignUid);
    }

    /**
     * campaign Executed report
     *
     * @param string $campaignUid
     * @return file
     */
    public function processCampaignQueueLogReportGenerate($campaignUid = null)
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignEngine->processGenerateQueueLogCampaignReport($campaignUid);
    }

    /**
     * campaign Expired report
     *
     * @param string $campaignUid
     * @return file
     */
    public function processCampaignExpiredLogReportGenerate($campaignUid = null)
    {
        validateVendorAccess('manage_campaigns');
        return $this->campaignEngine->processGenerateExpiredLogCampaignReport($campaignUid);
    }

    /**
     * Get campaign list
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetCampaignList()
    {
        validateVendorAccess('administrative');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->prepareCampaignList();
        
        return $this->processApiResponse($processReaction, $processReaction->data());
    }

    /**
     * Get campaign list
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function apiGetCampaignStatus($vendorUid, $campaignUid)
    {
        validateVendorAccess('manage_campaigns');
        // ask engine to process the request
        $processReaction = $this->campaignEngine->prepareCampaignData($campaignUid);
        // get back to controller with engine response
        return $this->processApiResponse($processReaction, $processReaction->data());
    }
}
