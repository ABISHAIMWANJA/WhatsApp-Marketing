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
* WhatsAppTemplateController.php - Controller file
*
* This file is part of the WhatsAppService component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequest;
use App\Yantrana\Base\BaseRequestTwo;
use App\Yantrana\Components\WhatsAppService\WhatsAppTemplateEngine;
use Illuminate\Validation\Rule;

class WhatsAppTemplateController extends BaseController
{       /**
     * @var  WhatsAppTemplateEngine $whatsAppTemplateEngine - WhatsAppTemplate Engine
     */
    protected $whatsAppTemplateEngine;

    /**
      * Constructor
      *
      * @param  WhatsAppTemplateEngine $whatsAppTemplateEngine - WhatsAppTemplate Engine
      *
      * @return  void
      *-----------------------------------------------------------------------*/
    public function __construct(WhatsAppTemplateEngine $whatsAppTemplateEngine)
    {
        $this->whatsAppTemplateEngine = $whatsAppTemplateEngine;
    }

    /**
     * list of Templates
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function showTemplatesView()
    {
        validateVendorAccess('manage_templates');
        // load the view
        return $this->loadView('whatsapp-service.templates-list');
    }

    /**
     * list of Templates
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function prepareTemplatesList()
    {
        validateVendorAccess('manage_templates');
        // respond with dataTables preparations
        return $this->whatsAppTemplateEngine->prepareTemplatesDataTableSource();
    }

    /**
     * Create new template view
     *
     * @return view
     */
    public function createNewTemplate()
    {
        validateVendorAccess('manage_templates', 'add_edit_templates');

        $languages = include app_path('Yantrana/Support/languages.php');

        // load the view
        return $this->loadView('whatsapp-service.templates.new-template', [
            'languages' => $languages
        ]);
    }

