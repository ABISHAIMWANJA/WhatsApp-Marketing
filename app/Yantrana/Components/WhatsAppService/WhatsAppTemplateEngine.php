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
* WhatsAppTemplateEngine.php - Main component file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService;

use Illuminate\Support\Arr;
use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppApiService;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppTemplateRepository;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppTemplateEngineInterface;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use Carbon\Carbon;

class WhatsAppTemplateEngine extends BaseEngine implements WhatsAppTemplateEngineInterface
{
    /**
     * @var  WhatsAppTemplateRepository $whatsAppTemplateRepository - WhatsAppTemplate Repository
     */
    protected $whatsAppTemplateRepository;

    /**
     * @var WhatsAppApiService - WhatsApp API Service
     */
    protected $whatsAppApiService;

    /**
     * @var WhatsAppServiceEngine - WhatsAppService Engine
     */
    protected $whatsAppServiceEngine;

    /**
      * Constructor
      *
      * @param  WhatsAppTemplateRepository $whatsAppTemplateRepository - WhatsAppTemplate Repository
      * @param  WhatsAppApiService $whatsAppApiService - WhatsAppApiService
      *
      * @return  void
      *-----------------------------------------------------------------------*/

    public function __construct(
        WhatsAppTemplateRepository $whatsAppTemplateRepository,
        WhatsAppApiService $whatsAppApiService,
        WhatsAppServiceEngine $whatsAppServiceEngine
    ) {
        $this->whatsAppTemplateRepository = $whatsAppTemplateRepository;
        $this->whatsAppApiService = $whatsAppApiService;
        $this->whatsAppServiceEngine = $whatsAppServiceEngine;
    }

    /**
     * Templates datatable source
     *
     * @return array
     *---------------------------------------------------------------- */
    public function prepareTemplatesDataTableSource()
    {
        $templatesCollection = $this->whatsAppTemplateRepository->fetchTemplatesDataTableSource();
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'template_name',
            'template_id',
            'language',
            'category',
            'status',
            'updated_at' => function ($templateData) {
                return formatDateTime($templateData['updated_at']);
            },
        ];

