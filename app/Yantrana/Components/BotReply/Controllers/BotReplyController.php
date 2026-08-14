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
* BotReplyController.php - Controller file
*
* This file is part of the BotReply component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\BotReply\Controllers;

use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Base\BaseController;

use Illuminate\Database\Query\Builder;
use App\Yantrana\Support\CommonPostRequest;
use App\Yantrana\Components\BotReply\BotReplyEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;

class BotReplyController extends BaseController
{
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
      * @param  BotReplyEngine $botReplyEngine - BotReply Engine
      *
      * @return  void
      *-----------------------------------------------------------------------*/
    public function __construct(
        BotReplyEngine $botReplyEngine,
        WhatsAppTemplateEngine $whatsAppTemplateEngine
    )
    {
        $this->botReplyEngine = $botReplyEngine;
        $this->whatsAppTemplateEngine = $whatsAppTemplateEngine;
    }


    /**
      * list of BotReply
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function showBotReplyView()
    {
        validateVendorAccess('manage_bot_replies');
        // load the view
        $botActionSupportData = $this->botReplyEngine->getBotActionSupportData();
        return $this->loadView('bot-reply.list', [
            'dynamicFields' => $this->botReplyEngine->preDataForBots()->data('dynamicFields'),
            'templateData' => $this->whatsAppTemplateEngine->prepareApprovedTemplates()->data(),
            'vendorMessagingUsers' => $botActionSupportData['vendorMessagingUsers'],
            'allLabels' => $botActionSupportData['allLabels']
        ]);
    }
    /**
      * list of BotReply
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function prepareBotReplyList()
    {
        validateVendorAccess('manage_bot_replies');
        // respond with dataTables preparations
        return $this->botReplyEngine->prepareBotReplyDataTableSource();
    }

    /**
        * BotReply process delete
        *
        * @param  mix $botReplyIdOrUid
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function processBotReplyDelete(BaseRequest $request, $botReplyIdOrUid)
    {
        if (request('page_type') == 'bot_flow_builder') {
            validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        } elseif (request('page_type') == 'preset_message') { 
            validateVendorAccess('manage_templates', 'delete_templates');
        } else {
            validateVendorAccess('manage_bot_replies', 'delete_bot_replies');
        }
        
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->processBotReplyDelete($botReplyIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
    /**
        * BotReply duplicate
        *
        * @param  mix $botReplyIdOrUid
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function processBotReplyDuplicate(BaseRequest $request, $botReplyIdOrUid)
    {
        if (request('page_type') == 'bot_flow_builder') {
            validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        } elseif (request('page_type') == 'preset_message') { 
            validateVendorAccess('manage_templates', 'add_edit_templates');
        } else {
            validateVendorAccess('manage_bot_replies', 'add_edit_bot_replies');
        }

        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->processBotReplyDuplicate($botReplyIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
      * BotReply create process
      *
      * @param  object BaseRequest $request
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function processBotReplyCreate(BaseRequest $request)
    {
        if(!$request->bot_flow_uid) {
            if ($request->nt_campaign_preset_message == 'yes') {
                validateVendorAccess('manage_templates', 'add_edit_templates');
            } else {
                validateVendorAccess('manage_bot_replies', 'add_edit_bot_replies');
            }
        } else {
            validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        }
        
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $triggerTypeValidations = [];
        $vendorId = getVendorId();
        // if bot flow
        if(!$request->bot_flow_uid) {
            if($request->trigger_type != 'welcome') {
                $triggerTypeValidations = [
                    "required",
                    "max:250",
                ];
            }
        }
        // if bot flow item
        if($request->bot_flow_uid) {
            $request->merge([
                'trigger_type' => 'is',
                'reply_trigger' => null,
            ]);
        }
        $validations = [
            "trigger_type" => "required",
            // "reply_trigger" => $triggerTypeValidations,
        ];
        if($request->trigger_type != 'NT_CAMPAIGN_MESSAGE') {
            $validations['reply_trigger'] = $triggerTypeValidations;
        }
        // if not bot flow item
        if(!$request->bot_flow_uid) {
            $validations['name'] = [
                "required",
                "max:200",
                Rule::unique('bot_replies')->where(fn (Builder $query) => $query->where('vendors__id', $vendorId)->whereNull('bot_flows__id'))
            ];
        }
        if(in_array($request->message_type, [
            'simple',
            'interactive',
        ])) {
            $validations['reply_text'] = "required";
        }
        if(in_array($request->message_type, [
            'media',
        ])) {
            $validations['media_header_type'] = [
                "required",
                Rule::in([
                    'image',
                    'video',
                    'audio',
                    'document',
                ])
            ];
            $validations['uploaded_media_file_name'] = "required";
        }
        // interaction message thats button etc
        if($request->message_type == 'interactive') {
            $validations['header_type'] = [
                Rule::in([
                    '',
                    'text',
                    'image',
                    'video',
                    'document',
                ])
            ];
            if(in_array($request->header_type, [
                '',
                'text',
            ])) {
                $validations['interactive_type'] = [
                    'required',
                    Rule::in([
                        'button',
                        'cta_url',
                        'list',
                    ])
                ];
            } else {
                $validations['interactive_type'] = [
                    'required',
                    Rule::in([
                        'button',
                        'cta_url',
                    ])
                ];
            }

            if($request->interactive_type == 'cta_url') {
                $validations['button_display_text'] = "required|min:1|max:20";
                $validations['button_url'] = "required";
                // list type
            } elseif($request->interactive_type == 'list') {
                $validations['list_button_text'] = "required|min:1|max:20";
                $validations['sections'] = ["required","array","min:1","max:10"];
                if(!empty($request->sections)) {
                    foreach ($request->sections as $sectionKey => $section) {
                        $validations["sections.$sectionKey.rows"] = ["required","array","min:1","max:10"];
                        if(!empty($section['rows'])) {
                            $collectedRowIds = [];
                            foreach ($section['rows'] as $sectionRowKey => $sectionRow) {
                                $collectedRowIds[] = $sectionRow['row_id'];
                                $validations["sections.$sectionKey.rows.$sectionRowKey.row_id"] = ["required","min:1","max:200", 'alpha_dash'];
                                $validations["sections.$sectionKey.rows.$sectionRowKey.title"] = ["required","min:1","max:24"];
                                $validations["sections.$sectionKey.rows.$sectionRowKey.description"] = ["nullable","max:72"];
                            }
                            if(array_filter($collectedRowIds) != array_unique(array_filter($collectedRowIds))) {
                                return $this->processResponse(3, [
                                    3 => __tr('Row IDs in Section should be unique.')
                                ], [], true);
                            }
                        }
                    }
                }
            } else {
                // must be reply button type
                // at least 1 button is required
                $validations['buttons.1'] = "required|min:1|max:20";
                $validations['buttons.2'] = "nullable|min:1|max:20";
                $validations['buttons.3'] = "nullable|min:1|max:20";
                if(array_filter($request->buttons ?: []) != array_unique(array_filter($request->buttons ?: []))) {
                    return $this->processResponse(3, [
                        3 => __tr('Buttons labels should be unique.')
                    ], [], true);
                }
            }

            // if header is not a text then it should be media
            if(($request->header_type != 'text') and ($request->message_type != 'interactive')) {
                $validations['uploaded_media_file_name'] = "required";
            } elseif($request->message_type != 'interactive') {
                // if header text then its required
                $validations['header_text'] = "required";
            }
        }
        if($request->message_type == 'template') {
            $validations['template_uid'] = 'required';
        }
        // process the validation based on the provided rules
        $request->validate($validations, [
            'uploaded_media_file_name' => __tr('Media is required')
        ]);
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->processBotReplyCreate($request);
        // get back with response
        return $this->processResponse($processReaction);
    }

    /**
      * BotReply get update data
      *
      * @param  mix $botReplyIdOrUid
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function updateBotReplyData($botReplyIdOrUid)
    {
        if (request('page_type') == 'bot_flow_builder') {
            validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        } elseif (request('page_type') == 'preset_message') {
            validateVendorAccess('manage_templates', 'add_edit_templates');
        } else {
            validateVendorAccess('manage_bot_replies', 'add_edit_bot_replies');
        }
        
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->prepareBotReplyUpdateData($botReplyIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
      * BotReply process update
      *
      * @param  mix @param  mix $botReplyIdOrUid
      * @param  object BaseRequest $request
      *
      * @return  json object
      *---------------------------------------------------------------- */