    /**
     * New Template creation process
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function createNewTemplateProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_templates', 'add_edit_templates');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $validations = [
            'template_name' => [
                'required',
                'max:512',
                'alpha_dash',
            ],
            'language_code' => [
                'required',
                'max:15',
                'alpha_dash',
            ],
            'category' => [
                'required',
                Rule::in([
                    'MARKETING',
                    'UTILITY',
                    'AUTHENTICATION',
                ]),
            ],
        ];

        // Check if template type is header or carousel
        if ($request->template_type == 'header') {
            // Check template body validation
            $validations['template_body'] = [
                'required',
                'max:1024',
            ];
            // Check template footer validation
            $validations['template_footer'] = [
                'max:60',
                'nullable',
            ];

            if($request->media_header_type) {
                if(!in_array($request->media_header_type, [
                    'text',
                    'location',
                ])) {
                    $validations["uploaded_media_file_name"] = [
                        'required',
                    ];
                } elseif($request->media_header_type == 'text') {
                    $validations["header_text_body"] = [
                        'required',
                        'max:60',
                    ];
                }
            }
            // custom buttons
            if(!empty($request->message_buttons)) {
                foreach ($request->message_buttons as $customButtonKey => $customButton) {
                    // button texts
                    if (in_array($customButton['type'], [
                        'QUICK_REPLY','PHONE_NUMBER', 'URL_BUTTON', 'VOICE_CALL','DYNAMIC_URL_BUTTON'
                    ])) {
                        $validations["message_buttons.$customButtonKey.text"] = [
                            'required',
                            'max:25',
                        ];
                        // urls
                        if (in_array($customButton['type'], [
                            'URL_BUTTON',
                            'DYNAMIC_URL_BUTTON'
                        ])) {
                            $validations["message_buttons.$customButtonKey.url"] = [
                                'required',
                                'max:2000',
                                'url'
                            ];
                        }
                    }

                    // single example
                    if (in_array($customButton['type'], [
                        'COPY_CODE',
                        'DYNAMIC_URL_BUTTON'
                    ])) {
                        $validations["message_buttons.$customButtonKey.example"] = [
                            'required',
                            'alpha_dash'
                        ];
                    }
                    // phone number
                    if (in_array($customButton['type'], [
                        'PHONE_NUMBER',
                    ])) {
                        $validations["message_buttons.$customButtonKey.phone_number"] = [
                            'required',
                            'numeric'
                        ];
                    }
                }
            }
        } elseif ($request->template_type == 'carousel') {
            
            // Template body is required
            $validations['carousel_template_body'] = 'required';
            $validations['carousel_templates'] = 'required|array|min:2';
            // body media type will be image or video
            $validations['carousel_templates.*.header_type'] = ['required', Rule::in(['image', 'video'])];
            $validations['carousel_templates.*.uploaded_media_file_name'] = 'required';
            $validations['carousel_templates.*.carousel_card_body'] = 'required';
            $validations['carousel_templates.*.message_buttons'] = 'required|array|min:1|max:2';
            $validations['carousel_templates.*.message_buttons_count'] = 'required';
            
            foreach ($request->carousel_templates as $carouselIndex => $carouselValue) {
                // Check if card 1 message buttons exists
                if (!__isEmpty(data_get($carouselValue, 'message_buttons'))) {
                    foreach ($carouselValue['message_buttons'] as $card1MessageButtonKey => $card1MessageButtonValue) {
                        // Check quick reply text button validation
                        if ($card1MessageButtonValue['type'] == 'QUICK_REPLY') {
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.text"] = [
                                'required',
                                'max:25',
                            ];
                        }
                        // Check phone number button validation
                        if ($card1MessageButtonValue['type'] == 'PHONE_NUMBER') {
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.text"] = [
                                'required',
                                'max:25',
                            ];

                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.phone_number"] = [
                                'required',
                                'numeric',
                                'min_digits:9',
                                'min:1'
                            ];
                        }
                        // check url button validation
                        if ($card1MessageButtonValue['type'] == 'URL_BUTTON') {
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.text"] = [
                                'required',
                                'max:25',
                            ];
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.url"] = [
                                'required',
                                'max:2000',
                                'url'
                            ];
                        }
                    }
                }
            }
        }
        
        $request->validate($validations, [], [
            'carousel_templates.*.header_type' => __tr('header type'),
            'carousel_templates.*.uploaded_media_file_name' => __tr('media file'),
            'carousel_templates.*.carousel_card_body' => __tr('carousel card body'),
            'carousel_templates.*.message_buttons'   => __tr('buttons'),
            'carousel_templates.*.message_buttons.*' => __tr('button'),
            'carousel_templates.*.message_buttons.*.text' => __tr('text'),
            'carousel_templates.*.message_buttons.*.phone_number' => __tr('phone number'),
            'carousel_templates.*.message_buttons.*.url' => __tr('url'),
            'carousel_templates.*.message_buttons_count' => __tr('message buttons')
        ]);
        
        $processResponse = $this->whatsAppTemplateEngine->createOrUpdateTemplate($request);
        if($processResponse->success()) {
            return $this->processResponse(21, [], [
                'redirectUrl' => route('vendor.whatsapp_service.templates.read.list_view'),
                'show_message' => true,
                'messageType' => 'success',
            ], true);
        }
        return $this->processResponse($processResponse);
    }

    /**
     * Create new template view
     *
     * @return view
     */
    public function updateTemplate($templateUid)
    {
        validateVendorAccess('manage_templates', 'add_edit_templates');
        $whatsAppTemplateDate = $this->whatsAppTemplateEngine->prepareUpdateTemplateData($templateUid);

        if ($whatsAppTemplateDate->success()) {
            // load the view
            return $this->loadView('whatsapp-service.templates.update-template', $whatsAppTemplateDate->data(), [
                'compress_page' => false
            ]);
        }

        return redirect()->route('vendor.whatsapp_service.templates.read.list_view')->with([
            'alertMessage' => $whatsAppTemplateDate->message(),
            'alertMessageType' => 'error',
        ]);
    }

