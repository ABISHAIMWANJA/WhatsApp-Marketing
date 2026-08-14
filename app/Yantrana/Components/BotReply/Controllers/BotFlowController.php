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
* BotFlowController.php - Controller file
*
* This file is part of the BotReply component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\BotReply\Controllers;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\BotReply\BotFlowEngine;
use App\Yantrana\Components\BotReply\BotReplyEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;

class BotFlowController extends BaseController
{       /**
     * @var  BotFlowEngine $botFlowEngine - BotFlow Engine
     */
    protected $botFlowEngine;

    /**
     * @var  BotReplyEngine $botReplyEngine - BotReply Engine
     */
    protected $botReplyEngine;

    /**
     * @var  WhatsAppTemplateEngine $whatsAppTemplateEngine - WhatsAppTemplate Engine
     */
    protected $whatsAppTemplateEngine;

    /**
      * Constructor
      *
      * @param  BotFlowEngine $botFlowEngine - BotFlow Engine
      * @param  BotReplyEngine $botReplyEngine - BotReply Engine
      *
      * @return  void
      *-----------------------------------------------------------------------*/
    public function __construct(
        BotFlowEngine $botFlowEngine,
        BotReplyEngine $botReplyEngine,
        WhatsAppTemplateEngine $whatsAppTemplateEngine
    ) {
        $this->botFlowEngine = $botFlowEngine;
        $this->botReplyEngine = $botReplyEngine;
        $this->whatsAppTemplateEngine = $whatsAppTemplateEngine;
    }


    /**
      * list of BotFlow
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function showBotFlowView()
    {
        validateVendorAccess('manage_bot_replies');
        // load the view
        return $this->loadView('bot-reply.bot-flow.list');
    }
    /**
      * list of BotFlow
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function prepareBotFlowList()
    {
        validateVendorAccess('manage_bot_replies');
        // respond with dataTables preparations
        return $this->botFlowEngine->prepareBotFlowDataTableSource();
    }

    /**
        * BotFlow process delete
        *
        * @param  mix $botFlowIdOrUid
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function processBotFlowDelete($botFlowIdOrUid, BaseRequest $request)
    {
        validateVendorAccess('manage_bot_replies', 'delete_bot_flows');
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        // ask engine to process the request
        $processReaction = $this->botFlowEngine->processBotFlowDelete($botFlowIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
      * BotFlow create process
      *
      * @param  object BaseRequest $request
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function processBotFlowCreate(BaseRequest $request)
    {
        validateVendorAccess('manage_bot_replies', 'add_edit_bot_flows');
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $vendorId = getVendorId();
        // process the validation based on the provided rules
        $request->validate([
            'title' => [
                "required",
                "max:150",
                Rule::unique('bot_flows')->where(fn (Builder $query) => $query->where('vendors__id', $vendorId))
            ],
            'start_trigger' => [
                "required",
                "max:255",
                Rule::unique('bot_flows')->where(fn (Builder $query) => $query->where('vendors__id', $vendorId))
            ]
        ]);
        // ask engine to process the request
        $processReaction = $this->botFlowEngine->processBotFlowCreate($request->all());
        // get back with response
        return $this->processResponse($processReaction);
    }

    /**
      * BotFlow get update data
      *
      * @param  mix $botFlowIdOrUid
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function updateBotFlowData($botFlowIdOrUid)
    {
        validateVendorAccess('manage_bot_replies', 'add_edit_bot_flows');
        // ask engine to process the request
        $processReaction = $this->botFlowEngine->prepareBotFlowUpdateData($botFlowIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
      * BotFlow process update
      *
      * @param  mix @param  mix $botFlowIdOrUid
      * @param  object BaseRequest $request
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function processBotFlowUpdate(BaseRequest $request)
    {
        validateVendorAccess('manage_bot_replies', 'add_edit_bot_flows');
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        // process the validation based on the provided rules
        $request->validate([
            'botFlowIdOrUid' => 'required',
            "title" => "required|max:150",
            "start_trigger" => "required|max:255",
        ]);
        // ask engine to process the request
        $processReaction = $this->botFlowEngine->processBotFlowUpdate($request->get('botFlowIdOrUid'), $request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
     * Bot Flow Data update for builder
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function botFlowDataUpdate(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        // process the validation based on the provided rules
        $request->validate([
            'botFlowUid' => 'required',
            "flow_chart_data" => "nullable|array",
        ]);
        // ask engine to process the request
        $processReaction = $this->botFlowEngine->processBotFlowDataUpdate($request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Flow Builder view
     *
     * @param BaseRequest $request
     * @param string $botFlowIdOrUid
     * @return view
     */
    public function flowBuilderView(BaseRequest $request, $botFlowIdOrUid)
    {
        validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        $processReaction = $this->botFlowEngine->prepareBotFlowBuilderData($botFlowIdOrUid);
        abortIf($processReaction->failed());
        // load the view
        return $this->loadView('bot-reply.bot-flow.builder', array_merge([
            'dynamicFields' => $this->botReplyEngine->preDataForBots()->data('dynamicFields'),
            'botFlowUid' => $botFlowIdOrUid,
            'templateData' => $this->whatsAppTemplateEngine->prepareApprovedTemplates()->data()
        ], $processReaction->data()));
    }
}