        // prepare data for the DataTables
        return $this->dataTableResponse($templatesCollection, $requireColumns);
    }

    /**
     * Prepare template update data
     *
     * @return EngineResponse
     */
    public function prepareUpdateTemplateData($whatsAppTemplateUid)
    {
        $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt($whatsAppTemplateUid);
        abortIf(__isEmpty($whatsAppTemplate), 404, __tr('Template not found'));
        $whatsAppTemplateData = Arr::get($whatsAppTemplate->toArray(), '__data.template');

        if (__isEmpty($whatsAppTemplateData)) {
            return $this->engineFailedResponse([], __tr('Template data not found on META'));
        }
        
        // Check if it is a carousel template
        if (data_get($whatsAppTemplateData, 'components.1.type') == 'CAROUSEL') {
            $bodyTextVariables = $carouselTemplateContainer = [];
            $components = $whatsAppTemplateData['components'];
            
            // Check if body text exists
            if (!__isEmpty(data_get($components, '0.example'))) {
                $carouselBodyVariables = data_get($components, '0.example.body_text');
                foreach ($carouselBodyVariables[0] as $variableIndex => $variable) {
                    $bodyTextVariables[] = [
                        'text_variable' => '{{' . ($variableIndex + 1) . '}}',
                        'text_variable_value' => $variable
                    ];
                }
            }

            $cardContainer = data_get($components, '1.cards');
            foreach ($cardContainer as $cards) {
                $cardBodyTextVariables = [];
                foreach ($cards as $card) {
                    if (!__isEmpty(data_get($card, '1.example'))) {
                        $bodyTextVariableData = data_get($card, '1.example.body_text');
                        foreach ($bodyTextVariableData[0] as $bodyDataIndex => $bodyData) {
                            $cardBodyTextVariables[] = [
                                'text_variable' => '{{' . ($bodyDataIndex + 1) . '}}',
                                'text_variable_value' => $bodyData
                            ];
                        }
                    }
                    
                    $quickReplyBtn = $phoneNumberButton = $urlButton = 0;
                    $cardButtonData = data_get($card, '2.buttons');
                    $cardButtons = new \stdClass();
                    $buttonUsedByTypes = new \stdClass();
                    $cardButtonsData = [];
                    // Check if button data exists
                    if (!__isEmpty($cardButtonData)) {
                        foreach ($cardButtonData as $btnIndex => $buttonData) {
                            $buttonType = $buttonData['type'];

                            if ($buttonType == 'QUICK_REPLY') {
                                $cardButtonsData[] = [
                                    'buttonType' => $buttonData['type'],
                                    'buttonText' => $buttonData['text'],
                                    'buttonIndex' => $btnIndex,
                                ];
                                $quickReplyBtn = 1;
                            }
                            
                            if ($buttonType == 'PHONE_NUMBER') {
                                $cardButtonsData[] = [
                                    'buttonType' => $buttonData['type'],
                                    'buttonText' => $buttonData['text'],
                                    'phoneNumber' => cleanDisplayPhoneNumber($buttonData['phone_number']),
                                    'buttonIndex' => $btnIndex,
                                ];
                                $phoneNumberButton = 1;
                            }

                            if ($buttonType == 'URL') {
                                $cardButtonsData[] = [
                                    'buttonType' => $buttonData['type'],
                                    'buttonText' => $buttonData['text'],
                                    'url' => $buttonData['url'],
                                    'example' => data_get($buttonData, 'example'),
                                    'buttonIndex' => $btnIndex,
                                ];
                                $urlButton = 1;
                            }
                        }
                    }
                    
                    $buttonUsedByTypes->QUICK_REPLY = $quickReplyBtn;
                    $buttonUsedByTypes->QUICK_REPLY_LIMIT = 1;
                    $buttonUsedByTypes->URL = $urlButton;
                    $buttonUsedByTypes->URL_LIMIT = 1;
                    $buttonUsedByTypes->PHONE_NUMBER = $phoneNumberButton;
                    $buttonUsedByTypes->PHONE_NUMBER_LIMIT = 1;
                    $cardButtons->totalAllowedButtons = 2;
                    $cardButtons->totalButtonsUsed = count($cardButtonData);
                    $cardButtons->buttonUsesByTypes = $buttonUsedByTypes;
                    $cardButtons->data = $cardButtonsData;

                    $carouselTemplateContainer[] = [
                        'headerType' => strtolower($card[0]['format']),
                        'bodyText' => $card[1]['text'],
                        'bodyTextVariables' => $cardBodyTextVariables,
                        'cardButtons' => $cardButtons
                    ];
                }
            }
            // __dd($carouselTemplateContainer);
            updateClientModels([
                'templateType' => 'carousel',
                'carousel_body_text' => data_get($components, '0.text'),
                'carouselBodyTextVariables' => $bodyTextVariables,
                'carouselTemplateContainer' => $carouselTemplateContainer,
                'totalUsedCards' => count($cardContainer)
            ]);
            
        } else {
            updateClientModels([
                'templateType' => 'header'
            ]);
        }

        return $this->engineSuccessResponse([
            'whatsAppTemplateUid' => $whatsAppTemplateUid,
            'whatsAppTemplateData' => $whatsAppTemplateData,
        ]);
    }

    /**
     * Prepare carousel template data
     *
     * @return array
     */
    protected function prepareCarouselTemplateData($request, $isTemplateUpdateRequest) 
    {
        $carouselBodyData = [
            "type" => "body",
            "text" => $request->carousel_template_body
        ];

        $carouselExampleBodyFields = ($isTemplateUpdateRequest == true) ? $request->carousel_example_body_fields : $request->example_body_fields;
        
        // Check if dynamic variables exists
        if (!__isEmpty($carouselExampleBodyFields)) {
            $carouselBodyData['example'] = [
                'body_text' => [
                    array_values($carouselExampleBodyFields)
                ]
            ];
        }

        $carouselCardData = [];
        foreach ($request->carousel_templates as $carouselData) {
            // Prepare card header data
            $headerData = [
                "type" => "header",
                "format" => $carouselData['header_type'],
                "example" => [
                    "header_handle" => [
                        $this->whatsAppApiService->uploadResumableMedia($carouselData['uploaded_media_file_name'])
                    ]
                ]
            ];
            // Prepare data for card body
            $bodyData = [
                "type" => "body",
                "text" => $carouselData['carousel_card_body']
            ];
            // Check if card body have dynamic variables
            if (!__isEmpty(data_get($carouselData, 'body_example_fields'))) {
                $bodyData['example'] = [
                    'body_text' => [
                        array_values($carouselData['body_example_fields'])
                    ]
                ];
            }
            // Create an array for buttons
            $buttonData = [
                "type" => "buttons",
            ];
            
            // Check if card button data exists
            if (!__isEmpty($carouselData['message_buttons'])) {
                foreach ($carouselData['message_buttons'] as $messageButton) {
                    if ($messageButton['type'] == 'QUICK_REPLY') {
                        $cardButton = [
                            "type" => $messageButton['type'],
                            "text" => $messageButton['text']
                        ];
                    } elseif ($messageButton['type'] == 'URL') {
                        $cardButton = [
                            "type" => $messageButton['type'],
                            "text" => $messageButton['text'],
                            "url" => $messageButton['url'],
                        ];
                        if (!__isEmpty(data_get($messageButton, 'example'))) {
                            $cardButton["example"] = [$messageButton['example']];
                            $cardButton["url"] = $messageButton['url'].'{{1}}';
                        }
                    } elseif ($messageButton['type'] == 'PHONE_NUMBER') {
                        $cardButton = [
                            "type" => $messageButton['type'],
                            "text" => $messageButton['text'],
                            "phone_number" => $messageButton['phone_number']
                        ];
                    }
                    
                    $buttonData['buttons'][] = $cardButton;
                }
            }

            $carouselCardData[] = [
                'components' => [
                    $headerData,
                    $bodyData,
                    $buttonData
                ]
            ];
        }

        $carouselComponentData = [
            $carouselBodyData,
            [
                "type" => "carousel",
                "cards" => $carouselCardData
            ]
        ];
        
        return $carouselComponentData;
    }

    /**
     * Create Template
     *
     * @param BaseRequest $request
     * @return EngineResponse
     * @link https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates
     * @link for Carousel - https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates/media-card-carousel-templates
     */
    public function createOrUpdateTemplate($request)
    {
        $vendorId = getVendorId();
        $components = [];
        // https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates/components#media-headers
        if ($request->template_type == 'header') {
            if($request->media_header_type) {
                if ($request->media_header_type == 'text') {
                    $components[] = [
                        "type" => "HEADER",
                        "format" => "TEXT",
                        "text" => $request->header_text_body,
                    ];
                    // example fields
                    if($request->example_header_fields) {
                        $components[(count($components) - 1)]['example'] = [
                            "header_text" => [
                                $request->example_header_fields
                            ]
                        ];
                    }
                } elseif(in_array($request->media_header_type, [
                    'image', 'video', 'document'
                ])) {
                    $components[] = [
                        "type" => "HEADER",
                        "format" => strtoupper($request->media_header_type),
                        "text" => $request->header_body,
                        'example' => [
                            'header_handle' => [
                                $this->whatsAppApiService->uploadResumableMedia($request->uploaded_media_file_name)
                            ]
                        ]
                    ];
                } elseif($request->media_header_type == 'location') {
                    $components[] = [
                        "type" => "HEADER",
                        "format" => strtoupper($request->media_header_type),
                    ];
                }
            }
            // body text
            if($request->template_body) {
                $components[] = [
                    "type" => "BODY",
                    "text" => $request->template_body,
                ];
                if(!empty($request->example_body_fields) and is_array($request->example_body_fields)) {
                    $components[(count($components) - 1)]['example'] = [
                        "body_text" => [
                            $request->example_body_fields
                        ]
                    ];
                }
            }
            if($request->template_footer) {
                $components[] = [
                    "type" => "FOOTER",
                    "text" => $request->template_footer
                ];
            }
            if(!empty($request->message_buttons)) {
                $buttons = [];
                $buttonIndex = 0;
                $buttonTypes = [
                    'QUICK_REPLY' => 'QUICK_REPLY',
                    'PHONE_NUMBER' => 'PHONE_NUMBER',
                    'URL_BUTTON' => 'URL',
                    'VOICE_CALL' => 'VOICE_CALL',
                    'DYNAMIC_URL_BUTTON' => 'URL',
                    'COPY_CODE' => 'COPY_CODE',
                ];
                foreach ($request->message_buttons as $customButtonKey => $customButton) {
                    $buttons[$buttonIndex] = [
                        'type' => $buttonTypes[$customButton['type']],
                    ];
                    // -----
                    if (in_array($customButton['type'], [
                        'QUICK_REPLY','PHONE_NUMBER', 'URL_BUTTON', 'VOICE_CALL','DYNAMIC_URL_BUTTON'
                    ])) {
                        $buttons[$buttonIndex]['text'] = $customButton['text'];
                        // urls
                        if (in_array($customButton['type'], [
                            'URL_BUTTON',
                            'DYNAMIC_URL_BUTTON'
                        ])) {
                            $buttons[$buttonIndex]['url'] = $customButton['url'];
                        }
                    }
                    // single example
                    if (in_array($customButton['type'], [
                        'COPY_CODE',
                    ])) {
                        $buttons[$buttonIndex]['example'] = $customButton['example'];
                    }
                    if (in_array($customButton['type'], [
                        'DYNAMIC_URL_BUTTON'
                    ])) {
                        $buttons[$buttonIndex]['url'] = $customButton['url'] . '{{1}}';
                        $buttons[$buttonIndex]['example'] = [
                            $customButton['example']
                        ];
                    }
                    // phone number
                    if (in_array($customButton['type'], [
                        'PHONE_NUMBER',
                    ])) {
                        $buttons[$buttonIndex]['phone_number'] = $customButton['phone_number'];
                    }
                    // ----
                    $buttonIndex++;
                }
                if(!empty($buttons)) {
                    $components[] = [
                        "type" => "BUTTONS",
                        "buttons" => $buttons
                    ];
                }
            }
        } elseif ($request->template_type == 'carousel') {
            $isTemplateUpdateRequest = $request->template_uid ? true : false;
            $components = $this->prepareCarouselTemplateData($request, $isTemplateUpdateRequest);
        }
        if(!app('yadrichhikParikshan')()) {
                return $this->engineFailedResponse([], __tr('Template has been rejected due to __rejectedReason__', [
                '__rejectedReason__' => 'UNKNOWN'
            ]));
        }
        // template update
        if($request->template_uid) {
            $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt($request->template_uid);
            abortIf(__isEmpty($whatsAppTemplate), null, __tr('Template not found'));
            $whatsAppTemplateData = Arr::get($whatsAppTemplate->toArray(), '__data.template');
            $createTemplateRequest = $this->whatsAppApiService->updateTemplate(
                $whatsAppTemplateData['id'],
                $whatsAppTemplateData['name'],
                $components,
                $vendorId
            );
            if($createTemplateRequest['success'] == 1) {
                return $this->engineSuccessResponse([], __tr('Your template has been updated'));
            }
            return $this->engineSuccessResponse([], __tr('Failed to update template'));
        } else  {
            // create new template
            $createTemplateRequest = $this->whatsAppApiService->createTemplate(
                $request->template_name,
                $request->language_code,
                $request->category,
                $components,
                $vendorId
            );
        }

        if($createTemplateRequest['status'] == 'REJECTED') {
            $this->processSyncTemplates();
            $rejectedReason = $this->whatsAppApiService->getTemplateRejectionReason($createTemplateRequest['id']);
            return $this->engineFailedResponse([], __tr('Template has been rejected due to __rejectedReason__', [
                '__rejectedReason__' => $rejectedReason['rejected_reason']
            ]));
        } elseif($createTemplateRequest['status'] == 'APPROVED') {
            $this->processSyncTemplates();
            return $this->engineSuccessResponse([], __tr('Your template has been created and approved'));
        }
        $this->processSyncTemplates();
        return $this->engineSuccessResponse([], __tr('Your template has submitted for review and it is now __templateStatus__', [
            '__templateStatus__' => $createTemplateRequest['status']
        ]));
    }

    /**
     * Sync templates with WhatsApp Cloud API
     *
     * @return EngineResponse
     */
    public function processSyncTemplates()
    {
        // fetch the whatsapp templates from api
        // @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-account/message_templates
        $whatsAppTemplates = $this->whatsAppApiService->getTemplates();
        $templatesToAdd = [];
        $vendorId = getVendorId();
        foreach ($whatsAppTemplates as $whatsAppTemplateIndex => $whatsAppTemplate) {
            $templatesToAdd[] = [
                'template_name' => $whatsAppTemplate['name'],
                'language' => $whatsAppTemplate['language'],
                'template_id' => $whatsAppTemplate['id'],
                'category' => $whatsAppTemplate['category'],
                'status' => $whatsAppTemplate['status'],
                'language' => $whatsAppTemplate['language'],
                '__data' => [
                    'template' => $whatsAppTemplate,
                ],
                'vendors__id' => $vendorId,
            ];
        }
        
        if(!app('yadrichhikParikshan')()) {
            return $this->engineResponse(14, [], __tr('Nothing Updated'));
        }
        if ($this->whatsAppTemplateRepository->syncTemplates($templatesToAdd)) {
            return $this->engineSuccessResponse(['reloadDatatableId' => '#lwTemplatesList'], __tr('Templates Sync successfully'));
        }
        return $this->engineResponse(14, [], __tr('Nothing Updated'));
    }

    /**
     * Delete the requested template
     *
     * @param  string|int  $whatsappTemplateUid
     * @return EngineResponse
     */
    public function processDeleteTemplate($whatsappTemplateUid)
    {
        $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt($whatsappTemplateUid);
        abortIf(__isEmpty($whatsAppTemplate), null, __tr('Template not found in the system'));
        $deleteTemplate = $this->whatsAppApiService->deleteTemplate($whatsAppTemplate->template_name, $whatsAppTemplate->template_id);
        if (isset($deleteTemplate['success']) and $deleteTemplate['success']) {
            $this->processSyncTemplates();
            return $this->engineSuccessResponse(['reloadDatatableId' => '#lwTemplatesList'], __tr('Template deleted successfully.'));
        }

        return $this->engineFailedResponse([], __tr('Failed to delete template'));
    }

    /**
     * Prepare template update data
     *
     * @return EngineResponse
     */
    public function prepareApprovedTemplates()
    {
        $whatsAppTemplates = $this->whatsAppTemplateRepository->getApprovedTemplatesByNewest();
        
        return $this->engineSuccessResponse([
            'whatsAppTemplates' => $whatsAppTemplates,
            'template' => '',
            'templatePreview' => '',
        ]);
    }

    /**
     * Prepare template update data
     *
     * @return EngineResponse
     */
    public function prepareTemplateAnalytics($whatsappTemplateId)
    {
        $whatsAppTemplates = $this->whatsAppTemplateRepository->fetchIt([
            '_uid' => $whatsappTemplateId
        ]);
        
        // Check if template exits
        if (__isEmpty($whatsAppTemplates)) {
            return $this->engineFailedResponse([], __tr('Template does not exists'));
        }

        $whatsAppTemplateData = [
            'name' => $whatsAppTemplates->template_name,
            'language' => $whatsAppTemplates->language,
            'category' =>  $whatsAppTemplates->category,
            'status' => $whatsAppTemplates->status,
        ];
        
        // Get all preset duration
        $presetDuration = $this->getPresetDuration();

        // Get last week preset 
        $lastWeek = $this->getPresetDuration(4);

        $templateAnalytics = $this->processTemplateAnalytics([
            'analytics_start_date' => formatDate($lastWeek['start'], 'Y-m-d'),
            'analytics_end_date' => formatDate($lastWeek['end'], 'Y-m-d'),
            'template_id' => $whatsAppTemplates->template_id,
            'analytics_product_type' => 'CLOUD_API',
        ], getVendorId());

        $initialTemplateAnalyticsData = $templateAnalytics->data();

        updateClientModels([
            'isDataLoaded' => false,
            'analyticsData' => [],
            'templateId' => $whatsAppTemplates->template_id,
            'presetDuration' => $presetDuration,
            'analyticDurationPreset' => 4,
            'analyticStartDate' => $lastWeek['start'],
            'analyticEndDate' => $lastWeek['end'],
            'analyticsData' => $initialTemplateAnalyticsData['analyticsData'],
            'cursorAfter' => $initialTemplateAnalyticsData['cursorAfter'],
            'messageCountData' => $initialTemplateAnalyticsData['messageCountData'],
            'isDataLoaded' => true
        ]);

        $templatePreviewData = $this->whatsAppServiceEngine->processTemplateChange($whatsAppTemplates->_id, 'show-only-preview');

        return $this->engineSuccessResponse([
            'templateId' => $whatsAppTemplates->template_id,
            'presetDuration' => $presetDuration,
            'whatsAppTemplateData' => $whatsAppTemplateData,
            'templatePreview' => $templatePreviewData->data()['template']
        ]);
    }

    /**
     * Prepare template update data
     *
     * @return EngineResponse
     */
    public function processTemplateAnalytics($inputData)
    {
        $templateAnalyticsData = $this->whatsAppApiService->getTemplateAnalytics($inputData, getVendorId());
        
        $templateAnalyticsDetails = data_get($templateAnalyticsData, 'data.0.data_points');
        $cursorAfter = '';
        $processType = null;
        // Check if pagination exists
        if (!__isEmpty(data_get($templateAnalyticsData, 'paging.next'))) {
            $cursorAfter = data_get($templateAnalyticsData, 'paging.cursors.after');
        }

        $totalSentCount = $totalRepliedCount = $totalDeliveredCount = $totalReadCount = 0;
        // Check if request for load more content
        if (!__isEmpty($inputData['cursor_after'] ?? null)) {
            $processType = 'append';
            $totalDeliveredCount = $inputData['total_delivered_count'] ?? 0;
            $totalReadCount = $inputData['total_read_count'] ?? 0;
            $totalSentCount = $inputData['total_send_count'] ?? 0;
            $totalRepliedCount = $inputData['total_replied_count'] ?? 0;
        }
        
        $analyticsData = [];
        // Check if analytics data exists
        if (!__isEmpty($templateAnalyticsDetails)) {
            foreach($templateAnalyticsDetails as $templateAnalytics) {
                $delivered = $templateAnalytics['delivered'] ?? 0;
                $read = $templateAnalytics['read'] ?? 0;
                $readPercentage = $delivered > 0
                                    ? min(100, round(($read / $delivered) * 100))
                                    : 0;

                $analyticsData[md5(uniqid(rand(), true))] = [
                    'startDate' => formatDate($templateAnalytics['start'], 'Y/m/d'),
                    'endDate' => formatDate($templateAnalytics['end'], 'Y/m/d'),
                    'sent' => $templateAnalytics['sent'] ?? 0,
                    'delivered' => $delivered,
                    'read' => $read,
                    'readPercentage' => __tr('(__percentage__%)', ['__percentage__' => $readPercentage]),
                    'replied' => $templateAnalytics['replied'] ?? 0,
                    'clicked' => $templateAnalytics['clicked'] ?? [],
                ];
            }
        }

        $analyticsCollection = collect($analyticsData);

        $totalSentCount += $analyticsCollection->sum(fn ($row) => $row['sent'] ?? 0);
        $totalRepliedCount += $analyticsCollection->sum(fn ($row) => $row['replied'] ?? 0);
        $totalDeliveredCount += $analyticsCollection->sum(fn ($row) => $row['delivered'] ?? 0);
        $totalReadCount += $analyticsCollection->sum(fn ($row) => $row['read'] ?? 0);
        $totalReadPercentage = $totalDeliveredCount > 0
                                    ? min(100, round(($totalReadCount / $totalDeliveredCount) * 100))
                                    : 0;

        $messageCountData = [
            'totalSentCount' => $totalSentCount,
            'totalDeliveredCount' => $totalDeliveredCount,
            'totalReadCount' => $totalReadCount,
            'totalRepliedCount' => $totalRepliedCount,
            'totalReadPercentage' => __tr('(__percentage__%)', ['__percentage__' => $totalReadPercentage]),
        ];

        $durationStartDate = $analyticsCollection->first()['startDate'] ?? null;
        $durationEndDate = $analyticsCollection->last()['endDate'] ?? null;
        $durationMessage = '';
        // Check if duration exists
        if (!__isEmpty($durationStartDate) and !__isEmpty($durationEndDate)) {
            $durationMessage = __tr('Showing result start from __durationStartDate__ till __durationEndDate__.', [
                '__durationStartDate__' => '<strong>'.$durationStartDate.'</strong>',
                '__durationEndDate__' => '<strong>'.$durationEndDate.'</strong>'
            ]);
        }

        updateClientModels([
            'analyticsData' => $analyticsData,
            'cursorAfter' => $cursorAfter,
            'isDataLoaded' => true,
            'messageCountData' => $messageCountData,
            'durationMessage' => $durationMessage,

        ], $processType);

        return $this->engineSuccessResponse([
            'analyticsData' => $analyticsData,
            'cursorAfter' => $cursorAfter,
            'messageCountData' => $messageCountData
        ]);
    }

    /**
     * Prepare preset duration
     *
     * @return EngineResponse
     */
    protected function getPresetDuration($itemId = null)
    {
        $today = now();
        $endOfMonth = now()->endOfMonth();
        $endOfWeek = now()->endOfWeek();

        $newEndOfMonthDate = $endOfMonth->greaterThan($today)
            ? $today
            : $endOfMonth;

        $newEndOfWeekDate = $endOfWeek->greaterThan($today)
            ? $today
            : $endOfWeek;
            
        $presetDuration = [
            [
                'id'    => 1,
                'name'  => __tr('Current Month'),
                'start' => now()->startOfMonth()->format('Y-m-d'),
                'end'   => $newEndOfMonthDate->format('Y-m-d')
            ],
            [
                'id'    => 2,
                'name'  => __tr('Last Month'),
                'start' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end'   => now()->subMonth()->endOfMonth()->format('Y-m-d')
            ],
            [
                'id'    => 3,
                'name'  => __tr('Current Week'),
                'start' => now()->startOfWeek()->subDay()->format('Y-m-d'),
                'end'   => $newEndOfWeekDate->format('Y-m-d')
            ],
            [
                'id'    => 4,
                'name'  => __tr('Last Week'),
                'start' => now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'end'   => now()->subWeek()->endOfWeek()->format('Y-m-d')
            ],
            [
                'id'    => 5,
                'name'  => __tr('Today'),
                'start' => now()->startOfDay()->subDay()->format('Y-m-d'),
                'end'   => $today->format('Y-m-d')
            ],
            [
                'id'    => 6,
                'name'  => __tr('Yesterday'),
                'start' => now()->subDays(2)->startOfDay()->format('Y-m-d'),
                'end'   => now()->subDay()->endOfDay()->format('Y-m-d')
            ],
            [
                'id'    => 7,
                'name'  => __tr('Custom'),
                'start' => now()->startOfDay()->format('Y-m-d'),
                'end'   => now()->endOfDay()->format('Y-m-d')
            ]
        ];

        if (!__isEmpty($itemId)) {
            return collect($presetDuration)->firstWhere('id', $itemId);
        }

        return $presetDuration;
    }

    /**
     * Prepare Template list for API
     *
     * @return EngineResponse
     */
    public function prepareTemplateList()
    {
        $templateListData = $this->whatsAppTemplateRepository->fetchTemplateListPaginatedData();

        // if successful
        return $this->engineSuccessResponse([
            'templateList' => $templateListData
        ], __tr('Whatsapp Template List.'));
    }
}