    /**
     * New Template creation process
     *
     * @param BaseRequestTwo $request
     * @return json
     */
    public function updateTemplateProcess(BaseRequestTwo $request)
    {
        validateVendorAccess('manage_templates', 'add_edit_templates');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $validations = [
            'template_uid' => [
                'required',
            ]
        ];

        if ($request->template_type == 'header') {

            $validations['template_body'] = [
                'required',
                'max:1024',
            ];

            $validations['template_footer'] = [
                'max:60',
                'nullable',
            ];

            if($request->media_header_type) {
                if(!in_array($request->media_header_type, [
                    'text',
                    'location',
                ])) {
                    $validations["uploaded_media_file_name"] = [
                        'required',
                    ];
                } elseif($request->media_header_type == 'text') {
                    $validations["header_text_body"] = [
                        'required',
                        'max:60',
                    ];
                }
            }
            // custom buttons
            if(!empty($request->message_buttons)) {
                foreach ($request->message_buttons as $customButtonKey => $customButton) {
                    // button texts
                    if (in_array($customButton['type'], [
                        'QUICK_REPLY','PHONE_NUMBER', 'URL_BUTTON', 'VOICE_CALL','DYNAMIC_URL_BUTTON'
                    ])) {
                        $validations["message_buttons.$customButtonKey.text"] = [
                            'required',
                            'max:25',
                        ];
                        // urls
                        if (in_array($customButton['type'], [
                            'URL_BUTTON',
                            'DYNAMIC_URL_BUTTON'
                        ])) {
                            $validations["message_buttons.$customButtonKey.url"] = [
                                'required',
                                'max:2000',
                                'url'
                            ];
                        }
                    }

                    // single example
                    if (in_array($customButton['type'], [
                        'COPY_CODE',
                        'DYNAMIC_URL_BUTTON'
                    ])) {
                        $validations["message_buttons.$customButtonKey.example"] = [
                            'required',
                            'alpha_dash'
                        ];
                    }
                    // phone number
                    if (in_array($customButton['type'], [
                        'PHONE_NUMBER',
                    ])) {
                        $validations["message_buttons.$customButtonKey.phone_number"] = [
                            'required',
                            'numeric'
                        ];
                    }
                }
            }
        } elseif ($request->template_type == 'carousel') {
            // Template body is required
            $validations['carousel_template_body'] = 'required';
            $validations['carousel_templates'] = 'required|array|min:2';
            // body media type will be image or video
            $validations['carousel_templates.*.header_type'] = ['required', Rule::in(['image', 'video'])];
            $validations['carousel_templates.*.uploaded_media_file_name'] = 'required';
            $validations['carousel_templates.*.carousel_card_body'] = 'required';
            $validations['carousel_templates.*.message_buttons'] = 'required|array|min:1|max:2';
            $validations['carousel_templates.*.message_buttons_count'] = 'required';

            foreach ($request->carousel_templates as $carouselIndex => $carouselValue) {
                // Check if card 1 message buttons exists
                if (!__isEmpty(data_get($carouselValue, 'message_buttons'))) {
                    $validations['carousel_templates.*.message_buttons'] = 'required|array|min:1|max:2';
                    foreach ($carouselValue['message_buttons'] as $card1MessageButtonKey => $card1MessageButtonValue) {
                        // Check quick reply text button validation
                        if ($card1MessageButtonValue['type'] == 'QUICK_REPLY') {
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.text"] = [
                                'required',
                                'max:25',
                            ];
                        }
                        // Check phone number button validation
                        if ($card1MessageButtonValue['type'] == 'PHONE_NUMBER') {
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.text"] = [
                                'required',
                                'max:25',
                            ];

                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.phone_number"] = [
                                'required',
                                'numeric',
                                'min_digits:9',
                                'min:1'
                            ];
                        }
                        // check url button validation
                        if ($card1MessageButtonValue['type'] == 'URL_BUTTON') {
                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.text"] = [
                                'required',
                                'max:25',
                            ];

                            $validations["carousel_templates.$carouselIndex.message_buttons.$card1MessageButtonKey.url"] = [
                                'required',
                                'max:2000',
                                'url'
                            ];
                        }
                    }
                }
            }
        }

        $request->validate($validations, [], [
            'carousel_templates.*.header_type' => __tr('header type'),
            'carousel_templates.*.uploaded_media_file_name' => __tr('media file'),
            'carousel_templates.*.carousel_card_body' => __tr('carousel card body'),
            'carousel_templates.*.message_buttons'   => __tr('buttons'),
            'carousel_templates.*.message_buttons.*' => __tr('button'),
            'carousel_templates.*.message_buttons.*.text' => __tr('text'),
            'carousel_templates.*.message_buttons.*.phone_number' => __tr('phone number'),
            'carousel_templates.*.message_buttons.*.url' => __tr('url'),
            'carousel_templates.*.message_buttons_count' => __tr('message buttons')
        ]);
        $processResponse = $this->whatsAppTemplateEngine->createOrUpdateTemplate($request);
        if($processResponse->success()) {
            return $this->processResponse(21, [], [
                'redirectUrl' => route('vendor.whatsapp_service.templates.read.list_view'),
                'show_message' => true,
                'messageType' => 'success',
            ], true);
        }
        return $this->processResponse($processResponse);
    }

    /**
     * Sync templates with Meta account
     *
     * @return json
     */
    public function syncTemplates()
    {
        validateVendorAccess([
            'messaging',
            'manage_templates'
        ]);
        if(!isWhatsAppBusinessAccountReady()) {
            return $this->processResponse(22, [
                22 => __tr('Please complete your WhatsApp Cloud API Setup first')
            ], [], true);
        }
        return $this->processResponse(
            $this->whatsAppTemplateEngine->processSyncTemplates(),
            [],
            [],
            true
        );
    }

    /**
     * Ask to delete the template
     *
     * @param BaseRequestTwo $request
     * @param mixed $whatsappTemplateId
     * @return json
     */
    public function deleteTemplate(BaseRequestTwo $request, $whatsappTemplateId)
    {
        validateVendorAccess('manage_templates', 'delete_templates');
        // restrict demo user
        if(isDemo() and isDemoVendorAccount()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        // ask engine to process the request
        $processReaction = $this->whatsAppTemplateEngine->processDeleteTemplate($whatsappTemplateId);

        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }

    /**
     * Ask to delete the template
     *
     * @param BaseRequestTwo $request
     * @param mixed $whatsappTemplateId
     * @return json
     */
    public function getTemplatesAnalytics($whatsappTemplateId)
    {
        validateVendorAccess('manage_templates');
        
        // ask engine to process the request
        $processReaction = $this->whatsAppTemplateEngine->prepareTemplateAnalytics($whatsappTemplateId);
        
        // load the view
        return $this->loadView('whatsapp-service.templates.template-analytics', [
            'analyticData' => $processReaction->data()
        ]);
    }

    /**
     * Ask to delete the template
     *
     * @param BaseRequestTwo $request
     * @param mixed $whatsappTemplateId
     * @return json
     */
    public function processTemplatesAnalytics(BaseRequest $request)
    {
        validateVendorAccess('manage_templates');

        $request->validate([
            'analytics_start_date' => 'required|before_or_equal:analytics_end_date|date',
            'analytics_end_date' => 'required|after_or_equal:analytics_start_date|date',
            'analytics_product_type' => 'required|string'
        ]);

        // ask engine to process the request
        $processReaction = $this->whatsAppTemplateEngine->processTemplateAnalytics($request->all());

        // get back with response
        return $this->processResponse($processReaction, [], [], true);
    }
}
