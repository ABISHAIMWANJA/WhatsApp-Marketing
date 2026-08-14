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
 * BotReplyEngine.php - Main component file
 *
 * This file is part of the BotReply component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\BotReply;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Database\Query\Builder;
use App\Yantrana\Components\Media\MediaEngine;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\BotReply\Repositories\BotFlowRepository;
use App\Yantrana\Components\BotReply\Repositories\BotReplyRepository;
use App\Yantrana\Components\BotReply\Interfaces\BotReplyEngineInterface;
use App\Yantrana\Components\Contact\Repositories\ContactCustomFieldRepository;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;
use App\Yantrana\Components\User\Repositories\UserRepository;
use App\Yantrana\Components\Contact\Repositories\LabelRepository;

class BotReplyEngine extends BaseEngine implements BotReplyEngineInterface
{
    /**
     * @var  BotReplyRepository $botReplyRepository - BotReply Repository
     */
    protected $botReplyRepository;

    /**
     * @var  ContactCustomFieldRepository $contactCustomFieldRepository - ContactCustomField Repository
     */
    protected $contactCustomFieldRepository;

    /**
     * @var MediaEngine - Media Engine
     */
    protected $mediaEngine;

    /**
     * @var WhatsAppServiceEngine - WhatsApp Service Engine
     */
    protected $whatsAppServiceEngine;

    /**
     * @var  BotFlowRepository $botFlowRepository - BotFlow Repository
     */
    protected $botFlowRepository;

    /**
     * @var ContactRepository - Contact Repository
     */
    protected $contactRepository;

    /**
     * @var UserRepository - User Repository
     */
    protected $userRepository;

    /**
     * @var LabelRepository - Label Repository
     */
    protected $labelRepository;

    /**
     * Constructor
     *
     * @param  BotReplyRepository $botReplyRepository - BotReply Repository
     * @param  ContactCustomFieldRepository $contactCustomFieldRepository - BotReply Repository
     * @param  MediaEngine $mediaEngine
     * @param  WhatsAppServiceEngine $whatsAppServiceEngine
     * @param  BotFlowRepository $botFlowRepository
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function __construct(
        BotReplyRepository $botReplyRepository,
        ContactCustomFieldRepository $contactCustomFieldRepository,
        MediaEngine $mediaEngine,
        WhatsAppServiceEngine $whatsAppServiceEngine,
        BotFlowRepository $botFlowRepository,
        ContactRepository $contactRepository,
        UserRepository $userRepository,
        LabelRepository $labelRepository
    ) {
        $this->botReplyRepository = $botReplyRepository;
        $this->contactCustomFieldRepository = $contactCustomFieldRepository;
        $this->mediaEngine = $mediaEngine;
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
        $this->botFlowRepository = $botFlowRepository;
        $this->contactRepository = $contactRepository;
        $this->userRepository = $userRepository;
        $this->labelRepository = $labelRepository;
    }

    /**
     * Get contact dynamic fields and custom fields
     *
     * @return EngineResponse
     */
    public function preDataForBots()
    {
        $vendorId = getVendorId();

        $dynamicFieldsToReplace = [
            '{first_name}',
            '{last_name}',
            '{full_name}',
            '{phone_number}',
            '{email}',
            '{country}',
            '{language_code}',
            '{assigned_team_member}'
        ];

        $customFields = $this->contactCustomFieldRepository->fetchItAll([
            'vendors__id' => $vendorId
        ]);

        foreach ($customFields as $customField) {
            $dynamicFieldsToReplace[] = "{{$customField->input_name}}";
        }

        return $this->engineSuccessResponse([
            'dynamicFields' => $dynamicFieldsToReplace
        ]);
    }