    public function processBotReplyUpdate(BaseRequest $request)
    {
        if (!$request->bot_flow_uid) {
            if ($request->trigger_type == 'NT_CAMPAIGN_MESSAGE') {
                validateVendorAccess('manage_templates', 'add_edit_templates');
            } else {
                validateVendorAccess('manage_bot_replies', 'add_edit_bot_replies');
            }
        } else {
            validateVendorAccess('manage_bot_replies', 'manage_bot_flow_builder');
        }
        
        $triggerTypeValidations = [];

        if($request->trigger_type != 'NT_CAMPAIGN_MESSAGE') {
            if(!$request->bot_flow_uid) {
                if($request->trigger_type != 'welcome') {
                    $triggerTypeValidations = [
                        "required",
                        "max:250",
                    ];
                }
            }
        }
        $validations = [
            'botReplyIdOrUid' => 'required',
            "name" => [
                "required",
                "max:200",
            ],
            // "reply_text" => "required",
            // "trigger_type" => "required",
            "reply_trigger" => $triggerTypeValidations,
        ];
        if($request->bot_flow_uid) {
            $request->merge([
                'trigger_type' => 'is',
                // 'reply_trigger' => null,
            ]);
        }
        if(!$request->bot_flow_uid) {
            $validations['trigger_type'] = [
                'required'
            ];
        }
        if(in_array($request->message_type, [
            'simple',
            'interactive',
        ])) {
            $validations['reply_text'] = "required";
        }
        if(in_array($request->message_type, [
            'media',
        ])) {
            $validations['media_header_type'] = [
                "required",
                Rule::in([
                    'image',
                    'video',
                    'audio',
                    'document',
                ])
            ];
        }

        if($request->message_type == 'interactive') {
            if($request->interactive_type == 'cta_url') {
                $validations['button_display_text'] = "required|min:1|max:20";
                $validations['button_url'] = "required";
            } elseif($request->interactive_type == 'list') {
                $validations['list_button_text'] = "required|min:1|max:20";
                $validations['sections'] = ["required","array","min:1","max:10"];
                if(!empty($request->sections)) {
                    foreach ($request->sections as $sectionKey => $section) {
                        $validations["sections.$sectionKey.rows"] = ["required","array","min:1","max:10"];
                        if(!empty($section['rows'])) {
                            $collectedRowIds = [];
                            foreach ($section['rows'] as $sectionRowKey => $sectionRow) {
                                $collectedRowIds[] = $sectionRow['row_id'];
                                $validations["sections.$sectionKey.rows.$sectionRowKey.row_id"] = ["required","min:1","max:200",'alpha_dash'];
                                $validations["sections.$sectionKey.rows.$sectionRowKey.title"] = ["required","min:1","max:24"];
                                $validations["sections.$sectionKey.rows.$sectionRowKey.description"] = ["nullable","max:72"];
                            }
                            if(array_filter($collectedRowIds) != array_unique(array_filter($collectedRowIds))) {
                                return $this->processResponse(3, [
                                    3 => __tr('Row IDs in Section should be unique.')
                                ], [], true);
                            }
                        }
                    }
                }
            } else {
                // must be reply button type
                // at least 1 button is required
                $validations['buttons.1'] = "required|min:1|max:20";
                $validations['buttons.2'] = "nullable|min:1|max:20";
                $validations['buttons.3'] = "nullable|min:1|max:20";
                if(array_filter($request->buttons) != array_unique(array_filter($request->buttons))) {
                    return $this->processResponse(3, [
                        3 => __tr('Buttons labels should be unique.')
                    ], [], true);
                }
            }
            // if header is not a text then it should be media
            if($request->header_type == 'text') {
                // if header text then its required
                $validations['header_text'] = "required";
            }
        }

        if($request->message_type == 'template') {
            $validations['template_uid'] = 'required';
        }

        // process the validation based on the provided rules
        $request->validate($validations);
        
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->processBotReplyUpdate($request->get('botReplyIdOrUid'), $request);
        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
        * Get all active bots
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function getAllActiveBots($contactIdOrUid)
    {
        validateVendorAccess([
            'messaging', 
            'assigned_chats_only'
        ]);
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->prepareAllActiveBots($contactIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
        * Get bot preview
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function getBotPreview($botIdOrUid, $contactIdOrUid)
    {
        validateVendorAccess([
            'messaging', 
            'assigned_chats_only'
        ]);
        // ask engine to process the request
        $processReaction = $this->botReplyEngine->prepareBotPreview($botIdOrUid, $contactIdOrUid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
        * BotReply process quick reply
        *
        * @param  mix $botReplyIdOrUid
        *
        * @return  json object
        *---------------------------------------------------------------- */

    public function processBotQuickReply(BaseRequest $request)
    {
        validateVendorAccess([
            'messaging', 
            'assigned_chats_only'
        ]);
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }

        $request->validate([
            'bot_id' => 'required',
            'contact_id_or_uid' => 'required'
        ]);

        // ask engine to process the request
        $processReaction = $this->botReplyEngine->processSendTestBotReply($request->bot_id, $request->contact_id_or_uid);
        // get back to controller with engine response
        return $this->processResponse($processReaction, [], [], true);
    }
}