    /**
     * BotReply datatable source
     *
     * @return  array
     *---------------------------------------------------------------- */
    public function prepareBotReplyDataTableSource($options = [])
    {
        $options = array_merge([
            'reply_bot_usages' => null,
        ], $options);
        $ntCampaignMessagePresets = false;
        if ($options['reply_bot_usages'] == 'NT_CAMPAIGN_MESSAGE') {
            $ntCampaignMessagePresets = true;
            $botReplyCollection = $this->botReplyRepository->fetchNonTemplateCampaignMessagePresetsDataTableSource();
        } else {
            $botReplyCollection = $this->botReplyRepository->fetchBotReplyDataTableSource();
        }
        $orderStatuses = configItem('status_codes');
        $botTriggerTypes = configItem('bot_reply_trigger_types');
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'name',
            'reply_text',
            'trigger_type',
            'trigger_type' => function ($rowData) use (&$botTriggerTypes) {
                return $botTriggerTypes[$rowData['trigger_type']]['title'] ?? '';
            },
            'reply_trigger' => function ($rowData) {
                return ($rowData['trigger_type'] != 'welcome') ? $rowData['reply_trigger'] : '';
            },
            'created_at' => function ($rowData) {
                return formatDateTime($rowData['created_at']);
            },
            'status' => function ($key) use (&$orderStatuses) {
                if ($key['status'] === null) {
                    $key['status'] = 1; // active
                }
                return Arr::get($orderStatuses, $key['status']);
            },
            'bot_type' => function ($rowData) {
                $botReplyType = __tr('Simple');
                if ($rowData['__data']['media_message'] ?? null) {
                    $botReplyType = __tr('Media');
                } elseif ($rowData['__data']['interaction_message'] ?? null) {
                    $botReplyType = __tr('Interactive/Buttons');
                } elseif ($rowData['__data']['template_message'] ?? null) {
                    $botReplyType = __tr('Template Bot');
                }
                return $botReplyType;
            },
        ];
        // prepare data for the DataTables
        return $this->dataTableResponse($botReplyCollection, $requireColumns);
    }


    /**
     * BotReply delete process
     *
     * @param  mix $botReplyIdOrUid
     *
     * @return  EngineResponse
     *---------------------------------------------------------------- */

    public function processBotReplyDelete($botReplyIdOrUid)
    {
        $vendorId = getVendorId();
        // fetch the record
        $botReply = $this->botReplyRepository->fetchIt([
            '_uid' => $botReplyIdOrUid,
            'vendors__id' => $vendorId,
        ]);
        // check if the record found
        if (__isEmpty($botReply)) {
            // if not found
            return $this->engineResponse(18, [
                'botReplyUid' => $botReplyIdOrUid
            ], __tr('Bot Reply not found'));
        }

        // demo bot delete protection
        if (isDemo() and in_array($botReply->_id, explode(',', config('__misc.demo_protected_bots')))) {
            return $this->engineResponse(2, null, __tr('Your are not allowed to delete this bot in DEMO.'));
        }
        // ask to delete the record
        if ($this->botReplyRepository->deleteIt($botReply)) {
            // delete the links
            $this->botReplyRepository->updateItAll([
                'bot_replies__id' => $botReply->_id
            ], [
                'reply_trigger' => null
            ]);
            // if successful
            return $this->engineResponse(1, [
                'botReplyUid' => $botReply->_uid
            ], __tr('Bot Reply deleted successfully'));
        }
        // if failed to delete
        return $this->engineResponse(2, [
            'botReplyUid' => $botReplyIdOrUid
        ], __tr('Failed to delete BotReply'));
    }
    /**
     * BotReply duplicate process
     *
     * @param  mix $botReplyIdOrUid
     *
     * @return  EngineResponse
     *---------------------------------------------------------------- */

    public function processBotReplyDuplicate($botReplyIdOrUid)
    {
        $vendorId = getVendorId();
        // fetch the record
        $botReply = $this->botReplyRepository->fetchIt([
            '_uid' => $botReplyIdOrUid,
            'vendors__id' => $vendorId,
        ]);
        // check if the record found
        if (__isEmpty($botReply)) {
            // if not found
            return $this->engineResponse(18, [
                'botReplyUid' => $botReplyIdOrUid
            ], __tr('Bot Reply not found'));
        }
        // do not apply plan restriction if bot is getting added for bot flow
        // as there no limit for flows
        if (!$botReply->bot_flows__id) {
            // check the feature limit
            $vendorPlanDetails = vendorPlanDetails('bot_replies', $this->botReplyRepository->countIt([
                'vendors__id' => $vendorId,
                'bot_flows__id' => null,
            ]), $vendorId);
            if (!$vendorPlanDetails['is_limit_available']) {
                return $this->engineResponse(22, null, $vendorPlanDetails['message']);
            }
        }
        // ask to duplicate the record
        $newBotUid = Str::uuid();
        $newBotReply = $botReply->replicate();
        $newBotReply->name = $botReply->name . '-' . uniqid();
        $newBotReply->_uid = $newBotUid;
        $botData = $botReply->__data;
        // unset buttons
        if (isset($botData['interaction_message']['buttons'])) {
            $botData['interaction_message']['buttons'] = [];
        }
        // unset list data
        if (isset($botData['interaction_message']['list_data'])) {
            $botData['interaction_message']['list_data'] = [
                'button_text' => $botData['interaction_message']['list_data']['button_text'],
            ];
        }
        if ($botReply->bot_flows__id) {
            $newBotReply->reply_trigger = null;
            $newBotReply->status = 2; // it always be inactive for bot flow
            $newBotReply->__data = $botData;
        }
        if ($newBotReply->save()) {
            if ($botReply->bot_flows__id) {
                return $this->engineResponse(21, [
                    'reloadPage' => true,
                    'messageType' => 'success',
                ], __tr('Bot Reply duplicated'));
            }
            // if successful
            return $this->engineResponse(1, [
                'botReplyUid' => $newBotUid
            ], __tr('Bot Reply duplicated'));
        }
        // if failed to delete
        return $this->engineResponse(2, [
            'botReplyUid' => $botReplyIdOrUid
        ], __tr('Failed to duplicate Bot Reply'));
    }

    /**
     * BotReply create
     *
     * @param  array $inputData
     *
     * @return  array
     *---------------------------------------------------------------- */

    public function processBotReplyCreate($request)
    {
        $inputData = $request->all();
        $vendorId = getVendorId();
        $inputData['status'] = ($inputData['status'] ?? null) ? 1 : 2;
        // if bot flow
        if (isset($inputData['bot_flow_uid']) and $inputData['bot_flow_uid']) {
            $botFlow = $this->botFlowRepository->fetchIt($inputData['bot_flow_uid']);
            if (__isEmpty($botFlow)) {
                return $this->engineResponse(2, null, __tr('Invalid bot flow'));
            }
            $inputData['bot_flows__id'] = $botFlow->_id;

            $request->validate([
                'name' => [
                    "required",
                    "max:200",
                    Rule::unique('bot_replies')->where(fn(Builder $query) => $query->where([
                        'vendors__id' => $vendorId,
                        'bot_flows__id' => $botFlow->_id,
                    ]))
                ]
            ]);
            // bot flow bot always in inactive state, its state will be depend on flow status
            $inputData['status'] = 2;
        }
        $messageType = $inputData['message_type'] ?? 'simple';
        // do not apply plan restriction if bot is getting added for bot flow
        // as there no limit for flows
        if (!isset($inputData['bot_flow_uid']) or !$inputData['bot_flow_uid']) {
            // check the feature limit
            $vendorPlanDetails = vendorPlanDetails('bot_replies', $this->botReplyRepository->countIt([
                'vendors__id' => $vendorId,
                'bot_flows__id' => null,
            ]), $vendorId);
            if (!$vendorPlanDetails['is_limit_available']) {
                return $this->engineResponse(22, null, $vendorPlanDetails['message']);
            }
        }
        $inputData['vendors__id'] = $vendorId;
        if ($messageType == 'interactive') {
            $interactiveType = $inputData['interactive_type'] ?? 'button';
            $mediaLink = '';
            if ($inputData['header_type'] and ($inputData['header_type'] != 'text')) {
                $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputData['uploaded_media_file_name']], 'whatsapp_' . $inputData['header_type']);
                if ($isProcessed->failed()) {
                    return $isProcessed;
                }
                $mediaLink = $isProcessed->data('path');
            }
            $ctaUrlButton = null;
            $listData = null;
            if ($interactiveType == 'cta_url') {
                $ctaUrlButton = [
                    'display_text' => $inputData['button_display_text'],
                    'url' => $inputData['button_url'],
                ];
            }
            if ($interactiveType == 'list') {
                $listData = [
                    'button_text' => $inputData['list_button_text'],
                    'sections' => array_filter($inputData['sections'] ?? []),
                ];
            }
            $inputData['__data'] = [
                'interaction_message' => [
                    'interactive_type' => $interactiveType,
                    'media_link' => $mediaLink,
                    'header_type' => $inputData['header_type'], // "text", "image", or "video"
                    'header_text' => $inputData['header_text'] ?? '',
                    'body_text' => $inputData['reply_text'],
                    'footer_text' => $inputData['footer_text'] ?? '',
                    'buttons' => array_filter($inputData['buttons'] ?? []),
                    'cta_url' => $ctaUrlButton,
                    'list_data' => $listData,
                ]
            ];
        } elseif ($messageType == 'media') {
            $inputData['header_type'] = $inputData['media_header_type'];
            $mediaLink = '';
            $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputData['uploaded_media_file_name']], 'whatsapp_' . $inputData['header_type']);
            if ($isProcessed->failed()) {
                return $isProcessed;
            }
            $mediaLink = $isProcessed->data('path');
            $inputData['reply_text'] = '';
            $inputData['__data'] = [
                'media_message' => [
                    'media_link' => $mediaLink,
                    'header_type' => $inputData['header_type'], // "text", "image", "audio or "video"
                    'caption' => $inputData['caption'] ?? '',
                    'file_name' => $isProcessed->data('fileName'),
                ]
            ];
        } elseif ($messageType == 'template') {

            $testContactUid = getVendorSettings('test_recipient_contact');
            $templateMessageSentProcess = $this->whatsAppServiceEngine->sendTemplateMessageProcess($request, $testContactUid, false, null, $vendorId, null);
            if ($templateMessageSentProcess->failed()) {
                return $templateMessageSentProcess;
            }
            $inputData['reply_text'] = '__IGNORE__';
            $inputData['__data'] = [
                'template_message' => [
                    'template_data' => $templateMessageSentProcess->data()
                ]
            ];
        }

        $botActions = array_filter(data_get($inputData, 'bot_actions', []));
        // Check if bot actions exists
        if (!__isEmpty($botActions)) {
            $inputData['__data'] = array_merge(data_get($inputData, '__data', []), [
                'bot_actions' => $botActions
            ]);
        }

        // ask to add record
        $engineResponse = $this->botReplyRepository->processTransaction(function () use (&$inputData, &$vendorId) {
            if ($botReply = $this->botReplyRepository->storeBotReply($inputData)) {
                // if needs to validate message using by sending test message
                if ($inputData['validate_bot_reply'] ?? null) {
                    // If reply text column value is __IGNORE__ then don't send test reply because its already sent.
                    if ($inputData['reply_text'] === '__IGNORE__') {
                        return $this->botReplyRepository->transactionResponse(1, [], __tr('Request Successful'));
                    } else {
                        $validateTestBotReply = $this->whatsAppServiceEngine->validateTestBotReply($botReply->_id);
                        if ($validateTestBotReply->success()) {
                            return $this->botReplyRepository->transactionResponse(1, [], __tr('Request Successful'));
                        }
                    }

                    // if got any errors etc
                    return $this->botReplyRepository->transactionResponse($validateTestBotReply->reaction(), [], $validateTestBotReply->message());
                }
                // success
                return $this->botReplyRepository->transactionResponse(1, [], __tr('Request Successful'));
            }
            // failed for any other reason
            return $this->botReplyRepository->transactionResponse(2, [], __tr('Request Failed'));
        });
        // if bot flow
        if (isset($inputData['bot_flow_uid']) and $inputData['bot_flow_uid'] and ($engineResponse[0] == 1)) {
            $flowBots = $this->botReplyRepository->fetchItAll([
                'bot_flows__id' => $inputData['bot_flows__id'],
                'vendors__id' => $vendorId,
            ]);
            updateClientModels([
                'flowBots' => $flowBots,
            ]);
        }
        return $this->engineResponse($engineResponse);
    }

    /**
     * BotReply prepare update data
     *
     * @param  mix $botReplyIdOrUid
     *
     * @return  array
     *---------------------------------------------------------------- */

    public function prepareBotReplyUpdateData($botReplyIdOrUid)
    {
        $vendorId = getVendorId();
        // fetch the record
        $botReply = $this->botReplyRepository->fetchIt([
            '_uid' => $botReplyIdOrUid,
            'vendors__id' => $vendorId,
        ]);
        // Check if $botReply not exist then throw not found
        // exception
        if (__isEmpty($botReply)) {
            return $this->engineResponse(18, null, __tr('Bot Reply not found.'));
        }

        return $this->engineResponse(1, $botReply->toArray());
    }

    /**
     * BotReply process update
     *
     * @param  mixed $botReplyIdOrUid
     * @param  object $request
     *
     * @return  array
     *---------------------------------------------------------------- */

    public function processBotReplyUpdate($botReplyIdOrUid, $request)
    {
        $vendorId = getVendorId();
        // fetch the record
        $botReply = $this->botReplyRepository->fetchIt([
            '_uid' => $botReplyIdOrUid,
            'vendors__id' => $vendorId,
        ]);
        // Check if $botReply not exist then throw not found
        // exception
        if (__isEmpty($botReply)) {
            return $this->engineResponse(18, null, __tr('Resource not found'));
        }
        // demo bot edit protection
        if (isDemo() and in_array($botReply->_id, explode(',', config('__misc.demo_protected_bots')))) {
            return $this->engineResponse(2, null, __tr('Your are not allowed to edit this bot in DEMO.'));
        }
        $botFlowId = null;
        $inputData = $request->all();
        // if bot flow
        if (isset($inputData['bot_flow_uid']) and $inputData['bot_flow_uid']) {
            $botFlow = $this->botFlowRepository->fetchIt($inputData['bot_flow_uid']);
            if (__isEmpty($botFlow)) {
                return $this->engineResponse(2, null, __tr('Invalid bot flow'));
            }
            $botFlowId = $botFlow->_id;
        }
        $currentInputSectionsData = array_filter($inputData['sections'] ?? []);
        $currentInputButtonsData = array_filter($inputData['buttons'] ?? []);
        if ($botFlowId) {
            // validate for uniqueness
            $request->validate([
                "name" => [
                    "required",
                    "max:200",
                    Rule::unique('bot_replies')->where(fn(Builder $query) => $query->where([
                        'vendors__id' => $vendorId,
                        'bot_flows__id'  => $botFlowId,
                    ]))->ignore($botReply->_id, '_id')
                ],
            ]);
        } else {
            $request->validate([
                "name" => [
                    "required",
                    "max:200",
                    Rule::unique('bot_replies')->where(fn(Builder $query) => $query->where([
                        'vendors__id' => $vendorId,
                        'bot_flows__id'  => null,
                    ]))->ignore($botReply->_id, '_id')
                ],
            ]);
        }

        $isTemplateMessage = Arr::get($botReply, '__data.template_message');
        $messageType = $inputData['message_type'] ?? 'simple';
        $updateData = [
            'name' => $inputData['name'],
            'reply_text' => $isTemplateMessage ? '__IGNORE__' : $inputData['reply_text'] ?? '',
        ];
        if (!$botFlowId) {
            $updateData['trigger_type'] = $request->trigger_type;
            $updateData['reply_trigger'] = $request->reply_trigger;
            $updateData['status'] = $request->status ? 1 : 2;
        }
        // media message
        if ($messageType == 'media') {

            $mediaMessage = [
                'caption' => $inputData['caption'] ?? '',
            ];
            // Check if new media uploaded
            if (!__isEmpty(data_get($inputData, 'uploaded_media_file_name'))) {
                $inputData['header_type'] = $inputData['media_header_type'];
                $mediaLink = '';
                $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputData['uploaded_media_file_name']], 'whatsapp_' . $inputData['header_type']);
                if ($isProcessed->failed()) {
                    return $isProcessed;
                }
                $mediaLink = $isProcessed->data('path');
                $inputData['reply_text'] = '';

                $mediaMessage['media_link'] = $mediaLink;
                $mediaMessage['header_type'] = $inputData['header_type']; // "text", "image", "audio or "video"
                $mediaMessage['file_name'] = $isProcessed->data('fileName');
            }

            $updateData['__data'] = [
                'media_message' => $mediaMessage
            ];
        } elseif ($messageType == 'interactive') {
            $interactiveType = $inputData['interactive_type'] ?? 'button';
            $ctaUrlButton = null;
            if ($interactiveType == 'cta_url') {
                $ctaUrlButton = [
                    'display_text' => $inputData['button_display_text'],
                    'url' => $inputData['button_url'],
                ];
            }
            $listData = null;
            if ($interactiveType == 'list') {
                $listData = [
                    'button_text' => $inputData['list_button_text'],
                    'sections' => $currentInputSectionsData,
                ];
            }
            $updateData['__data'] = [
                'interaction_message' => [
                    'interactive_type' => $interactiveType,
                    'header_text' => $inputData['header_text'] ?? '',
                    'body_text' => $inputData['reply_text'],
                    'footer_text' => $inputData['footer_text'] ?? '',
                    'buttons' => $currentInputButtonsData,
                    'cta_url' => $ctaUrlButton,
                    'list_data' => $listData,
                ]
            ];
        }
        // update process
        $engineResponse = $this->botReplyRepository->processTransaction(function () use (&$botReply, &$updateData, &$request, &$interactiveType, &$currentInputButtonsData, &$botFlowId, &$botFlow, $currentInputSectionsData, &$inputData, $messageType) {
            $isUpdated = false;
            $botFlowData = $botFlow->__data ?? [];
            $botData = $botReply->__data;
            $existingLinks = $botFlowData['flow_builder_data']['links'] ?? [];
            if ($interactiveType == 'button') {
                // update links etc for bot flow
                if ($botFlowId) {
                    $existingButtons = $botReply->__data['interaction_message']['buttons'] ?? [];
                    $buttonsRemoved = array_diff($existingButtons, $currentInputButtonsData);
                    $buttonsAdded = array_diff($currentInputButtonsData, $existingButtons);
                    $existingBotsForTrigger = $this->botReplyRepository->fetchItAll($existingButtons, [], 'reply_trigger', [
                        'where' => [
                            'bot_flows__id' => $botFlowId,
                            'bot_replies__id' => $botReply->_id,
                        ]
                    ]);
                    foreach ($existingBotsForTrigger as $existingBotForTrigger) {
                        $itemIndexInRemoved = array_search($existingBotForTrigger->reply_trigger, $buttonsRemoved);
                        if ($itemIndexInRemoved !== false) {
                            $newReplyTrigger = $buttonsAdded[$itemIndexInRemoved] ?? null;
                            $isUpdated = $this->botReplyRepository->updateIt($existingBotForTrigger, [
                                'reply_trigger' => $newReplyTrigger
                            ]);
                            if (!$newReplyTrigger) {
                                if (!empty($existingLinks)) {
                                    $existingLinks = Arr::where($existingLinks, function ($value, $key) use (&$existingBotForTrigger) {
                                        return $value['toOperator'] != $existingBotForTrigger['_uid'];
                                    });
                                }
                            }
                        }
                    }
                    // update flow links
                    // following type of method to update JSON creates problem for mariadb 
                    //  '__data->flow_builder_data->links' => $existingLinks
                    // instead we have used it
                    Arr::set($botFlowData, 'flow_builder_data.links', $existingLinks);
                    $isUpdated = $this->botFlowRepository->updateBotFlowData($botFlowId, [
                        '__data' => json_encode($botFlowData)
                    ]);
                }
                // update buttons
                // following type of method to update JSON creates problem for mariadb 
                //  '__data->interaction_message->buttons' => $updateData['__data']['interaction_message']['buttons']
                // instead we have used it
                Arr::set($botData, 'interaction_message.buttons', $updateData['__data']['interaction_message']['buttons']);
                unset($updateData['__data']);
                if ($request->has('footer_text') and !$request->footer_text and !__isEmpty(data_get($botData, 'interaction_message.footer_text'))) {
                    Arr::set($botData, 'interaction_message.footer_text', '');
                    $updateData['__data'] = json_encode($botData);
                    $isUpdated = $this->botReplyRepository->updateForListAndButtonMessage($botReply->_id, $updateData);
                } else {
                    Arr::set($botData, 'interaction_message.footer_text', $request->footer_text);
                    $updateData['__data'] = $botData;
                    $isUpdated = $this->botReplyRepository->updateIt($botReply, $updateData);
                }
            } elseif ($interactiveType == 'list') {
                $listDataSections =  $botReply->__data['interaction_message']['list_data']['sections'] ?? [];
                $existingRowSubjects = [];
                foreach ($listDataSections as $listDataSection) {
                    foreach ($listDataSection['rows'] as $listDataSectionRow) {
                        $existingRowSubjects[] = $listDataSectionRow['title'];
                    }
                }
                $currentRowSubjects = [];
                foreach ($currentInputSectionsData as $currentInputSection) {
                    foreach ($currentInputSection['rows'] as $currentInputSectionRow) {
                        $currentRowSubjects[] = $currentInputSectionRow['title'];
                    }
                }

                $rowsRemoved = array_diff($existingRowSubjects, $currentRowSubjects);
                $rowsAdded = array_diff($currentRowSubjects, $existingRowSubjects);
                $existingBotsForTrigger = $this->botReplyRepository->fetchItAll($existingRowSubjects, [], 'reply_trigger', [
                    'where' => [
                        'bot_flows__id' => $botFlowId,
                        'bot_replies__id' => $botReply->_id,
                    ]
                ]);
                foreach ($existingBotsForTrigger as $existingBotForTrigger) {
                    $itemIndexInRemoved = array_search($existingBotForTrigger->reply_trigger, $rowsRemoved);
                    if ($itemIndexInRemoved !== false) {
                        $newReplyTrigger = $rowsAdded[$itemIndexInRemoved] ?? null;
                        $isUpdated = $this->botReplyRepository->updateIt($existingBotForTrigger, [
                            'reply_trigger' => $newReplyTrigger
                        ]);
                        if (!$newReplyTrigger) {
                            if (!empty($existingLinks)) {
                                $existingLinks = Arr::where($existingLinks, function ($value, $key) use (&$existingBotForTrigger) {
                                    return $value['toOperator'] != $existingBotForTrigger['_uid'];
                                });
                            }
                        }
                    }
                }
                // update flow links
                // following type of method to update JSON creates problem for mariadb
                //  '__data->flow_builder_data->links' => $existingLinks
                // instead we have used it
                Arr::set($botFlowData, 'flow_builder_data.links', $existingLinks);
                $isUpdated = $this->botFlowRepository->updateBotFlowData($botFlowId, [
                    '__data' => json_encode($botFlowData)
                ]);
                // following type of method to update JSON creates problem for mariadb
                //  '__data->interaction_message->buttons' => $updateData['__data']['interaction_message']['buttons']
                // instead we have used it
                $existingFooterText = data_get($botData, 'interaction_message.footer_text');
                Arr::set($botData, 'interaction_message', $updateData['__data']['interaction_message']);
                unset($updateData['__data']);
                if ($request->has('footer_text') and !$request->footer_text and !__isEmpty($existingFooterText)) {
                    Arr::set($botData, 'interaction_message.footer_text', '');
                    $updateData['__data'] = json_encode($botData);
                    $isUpdated = $this->botReplyRepository->updateForListAndButtonMessage($botReply->_id, $updateData);
                } else {
                    Arr::set($botData, 'interaction_message.footer_text', $request->footer_text);
                    $updateData['__data'] = $botData;
                    $isUpdated = $this->botReplyRepository->updateIt($botReply, $updateData);
                }
            } else {
                if ($request->has('footer_text') and !$request->footer_text and !__isEmpty(data_get($botData, 'interaction_message.footer_text'))) {
                    $isUpdated = $this->botReplyRepository->updateForListAndButtonMessage($botReply->_id, $updateData);
                } else {
                    $isUpdated = $this->botReplyRepository->updateIt($botReply, $updateData);
                }
            }

            $botActions = array_filter(data_get($inputData, 'bot_actions', []));

            // Check if message type is media message
            if ($messageType == 'media') {
                $botMediaUpdateData = $updateData['__data']['media_message'] ?? [];
                $botData['media_message'] = array_merge($botData['media_message'] ?? [], $botMediaUpdateData);
            }
            
            // Check if bot actions exists
            if (!__isEmpty($botActions)) {
                $this->botReplyRepository->updateForListAndButtonMessage($botReply->_id, [
                    '__data' => json_encode(array_merge($botData ?? [], [
                        'bot_actions' => $botActions
                    ]))
                ]);

                $isUpdated = true;
            }

            if ($isUpdated or $request->validate_bot_reply) {

                if ($request->validate_bot_reply) {
                    // Check if updated bot is template bot
                    if ($botReply->reply_text === '__IGNORE__') {
                        $request->merge($botReply->__data['template_message']['template_data']['inputs']);
                        $vendorId = getVendorId();
                        $testContactUid = getVendorSettings('test_recipient_contact');
                        $validateTestBotReply = $this->whatsAppServiceEngine->sendTemplateMessageProcess($request, $testContactUid, false, null, $vendorId, null);
                    } else {
                        $validateTestBotReply = $this->whatsAppServiceEngine->validateTestBotReply($botReply->_id);
                    }

                    if ($validateTestBotReply->success()) {
                        return $this->botReplyRepository->transactionResponse($validateTestBotReply->reaction(), [], $validateTestBotReply->message());
                    }
                }

                return $this->botReplyRepository->transactionResponse(1, [], __tr('Resource updated successfully.'));
            }

            return $this->botReplyRepository->transactionResponse(2, [], __tr('Resource not updated.'));
        });
        // if bot flow
        if ($botFlowId and ($engineResponse[0] == 1)) {
            // reloadPage
            return $this->engineResponse(21, [
                'reloadPage' => true
            ]);
        }
        return $this->engineResponse($engineResponse);
    }

    /**
     * Prepare All Active Bots
     *
     * @return  EngineResponse
     *---------------------------------------------------------------- */

    public function prepareAllActiveBots($contactIdOrUid)
    {
        // fetch the record
        $botReplies = $this->botReplyRepository->fetchBotReplies();

        // if successful
        return $this->engineResponse(1, [
            'bot_replies' => $botReplies,
            'contactIdOrUid' => $contactIdOrUid
        ]);
    }

    /**
     * Prepare Bot Preview
     *
     * @return  EngineResponse
     *---------------------------------------------------------------- */

    public function prepareBotPreview($botIdOrUid, $contactIdOrUid)
    {
        $botReply = $this->botReplyRepository->fetchIt($botIdOrUid);

        if (__isEmpty($botReply)) {
            return $this->engineResponse(18, null, __tr('Resource not found.'));
        }

        $contact = $this->contactRepository->fetchIt($contactIdOrUid);

        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }

        return $this->engineResponse(1, [
            'previewContent' => $this->whatsAppServiceEngine->prepareAiBotPreviewData($botReply, $contact),
        ]);
    }

    /**
     * Prepare All Active Bots
     *
     * @return  EngineResponse
     *---------------------------------------------------------------- */

    public function processSendTestBotReply($botId, $contactIdOrUid)
    {
        $botReply = $this->botReplyRepository->fetchIt([
            '_id' => $botId,
            'vendors__id' => getVendorId(),
        ]);

        if (__isEmpty($botReply)) {
            return $this->engineResponse(18, null, __tr('Bot Reply not found.'));
        }

        $contact = $this->contactRepository->fetchIt($contactIdOrUid);

        if (__isEmpty($contact)) {
            return $this->engineResponse(18, null, __tr('Contact not found.'));
        }

        $validateTestBotReply = $this->whatsAppServiceEngine->processReplyBot($contact, '', $botReply->_id, [
            'isTriggerFromQuickReply' => true
        ]);

        if ($validateTestBotReply->success()) {
            return $this->engineResponse(1, null, __tr('Bot Quick Reply Sent Successfully.'));
        }

        return $this->engineResponse(2, null, $validateTestBotReply->message());
    }

    public function getBotActionSupportData()
    {
        $vendorId = getVendorId();
        return [
            'vendorMessagingUsers' => $this->userRepository->getVendorMessagingUsers($vendorId),
            'allLabels' => $this->labelRepository->fetchItAll([
                'vendors__id' => $vendorId
            ])
        ];
    }
}
