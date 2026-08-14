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
 * WhatsAppServiceEngine.php - Main component file
 *
 * This file is part of the WhatsAppService component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService;

use Request;
use Exception;
use YesSecurity;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Client\Pool;
use App\Yantrana\Base\BaseEngine;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Events\VendorChannelBroadcast;
use App\Events\WhatsappWebhookReceived;
use App\Jobs\ProcessMessageWebhookJob;
use App\Jobs\ProcessCampaignMessagesJob;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\RequestException;
use App\Yantrana\Components\Media\MediaEngine;
use Illuminate\Http\Client\ConnectionException;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use App\Yantrana\Components\User\Repositories\UserRepository;
use App\Yantrana\Components\Configuration\ConfigurationEngine;
use App\Yantrana\Components\Contact\Repositories\LabelRepository;
use App\Yantrana\Services\System\ServerPerformanceMonitorService;
use App\Yantrana\Components\Contact\Repositories\ContactRepository;
use App\Yantrana\Components\WhatsAppService\Services\OpenAiService;
use App\Yantrana\Components\BotReply\Repositories\BotReplyRepository;
use App\Yantrana\Components\Campaign\Repositories\CampaignRepository;
use App\Yantrana\Components\Contact\Repositories\ContactGroupRepository;
use App\Yantrana\Components\Contact\Repositories\GroupContactRepository;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppWebhookModel;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppApiService;
use App\Yantrana\Components\Contact\Repositories\ContactCustomFieldRepository;
use App\Yantrana\Components\WhatsAppService\Services\WhatsAppConnectApiService;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppTemplateRepository;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppServiceEngineInterface;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppMessageLogRepository;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppMessageQueueRepository;
use App\Yantrana\Components\Contact\Repositories\ContactLabelRepository;

class WhatsAppServiceEngine extends BaseEngine implements WhatsAppServiceEngineInterface
{
    /**
     * @var ContactRepository - Contact Repository
     */
    protected $contactRepository;

    /**
     * @var ContactGroupRepository - ContactGroup Repository
     */
    protected $contactGroupRepository;

    /**
     * @var GroupContactRepository - ContactGroup Repository
     */
    protected $groupContactRepository;

    /**
     * @var WhatsAppTemplateRepository - WhatsApp Template Repository
     */
    protected $whatsAppTemplateRepository;

    /**
     * @var WhatsAppApiService - WhatsApp API Service
     */
    protected $whatsAppApiService;

    /**
     * @var MediaEngine - Media Engine
     */
    protected $mediaEngine;

    /**
     * @var WhatsAppMessageLogRepository - Status repository
     */
    protected $whatsAppMessageLogRepository;

    /**
     * @var WhatsAppMessageQueueRepository - WhatsApp Message Queue repository
     */
    protected $whatsAppMessageQueueRepository;
    /**
     * @var CampaignRepository - Campaign repository
     */
    protected $campaignRepository;

    /**
     * @var BotReplyRepository - Bot Reply repository
     */
    protected $botReplyRepository;

    /**
     * @var VendorSettingsEngine - Vendor Settings Engine
     */
    protected $vendorSettingsEngine;

    /**
     * @var UserRepository - UserRepository
     */
    protected $userRepository;

    /**
     * @var ConfigurationEngine - configurationEngine
     */
    protected $configurationEngine;

    /**
     * @var ContactCustomFieldRepository - ContactGroup Repository
     */
    protected $contactCustomFieldRepository;

    /**
     * @var WhatsAppConnectApiService - WhatsApp Connect Service
     */
    protected $whatsAppConnectApiService;

    /**
     * @var LabelRepository - Label Repository
     */
    protected $labelRepository;

    /**
     * @var ContactLabelRepository - Contact Label Repository
     */
    protected $contactLabelRepository;

    /**
     * Constructor
     *
     * @param  ContactRepository  $contactRepository  - Contact Repository
     * @param  ContactGroupRepository  $contactGroupRepository  - ContactGroup Repository
     * @param  GroupContactRepository  $groupContactRepository  - Group Contacts Repository
     * @param  WhatsAppTemplateRepository  $whatsAppTemplateRepository  - WhatsApp Templates Repository
     * @param  WhatsAppApiService  $whatsAppApiService  - WhatsApp API Service
     * @param  WhatsAppMessageQueueRepository  $whatsAppMessageQueueRepository  - WhatsApp Message Queue
     * @param  CampaignRepository  $campaignRepository  - Campaign repository
     * @param  BotReplyRepository  $botReplyRepository  - Bot Reply repository
     * @param  VendorSettingsEngine  $vendorSettingsEngine  - Configuration Engine
     * @param  UserRepository  $userRepository  - Users Repository
     * @param  ConfigurationEngine  $configurationEngine  - Configuration Engine
     * @param  WhatsAppConnectApiService  $whatsAppConnectApiService  - WhatsApp Connect Service
     * @param  ContactCustomFieldRepository  $contactCustomFieldRepository  - Contacts Custom  Fields Repository
     * @param  LabelRepository  $labelRepository  - Label Repository
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        ContactRepository $contactRepository,
        ContactGroupRepository $contactGroupRepository,
        GroupContactRepository $groupContactRepository,
        WhatsAppTemplateRepository $whatsAppTemplateRepository,
        WhatsAppApiService $whatsAppApiService,
        MediaEngine $mediaEngine,
        WhatsAppMessageLogRepository $whatsAppMessageLogRepository,
        WhatsAppMessageQueueRepository $whatsAppMessageQueueRepository,
        CampaignRepository $campaignRepository,
        BotReplyRepository $botReplyRepository,
        VendorSettingsEngine $vendorSettingsEngine,
        UserRepository $userRepository,
        ConfigurationEngine $configurationEngine,
        WhatsAppConnectApiService $whatsAppConnectApiService,
        ContactCustomFieldRepository $contactCustomFieldRepository,
        LabelRepository $labelRepository,
        ContactLabelRepository $contactLabelRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactGroupRepository = $contactGroupRepository;
        $this->groupContactRepository = $groupContactRepository;
        $this->whatsAppTemplateRepository = $whatsAppTemplateRepository;
        $this->whatsAppApiService = $whatsAppApiService;
        $this->mediaEngine = $mediaEngine;
        $this->whatsAppMessageLogRepository = $whatsAppMessageLogRepository;
        $this->whatsAppMessageQueueRepository = $whatsAppMessageQueueRepository;
        $this->campaignRepository = $campaignRepository;
        $this->botReplyRepository = $botReplyRepository;
        $this->vendorSettingsEngine = $vendorSettingsEngine;
        $this->userRepository = $userRepository;
        $this->configurationEngine = $configurationEngine;
        $this->whatsAppConnectApiService = $whatsAppConnectApiService;
        $this->contactCustomFieldRepository = $contactCustomFieldRepository;
        $this->labelRepository = $labelRepository;
        $this->contactLabelRepository = $contactLabelRepository;
    }

    /**
     * Get Contact Info
     *
     * @param  string  $contactUid
     * @return EngineResponse
     */
    public function sendMessageData($contactUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->getVendorContact($contactUid, $vendorId);
        abortIf(__isEmpty($contact));
        $contact->wa_id = maskString($contact->wa_id, 'phone');
        $contact->email = maskString($contact->email, 'email');
        $whatsAppApprovedTemplates = $this->whatsAppTemplateRepository->getApprovedTemplatesByNewest();

        return $this->engineSuccessResponse([
            'contact' => $contact,
            'whatsAppTemplates' => $whatsAppApprovedTemplates,
            'template' => '',
            'templatePreview' => '',
        ]);
    }

    /**
     * Get Contact Info
     *
     * @param  string  $contactUid
     * @return EngineResponse
     */
    public function campaignRequiredData()
    {
        $vendorId = getVendorId();
        // templates
        $whatsAppApprovedTemplates = $this->whatsAppTemplateRepository->getApprovedTemplatesByNewest();
        // non template preset messages
        $nonTemplatePresetMessages = $this->botReplyRepository->fetchNonTemplateCampaignMessagePresets();
        // contact groups
        $vendorContactGroups = $this->contactGroupRepository->getActiveGroups($vendorId);
        // get all the labels
        $allLabels = $this->labelRepository->fetchItAll([
            'vendors__id' => $vendorId
        ]);
        return $this->engineSuccessResponse([
            'contact' => null,
            'whatsAppTemplates' => $whatsAppApprovedTemplates,
            'nonTemplatePresetMessages' => $nonTemplatePresetMessages,
            'vendorContactGroups' => $vendorContactGroups,
            'template' => '',
            'templatePreview' => '',
            'allLabels' => $allLabels,
        ]);
    }

    /**
     * Process the template change
     *
     * @param  string|int  $whatsAppTemplateId
     * @return EngineResponse
     */
    public function processTemplateChange($whatsAppTemplateId, $pageType)
    {
        $preparedTemplateData = $this->prepareTemplate($whatsAppTemplateId, ['pageType' => $pageType]);

        return $this->engineSuccessResponse([
            'template' => $preparedTemplateData['template'],
            'templateData' => $preparedTemplateData['templateData'],
        ]);
    }

    /**
     * Prepare Template with required parameters
     *
     * @param  string|int  $whatsAppTemplateId
     * @param  array  $options
     * @return array
     */
    protected function prepareTemplate($whatsAppTemplateId, array $options = [])
    {
        $options = array_merge([
            'templateComponents' => null,
            'pageType' => null
        ], $options);
        // useful for message
        if ($whatsAppTemplateId == 'for_message') {
            $whatsAppTemplate = null;
            $templateComponents = &$options['templateComponents'];
        } else {
            $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt($whatsAppTemplateId);
            abortIf(__isEmpty($whatsAppTemplate), null, __tr('Template not found'));
            $templateComponents = Arr::get($whatsAppTemplate->toArray(), '__data.template.components');
        }

        $templateType = data_get($templateComponents, '1.type');
        $bodyComponentText = '';
        $headerComponentText = '';
        $componentButtonText = '';
        $buttonItems = [];
        $headerParameters = [];
        $headerFormat = null;
        $btnIndex = 0;
        $buttonParameters = [];
        foreach ($templateComponents ?? [] as $templateComponent) {
            if ($templateComponent['type'] == 'HEADER') {
                $headerFormat = $templateComponent['format'];
                if ($templateComponent['format'] == 'TEXT') {
                    $headerComponentText = $templateComponent['text'];
                }
            } elseif ($templateComponent['type'] == 'BODY') {
                $bodyComponentText = $templateComponent['text'];
            } elseif ($templateComponent['type'] == 'BUTTONS') {
                foreach ($templateComponent['buttons'] as $templateComponentButton) {
                    if ($templateComponentButton['type'] == 'URL' and (Str::contains($templateComponentButton['url'], '{{1}}'))) {
                        $buttonItems[] = [
                            'type' => $templateComponentButton['type'],
                            'url' => $templateComponentButton['url'],
                            'text' => $templateComponentButton['text'],
                        ];
                        $buttonParameters[] = "button_$btnIndex";
                    } elseif ($templateComponentButton['type'] == 'COPY_CODE') {
                        $buttonItems['COPY_CODE'] = [
                            'type' => $templateComponentButton['type'],
                            'text' => $templateComponentButton['text'],
                        ];
                    }
                    $btnIndex++;
                }
            }
        }
        // Regular expression to match {{number}}
        $pattern = '/{{\d+}}/';
        // Find matches
        preg_match_all($pattern, $headerComponentText, $headerVariableMatches);
        // $templateParameters = $matches[0]; // will contain all matched patterns
        $headerParameters = array_map(function ($item) {
            return 'header_field_' . strtr($item, [
                '{{' => '',
                '}}' => '',
            ]);
        }, $headerVariableMatches[0]); // will contain all matched patterns
        // Find matches
        preg_match_all($pattern, $bodyComponentText, $matches);
        // $templateParameters = $matches[0]; // will contain all matched patterns
        $bodyParameters = array_map(function ($item) {
            return 'field_' . strtr($item, [
                '{{' => '',
                '}}' => '',
            ]);
        }, $matches[0]); // will contain all matched patterns

        $contactDataMaps = getContactDataMaps();
        $templateDataPrepared = [
            'buttonItems' => $buttonItems,
            'templateComponents' => $templateComponents,
            'headerParameters' => $headerParameters,
            'buttonParameters' => $buttonParameters,
            'bodyParameters' => $bodyParameters,
            // 'buttonParameters' => $buttonParameters,
            'template' => $whatsAppTemplate,
            'headerFormat' => $headerFormat,
            // for preview
            'bodyComponentText' => $bodyComponentText,
            'contactDataMaps' => $contactDataMaps,
            'templateType' => $templateType,
            'carouselTemplateData' => ($templateType == 'CAROUSEL') ? $templateComponents : [],
            'pageType' => $options['pageType']
        ];

        if ($options['templateComponents']) {
            return $templateDataPrepared;
        }

        return [
            'template' => view('whatsapp-service.message-preparation', $templateDataPrepared)->render(),
            'templateData' => $templateDataPrepared,
        ];
    }

    /**
     * Send message for selected contact
     *
     * @param  BaseRequestTwo  $request
     * @return EngineResponse
     */
    public function processSendMessageForContact($request)
    {
        $contact = $this->contactRepository->getVendorContact($request->get('contact_uid'));
        if (__isEmpty($contact)) {
            if (isExternalApiRequest()) {
                $contact = $this->createAContactForApiRequest($request);
            } else {
                return $this->engineFailedResponse([], __tr('Requested contact does not found'));
            }
        }

        // check if vendor has active plan
        $vendorPlanDetails = vendorPlanDetails(null, null, $contact->vendors__id);
        if (!$vendorPlanDetails->hasActivePlan()) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }

        return $this->sendTemplateMessageProcess($request, $contact);
    }

    /**
     * Create contact if does not exist
     *
     * @param BaseRequestTwo $request
     * @return void
     */
    protected function createAContactForApiRequest($request)
    {
        $vendorId = getVendorId();
        // check the feature limit
        $vendorPlanDetails = vendorPlanDetails('contacts', $this->contactRepository->countIt([
            'vendors__id' => $vendorId
        ]), $vendorId);

        abortIf(!$vendorPlanDetails['is_limit_available'], null, $vendorPlanDetails['message']);

        $request->validate([
            'contact.first_name' => [
                'nullable',
                'max:150',
            ],
            'contact.last_name' => [
                'nullable',
                'max:150',
            ],
            'contact.country' => 'nullable',
            'contact.language_code' => 'nullable|alpha_dash',
            "phone_number" => [
                'required',
                'numeric',
                'min_digits:9',
                'min:1',
            ],
            'contact.email' => 'nullable|email',
        ]);

        $dataForContact = Arr::only(($request->contact ?? []), [
            'first_name',
            'last_name',
            'language_code',
            'email',
            'country',
            'custom_fields',
        ]);
        // abortIf(str_starts_with($request->phone_number, '0') or str_starts_with($request->phone_number, '+'), null, 'phone number should be numeric value without prefixing 0 or +');

        // create contact
        if ($contactCreated = $this->contactRepository->storeContact([
            'first_name' => $dataForContact['first_name'] ?? '',
            'last_name' => $dataForContact['last_name'] ?? '',
            'email' => $dataForContact['email'] ?? '',
            'language_code' => $dataForContact['language_code'] ?? '',
            'phone_number' => cleanDisplayPhoneNumber($request->phone_number),
            'country' => getCountryIdByName($dataForContact['country'] ?? null),
        ], $vendorId)) {
            // prepare group ids needs to be assign to the contact
            $contactGroupsTitles = array_filter(array_unique(explode(',', $request->contact['groups'] ?? '') ?? []));
            if (!empty($contactGroupsTitles)) {
                // prepare group titles needs to be assign to the contact
                $groupsToBeAdded = $this->contactGroupRepository->fetchItAll($contactGroupsTitles, [], 'title', [
                    'where' => [
                        'vendors__id' => $vendorId
                    ]
                ]);
                $groupsToBeCreatedTitles = array_diff($contactGroupsTitles, $groupsToBeAdded->pluck('title')->toArray());
                $groupsToBeCreated = [];
                if (!empty($groupsToBeCreatedTitles)) {
                    foreach ($groupsToBeCreatedTitles as $groupsToBeCreatedTitle) {
                        if (strlen($groupsToBeCreatedTitle) > 255) {
                            abortIf(strlen($groupsToBeCreatedTitle) > 1, null, __tr('Group title should not be greater than 255 characters'));
                        }
                        $groupsToBeCreated[] = [
                            'title' => $groupsToBeCreatedTitle,
                            'vendors__id' => $vendorId,
                            'status' => 1,
                        ];
                    }
                    if (!empty($groupsToBeCreated)) {
                        $newlyCreatedGroupIds = $this->contactGroupRepository->storeItAll($groupsToBeCreated, true);
                        if (!empty($newlyCreatedGroupIds)) {
                            $newlyCreatedGroups = $this->contactGroupRepository->fetchItAll(array_values($newlyCreatedGroupIds));
                            if (!__isEmpty($groupsToBeAdded)) {
                                $groupsToBeAdded->merge($newlyCreatedGroups);
                            }
                        }
                    }
                }
                $assignGroups = [];
                // prepare to assign if needed
                if (! empty($groupsToBeAdded)) {
                    foreach ($groupsToBeAdded as $groupToBeAdded) {
                        if ($groupToBeAdded->vendors__id != $vendorId) {
                            continue;
                        }
                        $assignGroups[] = [
                            'contact_groups__id' => $groupToBeAdded->_id,
                            'contacts__id' => $contactCreated->_id,
                        ];
                    }
                    $this->groupContactRepository->storeItAll($assignGroups);
                }
            }

            // custom fields from External API
            $customInputFields = isset($dataForContact['custom_fields']) ? $dataForContact['custom_fields'] : [];
            // check if custom fields
            if (!empty($customInputFields)) {
                $customInputFieldsFromDb = $this->contactCustomFieldRepository->fetchItAll(array_keys(
                    $customInputFields
                ), [], 'input_name', [
                    'where' => [
                        'vendors__id' => $vendorId
                    ]
                ])->keyBy('input_name');
                // loop though items
                foreach ($customInputFields as $customInputFieldKey => $customInputFieldValue) {
                    $customInputFieldFromDb = null;
                    if (isset($customInputFieldsFromDb[$customInputFieldKey])) {
                        $customInputFieldFromDb = $customInputFieldsFromDb[$customInputFieldKey];
                    }
                    // if invalid
                    if (!$customInputFieldFromDb or ($customInputFieldFromDb->vendors__id != $vendorId)) {
                        continue;
                    }
                    // if data verified
                    $customInputFieldUidsAndValues[] = [
                        'contact_custom_fields__id' => $customInputFieldFromDb->_id,
                        'contacts__id' => $contactCreated->_id,
                        'field_value' => $customInputFieldValue,
                    ];
                }
            }
            if (!empty($customInputFieldUidsAndValues)) {
                $this->contactCustomFieldRepository->storeCustomValues($customInputFieldUidsAndValues);
            }
        }

        return $contactCreated;
    }

    /**
     * get Current Billing Cycle
     *
     * @param string $subscriptionStartDate
     * @return array
     */
    public function getCurrentBillingCycleDates($subscriptionStartDate)
    {
        $today = Carbon::now();
        $startOfMonth = new Carbon($subscriptionStartDate);
        // Adjust the start date to the current period
        $startOfMonth->year($today->year)->month($today->month);
        if ($today->day < $startOfMonth->day) {
            // If today is before the subscription day this month, start from last month
            $startOfMonth->subMonth();
        }
        $endOfMonth = (clone $startOfMonth)->addMonth()->subDay(); // End of this billing cycle
        return [
            'start' => $startOfMonth->startOfDay(), // Ensure time part is zeroed out
            'end' => $endOfMonth->endOfDay(), // Include the entire last day
        ];
    }

    /**
     * Prepare targeted contact count data
     *
     * @param  array  $inputData
     * @return EngineResponse
     */
    public function prepareTargetContactCountData($inputData)
    {
        $contactGroupIds = data_get($inputData, 'group_contact_ids', []);
        $labelIds = data_get($inputData, 'label_ids', []);
        $groupContactIds = [];
        $contactGroup = [];
        $vendorId = getVendorId();

        if (__isEmpty($inputData['template_id']) or __isEmpty($contactGroupIds)) {
            return $this->engineSuccessResponse([
                'totalContacts' => 0,
            ]);
        }

        $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt($inputData['template_id']);

        if (!__isEmpty($contactGroupIds) and !in_array('all_contacts', $contactGroupIds)) {
            $contactGroup = $this->contactGroupRepository->fetchItAll($contactGroupIds, [], '_id', [
                'where' => [
                    'vendors__id' => $vendorId
                ]
            ]);

            if (__isEmpty($contactGroup)) {
                return $this->engineFailedResponse([], __tr('Invalid Group'));
            }
            $groupContacts = $this->groupContactRepository->fetchItAll($contactGroupIds, [], 'contact_groups__id');

            if (__isEmpty($groupContacts)) {
                return $this->engineSuccessResponse([
                    'totalContacts' => 0,
                ]);
            }
            $groupContactIds = $groupContacts->pluck('contacts__id')->toArray();
        }

        $contactsWhereClause = [
            'contacts.vendors__id' => $vendorId
        ];

        if ($whatsAppTemplate->category == 'MARKETING') {
            $contactsWhereClause['contacts.whatsapp_opt_out'] = null;
        }

        if (data_get($inputData, 'restrict_by_language') == 'on') {
            $contactsWhereClause['contacts.language_code'] = $whatsAppTemplate->language;
        }

        $contactCount = $this->contactRepository->countContactsForCampaign($contactsWhereClause, $groupContactIds, $labelIds);

        return $this->engineSuccessResponse([
            'totalContacts' => $contactCount,
        ]);
    }

    /**
     * Process create campaign via API
     *
     * @param  Request  $request
     * @return EngineResponse
     */
    public function processAPICampaignCreate($request)
    {
        $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt([
            'template_name' => $request->template_name,
            'language' => $request->template_language
        ]);

        // check if template exists
        if (__isEmpty($whatsAppTemplate)) {
            return $this->engineFailedResponse([], __tr('Template does not exist.'));
        }
        // Assign template uid
        $request->merge([
            'template_uid' => $whatsAppTemplate->_uid
        ]);
        
        $contactGroup = array_map('trim', explode(',', $request->contact_group));
        
        // Check if campaign schedule for contact group
        if (!in_array('all_contacts', $contactGroup)) {
            $contactGroupData = $this->contactGroupRepository->fetchItAll($contactGroup, [], 'title', [
                'where' => [
                    'vendors__id' => getVendorId(),
                ]                
            ]);
            
            if (__isEmpty($contactGroupData)) {
                return $this->engineFailedResponse([], __tr('Invalid Group'));
            }
            $request->merge([
                'contact_group' => $contactGroupData->pluck('_id')->toArray()
            ]);
        } elseif (in_array('all_contacts', $contactGroup)) {
            $request->merge([
                'contact_group' => ['all_contacts']
            ]);
        }
        
        // Check if contact labels exists
        if (!__isEmpty($request->contact_labels)) {
            $contactLabelData = array_map('trim', explode(',', $request->contact_labels));
            $contactLabels = $this->labelRepository->fetchItAll($contactLabelData, [], 'title');
            if (!__isEmpty($contactLabels)) {
                $request->merge([
                    'contact_labels' => $contactLabels->pluck('_id')->all()
                ]);
            }
        }

        return $this->processCampaignCreate($request);
    }

    /**
     * Process the message for Campaign creation
     *
     * @param  Request  $request
     * @return EngineResponse
     */
    public function processCampaignCreate($request)
    {
        $vendorId = getVendorId();
        // check the feature limit
        $subscription = getVendorCurrentActiveSubscription($vendorId);
        $currentBillingCycle = $this->getCurrentBillingCycleDates($subscription->created_at ?? getUserAuthInfo('vendor_created_at'));
        $vendorPlanDetails = vendorPlanDetails('campaigns', $this->campaignRepository->countIt([
            'vendors__id' => $vendorId,
            [
                'created_at',
                '>=',
                $currentBillingCycle['start'],
            ],
            [
                'created_at',
                '<=',
                $currentBillingCycle['end'],
            ]
        ]), $vendorId);

        if (!$vendorPlanDetails['is_limit_available']) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }
        $scheduleAt = $request->get('schedule_at');
        $timezone = $request->get('timezone');
        $presetMessageUid = $request->get('selected_preset_message_uid');
        // if seconds missing, complete required date time format
        if (strlen($scheduleAt) == 16) {
            $scheduleAt = $scheduleAt . ':00';
        }
        if ($scheduleAt) {
            try {
                $rawTime = Carbon::createFromFormat('Y-m-d\TH:i:s', $scheduleAt, $timezone);
                $scheduleAt = $rawTime->setTimezone('UTC');
            } catch (\Throwable $th) {
                return $this->engineFailedResponse([], __tr('Failed to recognize the datetime, please reload and try again.'));
            }
        } else {
            $scheduleAt = now();
        }

        $expireAt = $request->get('expire_at');
        if (strlen($expireAt) == 16) {
            $expireAt = $expireAt . ':00';
        }
        if ($expireAt) {
            try {
                $expireAtRawTime = Carbon::createFromFormat('Y-m-d\TH:i:s', $expireAt, $timezone);
                $expireAt = $expireAtRawTime->setTimezone('UTC')->toDateTimeString();
            } catch (\Throwable $th) {
                return $this->engineFailedResponse([], __tr('Failed to recognize the datetime, please reload and try again.'));
            }
        } else {
            $expireAt = null;
        }
        $whatsAppTemplate = null;
        if (!$presetMessageUid) {
            $whatsAppTemplate = $this->whatsAppTemplateRepository->fetchIt($request->template_uid);
            abortIf(__isEmpty($whatsAppTemplate), null, __tr('Template not found in the system'));
        }
        $presetMessage = null;
        if ($presetMessageUid) {
            $presetMessage = $this->botReplyRepository->fetchIt($presetMessageUid);
            // abortIf(__isEmpty($presetMessage), null, __tr('Preset Message not found in the system'));
            if (__isEmpty($presetMessage)) {
                return $this->engineFailedResponse([], __tr('Preset Message not found in the system'));
            }
        }
        $contactGroupIds = $request->contact_group;
        $restrictByTemplateContactLanguage = $request->restrict_by_templated_contact_language == 'on';

        $labelIds = $request->contact_labels;
        $contactsWhereClause = [
            'contacts.vendors__id' => $vendorId,
        ];
        $isOnlyForOptedContacts = false;
        //if its Marketing campaign and user is opted out don't process it further
        if (!$presetMessageUid and ($whatsAppTemplate->category == 'MARKETING')) {
            $contactsWhereClause['whatsapp_opt_out'] = null;
            $isOnlyForOptedContacts = true;
        }

        if (!$presetMessageUid and $restrictByTemplateContactLanguage) {
            $contactsWhereClause['language_code'] = $whatsAppTemplate->language;
        }
        $groupContactIds = [];
        $groupWiseCount = [];
        $contactGroup = [];

        // if not all contacts
        if (!in_array('all_contacts', $contactGroupIds)) {
            $contactGroup = $this->contactGroupRepository->fetchItAll($contactGroupIds, [], '_id', [
                'where' => [
                    'vendors__id' => $vendorId
                ]
            ]);

            if (__isEmpty($contactGroup)) {
                return $this->engineFailedResponse([], __tr('Invalid Group'));
            }
            $groupContacts = $this->groupContactRepository->fetchItAll($contactGroupIds, [], 'contact_groups__id');

            if (__isEmpty($groupContacts)) {
                return $this->engineFailedResponse([], __tr('Group Contact does not found'));
            }
            $groupContactIds = $groupContacts->pluck('contacts__id')->toArray();

            $groupWiseCount = $groupContacts
                ->groupBy('contact_groups__id')
                ->map(function ($items) {
                    return $items->pluck('contacts__id')->unique()->count();
                })->all();
        }
        // get contacts count of selected
        $totalContacts = $this->contactRepository->countContactsForCampaign($contactsWhereClause, $groupContactIds, $labelIds);

        if (!$totalContacts) {
            return $this->engineFailedResponse([], __tr('Contacts does not found'));
        }
        // demo account restrictions
        if (isDemo() and ($totalContacts > 3)) {
            return $this->engineFailedResponse([], __tr('DEMO LIMIT: For the demo purposes you can not send campaign messages to more than 3 contacts.'));
        }
        $testContactUid = getVendorSettings('test_recipient_contact');
        if (!$testContactUid) {
            return $this->engineFailedResponse([], __tr('Test Contact missing, You need to set the Test Contact first, do it under the WhatsApp Settings'));
        }
        $contact = $this->contactRepository->getVendorContact($testContactUid);
        if (__isEmpty($contact)) {
            return $this->engineFailedResponse([], __tr('Test contact does not found'));
        }
        $isTestMessageProcessed = null;
        if (!$presetMessageUid) {
            // send test message
            $isTestMessageProcessed = $this->sendTemplateMessageProcess($request, $contact, false, null, $vendorId, $whatsAppTemplate);
            if ($isTestMessageProcessed->failed()) {
                return $this->engineFailedResponse([], __tr('Failed to send test message'));
            }
            // remove test message log entry
            $this->whatsAppMessageLogRepository->deleteIt([
                '_uid' => $isTestMessageProcessed->data('messageUid')
            ]);
        }

        $contactGroupData = [];
        if (!__isEmpty($contactGroup)) {
            foreach ($contactGroup as $contactGroupItem) {
                $contactGroupData[$contactGroupItem->_uid] = [
                    '_id' => $contactGroupItem->_id,
                    '_uid' => $contactGroupItem->_uid,
                    'title' => $contactGroupItem->title,
                    'description' => $contactGroupItem->description,
                    'total_group_contacts' => $groupWiseCount[$contactGroupItem->_id] ?? 0
                ];
            }
        }

        $campaign = $this->campaignRepository->storeIt([
            'status' => 1,
            'vendors__id' => $vendorId,
            'users__id' => getUserID(),
            'title' => $request->title,
            'template_name' => $whatsAppTemplate->template_name ?? null,
            'template_language' => $whatsAppTemplate->language ?? null,
            'whatsapp_templates__id' => $whatsAppTemplate->_id ?? null,
            'scheduled_at' => $scheduleAt,
            'timezone' => $timezone,
            '__data' => [
                'expiry_at' => $expireAt,
                'total_contacts' => $totalContacts,
                'is_for_template_language_only' => $restrictByTemplateContactLanguage,
                'is_for_opted_only_contacts' => $isOnlyForOptedContacts,
                'is_all_contacts' => in_array('all_contacts', $contactGroupIds) ? true : false,
                'preset_message_id' => $presetMessage->_id ?? null,
                'preset_message_uid' => $presetMessageUid,
                'preset_message_name' => $presetMessage->name ?? null,
                'selected_groups' => in_array('all_contacts', $contactGroupIds) ? [] : $contactGroupData,
                'send_message_via_marketing_message_api' => ($request->get('send_message_via_marketing_message_api') == 'on') ? true : false


            ],
        ]);
        if (__isEmpty($campaign)) {
            return $this->engineFailedResponse([], __tr('Failed to create campaign'));
        }

        if (!app('akasmatTapasani')()) {
            return $this->engineSuccessResponse([
                'campaignUid' => $campaign->_uid
            ], __tr('Test Message success and Campaign created'));
        }

        $isSucceed = false;
        $this->contactRepository->getContactsForCampaignInChunks($contactsWhereClause, $groupContactIds, $labelIds, function (Collection $contacts) use (&$request, &$isTestMessageProcessed, &$vendorId, &$whatsAppTemplate, &$scheduleAt, &$campaign, &$isSucceed, &$expireAt, &$presetMessageUid, &$presetMessage) {
            $queueData = [];
            foreach ($contacts as $contact) {
                // if number is missing don't process it further
                if (!$contact->wa_id) {
                    continue;
                }
                $replyText = $mediaMessageData = $interactionMessageData = null;
                if ($presetMessageUid) {
                    $replyText = $presetMessage->reply_text;
                    $interactionMessageData = $presetMessage->__data['interaction_message'] ?? null;
                    $mediaMessageData = $presetMessage->__data['media_message'] ?? null;
                    if ($replyText) {
                        $replyText = $this->dynamicValuesReplacement($replyText, $contact);
                    }
                    // if interaction message
                    if ($interactionMessageData) {
                        // body text
                        $interactionMessageData['body_text'] = $replyText;
                        // header text assignments
                        if ($interactionMessageData['header_text']) {
                            $interactionMessageData['header_text'] = $this->dynamicValuesReplacement($interactionMessageData['header_text'], $contact);
                        }
                        // footer text assignments
                        if ($interactionMessageData['footer_text']) {
                            $interactionMessageData['footer_text'] = $this->dynamicValuesReplacement($interactionMessageData['footer_text'], $contact);
                        }
                    } elseif ($mediaMessageData) {
                        // caption text
                        $mediaMessageData['caption'] = $this->dynamicValuesReplacement($mediaMessageData['caption'], $contact);
                    }
                } else {
                    $templateMessageSentProcess = $this->sendTemplateMessageProcess($request, $contact, true, $campaign->_id, $vendorId, $whatsAppTemplate, $isTestMessageProcessed->data('inputs'));
                }
                // large contacts simulation
                // for ($i=0; $i < 2000; $i++) {
                $queueData[] = [
                    'vendors__id' => $vendorId,
                    'status' => 1, // queue
                    'scheduled_at' => $scheduleAt,
                    'phone_with_country_code' => $contact->wa_id,
                    'campaigns__id' => $campaign->_id,
                    'contacts__id' => $contact->_id,
                    'full_name' => trim($contact->first_name . ' ' . $contact->last_name),
                    '__data' => [
                        'expiry_at' => $expireAt,
                        'contact_data' => [
                            '_id' => $contact->_id,
                            '_uid' => $contact->_uid,
                            'first_name' => $contact->first_name,
                            'last_name' => $contact->last_name,
                            'countries__id' => $contact->countries__id,
                            'business_scope_user_id' => $contact->bsuid,
                        ],
                        'campaign_data' => !$presetMessageUid ? $templateMessageSentProcess->data() : [
                            'presetMessageUid' => $presetMessage->_uid,
                            'presetMessageId' => $presetMessage->_id,
                            // simple text message without template
                            'message_body' => $replyText, // $this->dynamicValuesReplacement($replyText, $contact),
                            'interaction_message' => $interactionMessageData,
                            'media_message' => $mediaMessageData,
                            'fromPhoneNumberId' => fromPhoneNumberIdForRequest(),
                        ]
                    ]
                ];
                // }
            }
            if (!empty($queueData) and $this->whatsAppMessageQueueRepository->storeItAll($queueData, false, [
                'json_checks' => false
            ])) {
                $isSucceed = true;
            }
        });

        if ($isSucceed) {

            if (function_exists('gc_collect_cycles')) {
                // Collect garbage to free memory
                // This is useful when processing large data sets
                // to avoid memory leaks
                // and ensure that memory is released.
                $collected = gc_collect_cycles();
                // __logDebug($collected);
            }

            if (getAppSettings('enable_queue_jobs_for_campaigns')) {
                if ($totalContacts) {
                    $numberOfJobs = ceil($totalContacts / getAppSettings('cron_process_messages_per_lot'));
                    $numberOfJobs = $numberOfJobs + ceil($numberOfJobs * 0.1);
                    for ($i = 0; $i < $numberOfJobs; $i++) {
                        ProcessCampaignMessagesJob::dispatch()->delay($scheduleAt);
                    }
                }
            }
            return $this->engineSuccessResponse([
                'campaignUid' => $campaign->_uid
            ], $presetMessageUid ? __tr('Campaign created') : __tr('Test Message success and Campaign created'));
        }
        return $this->engineFailedResponse([
            'campaignUid' => $campaign->_uid
        ], __tr('Failed to queue messages for campaign'));
    }

    /**
     * Process the queued messages 
     *
     * @return EngineResponse
     */
    public function processCampaignSchedule()
    {
        $monitor = new ServerPerformanceMonitorService();
        if ($monitor->isCritical()) {
            // if server is not in normal state then return
            return $monitor->terminate();
        }
        // set that cron job is done
        if (
            !getAppSettings('cron_setup_using_artisan_at') and app()->runningInConsole() and
            (app()->runningConsoleCommand('schedule:run')
                or app()->runningConsoleCommand('schedule:work')
                or app()->runningConsoleCommand('whatsapp:campaign:process')
                or app()->runningConsoleCommand('whatsapp:webhooks:process'))
        ) {
            $this->configurationEngine->processConfigurationsStore('internals', [
                'cron_setup_using_artisan_at' => now()
            ]);
        }
        if (!getAppSettings('queue_setup_using_artisan_at') and app()->runningConsoleCommand('queue:work')) {
            $this->configurationEngine->processConfigurationsStore('internals', [
                'queue_setup_using_artisan_at' => now()
            ]);
        }
        // stuck in processing messages
        // $stuckInProcessingMessages = $this->whatsAppMessageQueueRepository->stuckInProcessing();
        $queuedMessages = $this->whatsAppMessageQueueRepository->getQueueItemsForProcess();
        if (__isEmpty($queuedMessages)) {
            return $this->engineSuccessResponse([], __tr('Nothing to process'));
        }
        $poolData = [];
        foreach ($queuedMessages as $queuedMessage) {
            // try {
            // fetch the latest record
            // $queuedMessage = $this->whatsAppMessageQueueRepository->fetchIt($queuedMessage->_id);
            // ✅ Conditional re-fetch (avoid N+1 unless needed)
            if ($queuedMessage->updated_at < now()->subSeconds(5)) {
                $queuedMessage = $this->whatsAppMessageQueueRepository->fetchIt($queuedMessage->_id);
            }
            // if record not found or if its already in process
            if (__isEmpty($queuedMessage) || ($queuedMessage->status == 3)) {
                continue;
            }
            $contactsData = $queuedMessage->__data['contact_data'];
            $campaignData = $queuedMessage->__data['campaign_data'];
            $presetMessageId = $queuedMessage->__data['preset_message_id'] ?? null;
            $currentPhoneNumberId = ($campaignData['fromPhoneNumberId'] ?? null) ?: getVendorSettings('current_phone_number_id', null, null, $queuedMessage->vendors__id);
            $poolData[$queuedMessage->_uid] = [
                'queueUid' => $queuedMessage->_uid,
                'retries' => $queuedMessage->retries ?: 1,
                'campaignId' => $queuedMessage->campaigns__id,
                'campaignData' => $campaignData,
                'contactsData' => $contactsData,
                'whatsAppTemplateName' => $campaignData['whatsAppTemplateName'] ?? null,
                'whatsAppTemplateLanguage' => $campaignData['whatsAppTemplateLanguage'] ?? null,
                'phoneNumber' => $queuedMessage->phone_with_country_code,
                'messageComponents' => $campaignData['messageComponents'] ?? null,
                'vendorId' => $queuedMessage->vendors__id,
                'currentPhoneNumberId' => $currentPhoneNumberId,
            ];
        }
        $counter = 0;
        $serviceName = getAppSettings('name');
        $isDemo = isDemo();
        $responses = Http::pool(function (Pool $pool) use ($poolData, &$counter, &$serviceName, &$isDemo) {
            // Map each request to a pool get request
            $index = 1;
            return array_map(function ($poolRequestItem) use (&$pool, &$index, &$counter, &$serviceName, &$isDemo) {
                if (!$poolRequestItem['queueUid']) {
                    return;
                }

                $counter++;
                // 🕒 Respect rate limits
                if ($counter % 50 === 0) {
                    usleep(200000); // smoother throttling
                }

                // Update message queue
                $this->whatsAppMessageQueueRepository->updateIt($poolRequestItem['queueUid'], [
                    'status' => 3, // processing
                ]);

                // set the from number
                fromPhoneNumberIdForRequest($poolRequestItem['currentPhoneNumberId']);
                // prepare the request for pool
                $replyText = $poolRequestItem['campaignData']['message_body'] ?? '';
                $interactionMessageData = $poolRequestItem['campaignData']['interaction_message'] ?? null;
                $mediaMessageData = $poolRequestItem['campaignData']['media_message'] ?? null;
                /*
                        '_id' => $contact->_id,
                        '_uid' => $contact->_uid,
                        'first_name' => $contact->first_name,
                        'last_name' => $contact->last_name,
                        'countries__id' => $contact->countries__id,
                    */
                $businessScopeUserId = data_get($poolRequestItem, 'contactsData.business_scope_user_id');

                if ($mediaMessageData) {
                    // process media
                    $mediaData = $this->processMediaData($mediaMessageData);
                    if(!isset($mediaData['link']) or !isset($mediaData['type'])) {
                        return $this->engineFailedResponse([], __tr('Failed to process media data, please try again.'));
                    }
                    return $this->whatsAppApiService->sendMediaMessage($poolRequestItem['phoneNumber'], $mediaData['type'], $mediaData['link'], ($isDemo ? "{$serviceName} DEMO - " . $mediaData['caption'] : '' . $mediaData['caption']), $mediaData['original_filename'], $poolRequestItem['vendorId'], [
                        'pool' => $pool,
                        'queueUid' => $poolRequestItem['queueUid'],
                        'business_scope_user_id' => $businessScopeUserId
                    ]);
                } else if ($interactionMessageData) {
                    $interactionMessageData['body_text'] = $isDemo ? "{$serviceName} DEMO - " . $interactionMessageData['body_text'] : $interactionMessageData['body_text'];
                    return $this->whatsAppApiService->sendInteractiveMessage($poolRequestItem['phoneNumber'], $interactionMessageData, $poolRequestItem['vendorId'], [
                        'pool' => $pool,
                        'queueUid' => $poolRequestItem['queueUid'],
                        'business_scope_user_id' => $businessScopeUserId
                    ]);
                } else if ($replyText) {
                    return $this->whatsAppApiService->sendMessage($poolRequestItem['phoneNumber'], ($isDemo ? "`{$serviceName} DEMO`\n\r\n\r " . $replyText : '' . $replyText), $poolRequestItem['vendorId'], [
                        'pool' => $pool,
                        'queueUid' => $poolRequestItem['queueUid'],
                        'business_scope_user_id' => $businessScopeUserId
                    ]);
                } else {
                    return $this->whatsAppApiService->sendTemplateMessageViaPool($pool, $poolRequestItem['queueUid'], $poolRequestItem['whatsAppTemplateName'], $poolRequestItem['whatsAppTemplateLanguage'], $poolRequestItem['phoneNumber'], $poolRequestItem['messageComponents'], $poolRequestItem['vendorId'], [
                        'send_message_via_marketing_message_api' => (data_get($poolRequestItem, 'campaignData.inputs.send_message_via_marketing_message_api') == 'on') ? true : false,
                        'business_scope_user_id' => $businessScopeUserId
                    ]);
                }
            }, $poolData);
        });
        $errorMessage = '';
        foreach ($responses as $responseKey => $response) {
            $errorMessage = '';
            $poolRequestItem = $poolData[$responseKey] ?? null;
            // skip it if empty
            if (!$poolRequestItem) {
                continue;
            }
            try {
                if (!method_exists($response, 'ok')) {
                    $responseMessage =  $response->getMessage();
                    if ($poolRequestItem['retries'] > 5) {
                        $this->whatsAppMessageQueueRepository->updateIt($responseKey, [
                            'status' => 2, // error - do not requeue
                            '__data' => [
                                'process_response' => [
                                    'error_message' => $response->getMessage() ?? 'unknown error occurred',
                                    'error_status' => 'error_occurred',
                                ]
                            ]
                        ]);
                    } elseif (($response instanceof ConnectException) or ($response instanceof ConnectionException)) { // if its connection issue we need to retry
                        // requeue the message if connection error
                        $this->whatsAppMessageQueueRepository->updateIt($poolRequestItem['queueUid'], [
                            'status' => 1, // re queue
                            'retries' => $poolRequestItem['retries'] + 1,
                            'scheduled_at' => now()->addMinute(), // try in next one minute
                            '__data' => [
                                'process_response' => [
                                    'error_status' => 'requeued_connection_error',
                                    'error_message' => $response->getMessage()
                                ]
                            ]
                        ]);
                    } else {
                        /**
                         * @link https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes/
                         */
                        // If Cloud API message throughput has been reached we will try to process it again in 1 minute
                        if (Str::contains($responseMessage, [
                            '130429', // https://developers.facebook.com/documentation/business-messaging/whatsapp/support/error-codes
                            '613' // https://developers.facebook.com/docs/graph-api/overview/rate-limiting/
                        ])) {
                            $this->whatsAppMessageQueueRepository->updateIt($poolRequestItem['queueUid'], [
                                'status' => 1, // re queue
                                'retries' => $poolRequestItem['retries'] + 1,
                                'scheduled_at' => now()->addMinutes($poolRequestItem['retries'] + 5), // try in next five minute
                                '__data' => [
                                    'process_response' => [
                                        'error_message' => $responseMessage,
                                        'error_status' => 'requeued_rate_limit_hit',
                                    ]
                                ]
                            ]);
                        } else {
                            $this->whatsAppMessageQueueRepository->updateIt($responseKey, [
                                'status' => 2, // error - don not requeue
                                '__data' => [
                                    'process_response' => [
                                        'error_message' => $responseMessage,
                                        'error_status' => 'error_occurred',
                                    ]
                                ]
                            ]);
                        }
                    }
                    // back to next item
                    continue;
                }

                if ($response and !$response->ok()) {
                    $response->throw(function (Response $response, $exception) use (&$poolRequestItem, &$errorMessage) {
                        $getContents = $response->getBody()->getContents();
                        $getContentsDecoded = json_decode($getContents, true);
                        $errorMessage = Arr::get($getContentsDecoded, 'error.message', '');
                        return $exception;
                    });
                }
            } catch (\Throwable $th) {
                $consolidatedErrorMessage =  $errorMessage ?: $th->getMessage();
                if ($poolRequestItem['retries'] > 5) {
                    $this->whatsAppMessageQueueRepository->updateIt($responseKey, [
                        'status' => 2, // error - don not requeue
                        '__data' => [
                            'process_response' => [
                                'error_message' => $consolidatedErrorMessage,
                                'error_status' => 'error_occurred',
                            ]
                        ]
                    ]);
                } elseif (($th instanceof ConnectionException) or ($th instanceof ConnectException)) { // if its connection issue we need to retry
                    // requeue the message if connection error
                    $this->whatsAppMessageQueueRepository->updateIt($poolRequestItem['queueUid'], [
                        'status' => 1, // re queue
                        'retries' => $poolRequestItem['retries'] + 1,
                        'scheduled_at' => now()->addMinutes($poolRequestItem['retries'] + 5), // try in next five minute
                        '__data' => [
                            'process_response' => [
                                'error_status' => 'requeued_connection_error',
                                'error_message' => $consolidatedErrorMessage
                            ]
                        ]
                    ]);
                } else {
                    /**
                     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes/
                     */
                    // If Cloud API message throughput has been reached we will try to process it again in 1 minute
                    if (Str::contains($consolidatedErrorMessage, [
                        '130429', // https://developers.facebook.com/documentation/business-messaging/whatsapp/support/error-codes
                        '613' // https://developers.facebook.com/docs/graph-api/overview/rate-limiting/
                    ])) {
                        $this->whatsAppMessageQueueRepository->updateIt($poolRequestItem['queueUid'], [
                            'status' => 1, // re queue
                            'retries' => $poolRequestItem['retries'] + 1,
                            'scheduled_at' => now()->addMinutes($poolRequestItem['retries'] + 5), // try in next five minute
                            '__data' => [
                                'process_response' => [
                                    'error_message' => $consolidatedErrorMessage,
                                    'error_status' => 'requeued_rate_limit_hit',
                                ]
                            ]
                        ]);
                    } else {
                        $this->whatsAppMessageQueueRepository->updateIt($responseKey, [
                            'status' => 2, // error - don not requeue
                            '__data' => [
                                'process_response' => [
                                    'error_message' => $consolidatedErrorMessage,
                                    'error_status' => 'error_occurred',
                                ]
                            ]
                        ]);
                    }
                }
                // as the error occurred no further process is required
                continue;
            }
            if (!$poolRequestItem['queueUid'] or __isEmpty($this->whatsAppMessageQueueRepository->fetchIt($poolRequestItem['queueUid']))) {
                continue;
            }
            $campaignData = $poolRequestItem['campaignData'];
            $contactsData = $poolRequestItem['contactsData'];

            // as the message already sent via pool we have sent result of it to following method
            // as the result of sending already give it won't try to send message again
            $processedResponse = $this->sendActualWhatsAppTemplateMessage(
                $poolRequestItem['vendorId'],
                $contactsData['_id'],
                $poolRequestItem['phoneNumber'],
                $contactsData['_uid'],
                $campaignData['whatsAppTemplateName'] ?? null,
                $campaignData['whatsAppTemplateLanguage'] ?? null,
                $campaignData['templateProforma'] ?? [],
                $campaignData['templateComponents'] ?? [],
                $campaignData['messageComponents'] ?? [],
                $poolRequestItem['campaignId'],
                $contactsData,
                ($campaignData['fromPhoneNumberId'] ?? null),
                $response->json(), // sent message result
                // options
                [
                    'message_body' => $campaignData['message_body'] ?? '',
                    'interaction_message' => $campaignData['interaction_message'] ?? '',
                    'media_message' => $campaignData['media_message'] ?? '',
                    'send_message_via_marketing_message_api' => (data_get($campaignData, 'inputs.send_message_via_marketing_message_api') == 'on') ? true : false
                ]
            );
            if ($processedResponse->success()) {
                // this entry will be deleted on webhook call if not requeued for any reason
                $this->whatsAppMessageQueueRepository->updateIt($poolRequestItem['queueUid'], [
                    'status' => 4, // processed & waiting for response
                ]);
                $campaignUid = viaFlashCache('campaign_details_' . $poolRequestItem['campaignId'], function () use (&$poolRequestItem) {
                    $campaign = $this->campaignRepository->fetchIt($poolRequestItem['campaignId']);
                    if (!__isEmpty($campaign)) {
                        return $campaign->_uid;
                    }
                    return null;
                });
                $vendorUid = getPublicVendorUid($poolRequestItem['vendorId']);
                // Dispatch event for message
                event(new VendorChannelBroadcast($vendorUid, [
                    'contactUid' => $contactsData['_uid'],
                    'isNewIncomingMessage' => null,
                    'campaignUid' => $campaignUid,
                    'lastMessageUid' => null,
                    'formatted_last_message_time' => null,
                ]));
            }
        }
        return $this->engineSuccessResponse([], __tr('Message processed'));
    }

    /**
     * Template Message Sending Process
     *
     * @param Request $request
     * @param object $contact
     * @param boolean $isForCampaign
     * @param int $campaignId
     * @param int $vendorId
     * @param object $whatsAppTemplate
     * @param array $inputs
     * @return EngineResponse
     */
    public function sendTemplateMessageProcess($request, $contact, $isForCampaign = false, $campaignId = null, $vendorId = null, $whatsAppTemplate = null, $inputs = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        if (!$isForCampaign) {
            // check if vendor has active plan
            $vendorPlanDetails = vendorPlanDetails(null, null, $vendorId);
            if (!$vendorPlanDetails->hasActivePlan()) {
                return $this->engineResponse(22, null, $vendorPlanDetails['message']);
            }
        }

        $inputs = $inputs ?: $request->all();
        if ($request->template_name and isExternalApiRequest()) {
            $whatsAppTemplate = $whatsAppTemplate ?: $this->whatsAppTemplateRepository->fetchIt([
                'template_name' => $request->template_name,
                'language' => $request->template_language
            ]);
        } else {
            $whatsAppTemplate = $whatsAppTemplate ?: $this->whatsAppTemplateRepository->fetchIt($inputs['template_uid']);
        }

        abortIf(__isEmpty($whatsAppTemplate), null, __tr('Template for the selected language not found in the system, if you have created template recently on Facebook please sync templates again.'));
        // if only id or uid sent
        if ($vendorId and (is_int($contact) or is_string($contact))) {
            $contact = $this->contactRepository->getVendorContact($contact, $vendorId);
        }
        // if the contact not found may test contact is missing etc
        if (__isEmpty($contact)) {
            return $this->engineFailedResponse([], __tr('Contact not found'));
        }

        $contactWhatsappNumber = $contact->whatsappNumber;
        $templateProforma = Arr::get($whatsAppTemplate->toArray(), '__data.template');
        $templateComponents = Arr::get($templateProforma, 'components');
        $componentValidations = [];
        $bodyComponentText = '';
        $headerComponentText = '';
        $componentButtonText = '';
        $pattern = '/{{\d+}}/';
        $tempBodyParameters = [];
        $messageComponents = [];

        if ($request->has('carousel_templates')) {
            // __dd($templateComponents);
            $contactId = $contact->_id;
            $contactUid = $contact->_uid;
            $componentValidations = [
                'carousel_templates' => 'required|array|min:2'
            ];

            // Check if request from external API
            if (isExternalApiRequest()) {
                $componentValidations['carousel_templates.*.media_type'] = 'required';
                $componentValidations['carousel_templates.*.media_url'] = 'required|url';
            } else {
                $componentValidations['carousel_templates.*.uploaded_media_file_type'] = 'required';
                $componentValidations['carousel_templates.*.uploaded_media_file_name'] = 'required';
            }

            $isBodyExists = false;
            if (isset($templateComponents[0]['example'])) {
                $isBodyExists = true;
            }

            $bodyParameters = [];
            foreach ($inputs as $inputItemKey => $inputItemValue) {
                if (Str::startsWith($inputItemKey, 'field_')) {
                    $valueKeyName = str_replace('field_', '', $inputItemKey);
                    $bodyParameters[] = [
                        "type" => "text",
                        "text" => $this->setParameterValue($contact, $inputs, $inputItemKey)
                    ];

                    if ($isBodyExists) {
                        $componentValidations[$inputItemKey] = 'required';
                    }
                }
            }

            $componentValidations['carousel_templates.*.body_example_fields.*'] = 'required';
            // If not for campaign then validate carousel request
            if (!$isForCampaign) {
                $request->validate($componentValidations, [], [
                    'carousel_templates.*.uploaded_media_file_name' => __tr('media file'),
                    'carousel_templates.*.media_url' => __tr('media file'),
                    'carousel_templates.*.body_example_fields.*' => __tr('body example field')
                ]);
            }

            $cardData = [
                "type" => "carousel"
            ];

            if (!__isEmpty($inputs['carousel_templates'])) {
                foreach ($inputs['carousel_templates'] as $cardIndex => $carouselTemplate) {
                    $mediaId = null;
                    $headerType = Str::lower(data_get($carouselTemplate, 'uploaded_media_file_type'));
                    $mediaUrl = '';
                    $mediaSource = $header = [];

                    // Check if request from external API
                    if (isExternalApiRequest()) {
                        $headerType = Str::lower($carouselTemplate['media_type']);
                        $downloadedFileInfo = $this->mediaEngine->downloadAndStoreMediaFile([
                            'media_url' => $carouselTemplate['media_url']
                        ], getVendorUid(), $headerType);

                        if (__isEmpty($downloadedFileInfo)) {
                            return $this->engineFailedResponse([], __tr('Invalid media.'));
                        }

                        $mediaUrl = $downloadedFileInfo['path'];
                        $fileMimeType = $this->mediaEngine->getMimeType($mediaUrl);
                        $mediaId = $this->whatsAppApiService->uploadMedia($mediaUrl, $fileMimeType);

                        $inputs['carousel_templates'][$cardIndex]['uploaded_media_id'] = $mediaId;
                        $inputs['carousel_templates'][$cardIndex]['uploaded_media_id_expiry_at'] = now()->addDays(29);
                    }

                    // Check if input data have media id and url
                    if (
                        isset($inputs['carousel_templates'][$cardIndex]['uploaded_media_id'])
                        and $inputs['carousel_templates'][$cardIndex]['uploaded_media_id']
                    ) {
                        $mediaId = $inputs['carousel_templates'][$cardIndex]['uploaded_media_id'];
                    } elseif (
                        !isset($inputs['carousel_templates'][$cardIndex]['uploaded_media_id'])
                        and !__isEmpty(data_get($carouselTemplate, 'uploaded_media_file_name'))
                    ) {

                        $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $carouselTemplate['uploaded_media_file_name']], 'whatsapp_' . $headerType);
                        if ($isProcessed->failed()) {
                            return $isProcessed;
                        }
                        $mediaId = $this->whatsAppApiService->uploadMedia($isProcessed->data('filePath'), $isProcessed->data('fileMimeType'));
                        $mediaUrl = $isProcessed->data('path');

                        $inputs['carousel_templates'][$cardIndex]['uploaded_media_id'] = $mediaId;
                        $inputs['carousel_templates'][$cardIndex]['uploaded_media_id_expiry_at'] = now()->addDays(29);
                    }

                    if (
                        isset($inputs['carousel_templates'][$cardIndex]['uploaded_media_id'])
                        and $inputs['carousel_templates'][$cardIndex]['uploaded_media_id']
                        and isset($inputs['carousel_templates'][$cardIndex]['uploaded_media_id_expiry_at'])
                        and Carbon::parse($inputs['carousel_templates'][$cardIndex]['uploaded_media_id_expiry_at'])->isFuture()
                    ) {
                        $mediaSource['id'] = $mediaId;
                        $mediaSource['link'] = $mediaUrl;
                    }

                    if ($mediaSource) {
                        $header = [
                            "type" => "header",
                            "parameters" => [
                                [
                                    "type" => $headerType,
                                    $headerType => $mediaSource
                                ]
                            ]
                        ];
                    }

                    $body = [
                        "type" => "body",
                    ];

                    if (!__isEmpty(data_get($carouselTemplate, 'body_example_fields'))) {
                        $bodyExampleFieldData = [];
                        foreach ($carouselTemplate['body_example_fields'] as $bodyExampleKey => $bodyExample) {
                            $bodyExampleFieldData[] = [
                                "type" => "text",
                                "text" => $this->setParameterValue($contact, $carouselTemplate['body_example_fields'], $bodyExampleKey)
                            ];
                        }
                        $body["parameters"] = $bodyExampleFieldData;
                    }

                    $buttons = [];

                    if (!__isEmpty($carouselTemplate['button_type'])) {
                        foreach ($carouselTemplate['button_type'] as $buttonIndex => $buttonType) {
                            $buttonData = [];
                            if ($buttonType == 'QUICK_REPLY') {
                                $parameters = [];
                                if (!__isEmpty(data_get($carouselTemplate, 'quick_reply_button_payload'))) {
                                    $parameters[] = [
                                        "type" => "payload",
                                        "payload" => $carouselTemplate['quick_reply_button_payload']
                                    ];
                                }
                                $buttonData = [
                                    "type" => "button",
                                    "sub_type" => "quick_reply",
                                    "index" => $buttonIndex,
                                ];

                                if (!__isEmpty(($parameters))) {
                                    $buttonData["parameters"] = $parameters;
                                }
                            }

                            if ($buttonType == 'URL') {
                                $parameters = [];
                                if (!__isEmpty(data_get($carouselTemplate, 'button_example_field'))) {
                                    $parameters[] = [
                                        "type" => "text",
                                        "text" => $carouselTemplate['button_example_field']
                                    ];
                                }
                                $buttonData = [
                                    "type" => "button",
                                    "sub_type" => "url",
                                    "index" => $buttonIndex,
                                ];

                                if (!__isEmpty(($parameters))) {
                                    $buttonData["parameters"] = $parameters;
                                }
                            }

                            if ($buttonType == 'PHONE_NUMBER') {
                                $buttonData = [
                                    "type" => "button",
                                    "sub_type" => "voice_call",
                                    "index" => $buttonIndex,
                                ];
                            }

                            $buttons[] = $buttonData;
                        }
                    }

                    $cardData['cards'][] = [
                        "card_index" => $cardIndex,
                        "components" => array_merge(
                            [$header, $body],
                            $buttons
                        )
                    ];
                }
            }

            if (!__isEmpty($bodyParameters)) {
                $messageComponents[] = [
                    "type" => "body",
                    "parameters" => $bodyParameters
                ];
            } else {
                $messageComponents[] = [
                    "type" => "body"
                ];
            }

            $messageComponents[] = $cardData;
        } else {
            foreach ($templateComponents as $templateComponent) {
                if ($templateComponent['type'] == 'HEADER') {
                    $headerFormat = $templateComponent['format'];
                    if ($headerFormat == 'TEXT') {
                        $headerComponentText = $templateComponent['text'];
                        // Find matches
                        preg_match_all($pattern, $headerComponentText, $headerMatches);
                        array_map(function ($item) use (&$componentValidations) {
                            $item = 'header_field_' . strtr($item, [
                                '{{' => '',
                                '}}' => '',
                            ]);
                            $componentValidations[$item] = [
                                'required',
                            ];

                            return $item;
                        }, $headerMatches[0]); // will contain all matched patterns
                    } elseif ($headerFormat == 'LOCATION') {
                        $componentValidations['location_latitude'] = [
                            'required',
                            'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/',
                        ];
                        $componentValidations['location_longitude'] = [
                            'required',
                            'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
                        ];
                        $componentValidations['location_name'] = [
                            'required',
                            'string',
                        ];
                        $componentValidations['location_address'] = [
                            'required',
                            'string',
                        ];
                    } elseif ($headerFormat == 'IMAGE') {
                        $componentValidations['header_image'] = [
                            'required',
                        ];
                    } elseif ($headerFormat == 'VIDEO') {
                        $componentValidations['header_video'] = [
                            'required',
                        ];
                    } elseif ($headerFormat == 'DOCUMENT') {
                        $componentValidations['header_document'] = [
                            'required',
                        ];
                        $componentValidations['header_document_name'] = [
                            'required',
                        ];
                    }
                } elseif ($templateComponent['type'] == 'BODY') {
                    $bodyComponentText = $templateComponent['text'];
                    // Find matches
                    preg_match_all($pattern, $bodyComponentText, $matches);
                    $tempBodyParameters = array_map(function ($item) use (&$componentValidations) {
                        $item = 'field_' . strtr($item, [
                            '{{' => '',
                            '}}' => '',
                        ]);
                        $componentValidations[$item] = [
                            'required',
                        ];

                        return $item;
                    }, $matches[0]); // will contain all matched patterns
                } elseif ($templateComponent['type'] == 'BUTTONS') {
                    $btnIndex = 0;
                    foreach ($templateComponent['buttons'] as $templateComponentButton) {
                        if ($templateComponentButton['type'] == 'URL' and (Str::contains($templateComponentButton['url'], '{{1}}'))) {
                            $componentValidations["button_$btnIndex"] = [
                                'required',
                            ];
                        } elseif ($templateComponentButton['type'] == 'COPY_CODE') {
                            $componentValidations['copy_code'] = [
                                'required',
                                'alpha_dash',
                            ];
                        }
                        $btnIndex++;
                    }
                }
            }
            if (!$isForCampaign) {
                $request->validate($componentValidations);
            }
            unset($componentValidations);

            // process the data
            // Regular expression to match {{number}}
            $pattern = '/{{\d+}}/';
            // Find matches
            preg_match_all($pattern, $headerComponentText, $headerVariableMatches);
            // $templateParameters = $matches[0]; // will contain all matched patterns
            $headerParameters = array_map(function ($item) {
                return 'header_field_' . strtr($item, [
                    '{{' => '',
                    '}}' => '',
                ]);
            }, $headerVariableMatches[0]); // will contain all matched patterns

            preg_match_all($pattern, $componentButtonText, $buttonWordsMatches);
            $buttonParameters = array_map(function ($item) {
                return 'button_' . strtr($item, [
                    '{{' => '',
                    '}}' => '',
                ]);
            }, $buttonWordsMatches[0]); // will contain all matched patterns

            $componentBodyIndex = 0;
            $mainIndex = 0;
            $componentBody = [];
            $componentBody[$mainIndex] = [
                'type' => 'body',
                'parameters' => [],
            ];
            foreach ($inputs as $inputItemKey => $inputItemValue) {
                if (Str::startsWith($inputItemKey, 'field_')) {
                    if (!in_array($inputItemKey, $tempBodyParameters)) {
                        continue;
                    }
                    $valueKeyName = str_replace('field_', '', $inputItemKey);
                    $componentBody[$mainIndex]['parameters']["{{{$valueKeyName}}}"] = [
                        'type' => 'text',
                        'text' => $this->setParameterValue($contact, $inputs, $inputItemKey),
                    ];
                }
                $componentBodyIndex++;
            }
            $componentButtons = [];
            $parametersComponentsCreations = [
                'COPY_CODE',
            ];
            foreach ($templateComponents as $templateComponent) {
                // @link https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages/#media-messages
                // @link https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#supported-media-types
                if ($templateComponent['type'] == 'HEADER') {
                    if ($templateComponent['format'] == 'VIDEO') {
                        $mainIndex++;
                        if (isset($inputs['header_video']) and isValidUrl($inputs['header_video'])) {
                            $inputs['whatsapp_video'] = $inputs['header_video'];
                        } elseif (!isset($inputs['whatsapp_video'])) {
                            $inputHeaderVideo = $inputs['header_video'];
                            $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputHeaderVideo], 'whatsapp_video');
                            if ($isProcessed->failed()) {
                                return $isProcessed;
                            }
                            $inputs['whatsapp_video'] = $isProcessed->data('path');
                            $mediaUploadedId = $this->whatsAppApiService->uploadMedia($isProcessed->data('filePath'), $isProcessed->data('fileMimeType'));
                            $inputs['whatsapp_video_media_id'] = $mediaUploadedId;
                            $inputs['whatsapp_video_media_id_expiry_at'] = now()->addDays(29);
                        }
                        $videoSource = [];
                        if (
                            isset($inputs['whatsapp_video_media_id'])
                            and $inputs['whatsapp_video_media_id']
                            and isset($inputs['whatsapp_video_media_id_expiry_at'])
                            and Carbon::parse($inputs['whatsapp_video_media_id_expiry_at'])->isFuture()
                        ) {
                            $videoSource = [
                                'id' => $inputs['whatsapp_video_media_id'],
                            ];
                        } else {
                            $videoSource = [
                                'link' => $inputs['whatsapp_video'],
                            ];
                        }
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'video',
                                    'video' => $videoSource,
                                ],
                            ],
                        ];
                    } elseif ($templateComponent['format'] == 'IMAGE') {
                        $mainIndex++;
                        if (isset($inputs['header_image']) and isValidUrl($inputs['header_image'])) {
                            $inputs['whatsapp_image'] = $inputs['header_image'];
                        } elseif (!isset($inputs['whatsapp_image'])) {
                            $inputHeaderImage = $inputs['header_image'];
                            $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputHeaderImage], 'whatsapp_image');
                            if ($isProcessed->failed()) {
                                return $isProcessed;
                            }
                            $inputs['whatsapp_image'] = $isProcessed->data('path');
                            $mediaUploadedId = $this->whatsAppApiService->uploadMedia($isProcessed->data('filePath'), $isProcessed->data('fileMimeType'));
                            $inputs['whatsapp_image_media_id'] = $mediaUploadedId;
                            $inputs['whatsapp_image_media_id_expiry_at'] = now()->addDays(29);
                        }
                        $imageSource = [
                            'link' => $inputs['whatsapp_image'],
                        ];
                        if (
                            isset($inputs['whatsapp_image_media_id'])
                            and $inputs['whatsapp_image_media_id']
                            and isset($inputs['whatsapp_image_media_id_expiry_at'])
                            and Carbon::parse($inputs['whatsapp_image_media_id_expiry_at'])->isFuture()
                        ) {
                            $imageSource['id'] = $inputs['whatsapp_image_media_id'];
                        }
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'image',
                                    'image' => $imageSource,
                                ],
                            ],
                        ];
                    } elseif ($templateComponent['format'] == 'DOCUMENT') {
                        $mainIndex++;
                        $inputHeaderDocument = $inputs['header_document'];
                        if (isset($inputs['header_document']) and isValidUrl($inputs['header_document'])) {
                            $inputs['whatsapp_document'] = $inputs['header_document'];
                        } elseif (!isset($inputs['whatsapp_document'])) {
                            $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputHeaderDocument], 'whatsapp_document');
                            if ($isProcessed->failed()) {
                                return $isProcessed;
                            }
                            $inputs['whatsapp_document'] = $isProcessed->data('path');
                            $mediaUploadedId = $this->whatsAppApiService->uploadMedia($isProcessed->data('filePath'), $isProcessed->data('fileMimeType'));
                            $inputs['whatsapp_document_media_id'] = $mediaUploadedId;
                            $inputs['whatsapp_document_media_id_expiry_at'] = now()->addDays(29);
                        }
                        $documentSource = [
                            'filename' => $this->setParameterValue($contact, $inputs, 'header_document_name'),
                            'link' => $inputs['whatsapp_document'],
                        ];
                        if (
                            isset($inputs['whatsapp_document_media_id'])
                            and $inputs['whatsapp_document_media_id']
                            and isset($inputs['whatsapp_document_media_id_expiry_at'])
                            and Carbon::parse($inputs['whatsapp_document_media_id_expiry_at'])->isFuture()
                        ) {
                            $documentSource['id'] = $inputs['whatsapp_document_media_id'];
                        }
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'document',
                                    'document' => $documentSource,
                                ],
                            ],
                        ];
                    } elseif ($templateComponent['format'] == 'LOCATION') {
                        // @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates/#location
                        $mainIndex++;
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'location',
                                    'location' => [
                                        'latitude' => $this->setParameterValue($contact, $inputs, 'location_latitude'),
                                        'longitude' => $this->setParameterValue($contact, $inputs, 'location_longitude'),
                                        'name' => $this->setParameterValue($contact, $inputs, 'location_name'),
                                        'address' => $this->setParameterValue($contact, $inputs, 'location_address'),
                                    ],
                                ],
                            ]
                        ];
                    } elseif ($templateComponent['format'] == 'IMAGE') {
                        $mainIndex++;
                        if (isset($inputs['header_image']) and isValidUrl($inputs['header_image'])) {
                            $inputs['whatsapp_image'] = $inputs['header_image'];
                        } elseif (!isset($inputs['whatsapp_image'])) {
                            $inputHeaderImage = $inputs['header_image'];
                            $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputHeaderImage], 'whatsapp_image');
                            if ($isProcessed->failed()) {
                                return $isProcessed;
                            }
                            $inputs['whatsapp_image'] = $isProcessed->data('path');
                        }
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'image',
                                    'image' => [
                                        'link' => $inputs['whatsapp_image'],
                                    ],
                                ],
                            ],
                        ];
                    } elseif ($templateComponent['format'] == 'DOCUMENT') {
                        $mainIndex++;
                        $inputHeaderDocument = $inputs['header_document'];
                        if (isset($inputs['header_document']) and isValidUrl($inputs['header_document'])) {
                            $inputs['whatsapp_document'] = $inputs['header_document'];
                        } elseif (!isset($inputs['whatsapp_document'])) {
                            $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $inputHeaderDocument], 'whatsapp_document');
                            if ($isProcessed->failed()) {
                                return $isProcessed;
                            }
                            $inputs['whatsapp_document'] = $isProcessed->data('path');
                        }

                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'document',
                                    'document' => [
                                        'filename' => $this->setParameterValue($contact, $inputs, 'header_document_name'),
                                        'link' => $inputs['whatsapp_document'],
                                    ],
                                ],
                            ],
                        ];
                    } elseif ($templateComponent['format'] == 'LOCATION') {
                        // @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates/#location
                        $mainIndex++;
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'location',
                                    'location' => [
                                        'latitude' => $this->setParameterValue($contact, $inputs, 'location_latitude'),
                                        'longitude' => $this->setParameterValue($contact, $inputs, 'location_longitude'),
                                        'name' => $this->setParameterValue($contact, $inputs, 'location_name'),
                                        'address' => $this->setParameterValue($contact, $inputs, 'location_address'),
                                    ],
                                ],
                            ],
                        ];
                    } elseif (($templateComponent['format'] == 'TEXT') and Str::contains($templateComponent['text'], '{{1}}')) {
                        // @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
                        $mainIndex++;
                        $componentBody[$mainIndex] = [
                            'type' => 'header',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $this->setParameterValue($contact, $inputs, 'header_field_1'),
                                ],
                            ],
                        ];
                    }
                } elseif ($templateComponent['type'] == 'BUTTONS') {
                    $componentButtonIndex = 0;
                    $skipComponentsCreations = [
                        // 'URL',
                        'PHONE_NUMBER',
                    ];
                    foreach ($templateComponent['buttons'] as $templateComponentButton) {
                        // or check if this type is skipped from components creations
                        if (! in_array($templateComponentButton['type'], $skipComponentsCreations)) {
                            // create component block
                            $componentButtons[$mainIndex] = [
                                'type' => 'button',
                                'sub_type' => $templateComponentButton['type'],
                                'index' => $componentButtonIndex,
                                'parameters' => [],
                            ];
                            // create coupon code parameters
                            if (in_array($templateComponentButton['type'], $parametersComponentsCreations)) {
                                $componentButtons[$mainIndex]['parameters'][] = [
                                    'type' => 'COUPON_CODE',
                                    'coupon_code' => $this->setParameterValue($contact, $inputs, 'copy_code'),
                                ];
                            } elseif // create url parameters
                            (in_array($templateComponentButton['type'], ['URL']) and Str::contains($templateComponentButton['url'], '{{1}}')) {
                                $componentButtons[$mainIndex]['parameters'][] = [
                                    'type' => 'text',
                                    'text' => $this->setParameterValue($contact, $inputs, "button_$componentButtonIndex"),
                                ];
                            } elseif // flow button parameters
                            (in_array($templateComponentButton['type'], ['FLOW'])) {
                                $componentButtons[$mainIndex]['parameters'][] = [
                                    "type" => "action",
                                    'action' => []
                                ];
                            }
                        }
                        $componentButtonIndex++;
                        $mainIndex++;
                    }
                }
            }
            // remove static links buttons
            foreach ($componentButtons as $componentButtonKey => $componentButton) {
                if (empty($componentButton['parameters'])) {
                    unset($componentButtons[$componentButtonKey]);
                }
            }
            $messageComponents = array_merge($componentBody, $componentButtons);
            $contactId = $contact->_id;
            $contactUid = $contact->_uid;
        }

        if ($isForCampaign) {
            return $this->engineSuccessResponse([
                'whatsAppTemplateName' => $whatsAppTemplate->template_name,
                'whatsAppTemplateLanguage' => $whatsAppTemplate->language,
                'templateProforma' => $templateProforma,
                'templateComponents' => $templateComponents,
                'messageComponents' => $messageComponents,
                'inputs' => $inputs,
                'fromPhoneNumberId' => $request->from_phone_number_id,
            ], __tr('Message prepared for WhatsApp campaign'));
        }

        $contactsData = [
            '_id' => $contact->_id,
            '_uid' => $contact->_uid,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'countries__id' => $contact->countries__id,
            'is_template_test_contact' => $request->is_template_test_contact
        ];

        $processedResponse = $this->sendActualWhatsAppTemplateMessage(
            $vendorId,
            $contactId,
            $contactWhatsappNumber,
            $contactUid,
            $whatsAppTemplate->template_name,
            $whatsAppTemplate->language,
            $templateProforma,
            $templateComponents,
            $messageComponents,
            $campaignId,
            $contactsData,
            $request->from_phone_number_id,
            null,
            ['send_message_via_marketing_message_api' => ($request->get('send_message_via_marketing_message_api') == 'on') ? true : false]
        );
        $processedResponse->updateData('inputs', $inputs);
        return $processedResponse;
    }

    /**
     * Send Interactive Message Process
     *
     * @param Request $request
     * @param boolean $isMediaMessage
     * @param integer $vendorId
     * @param array $options
     * @return EngineResponse
     */
    public function sendInteractiveMessageProcess($request, bool $isMediaMessage = false, $vendorId = null, $options = [])
    {
        $vendorId = $vendorId ?: getVendorId();
        // check if vendor has active plan
        $vendorPlanDetails = vendorPlanDetails(null, null, $vendorId);
        if (!$vendorPlanDetails->hasActivePlan()) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }

        if (is_array($request) === true) {
            $messageBody = $request['messageBody'];
            $contactUid = $request['contactUid'];
        } else {
            $messageBody = $request->message_body;
            $contactUid = $request->contact_uid;
        }
        $contact = $this->contactRepository->getVendorContact($contactUid, $vendorId);
        abortIf(__isEmpty($contact));
        // mark unread chats as read if any
        $this->markAsReadProcess($contact, $vendorId);
        $mediaData = [];
        $serviceName = getAppSettings('name');

        $sendMessageResult = $this->whatsAppApiService->sendInteractiveMessage($contact->wa_id, [
            'interactive_type' => '',
            'media_link' => '', //'https://camo.envatousercontent.com/a58d29650808a6231c7b785929abc438ac9910a3/68747470733a2f2f692e696d6775722e636f6d2f5a36367a3834682e706e67',
            'header_type' => '', // "text", "image", or "video"
            'header_text' => '',
            'body_text' => '',
            'footer_text' => '',
            'buttons' => [],
            'cta_url' => null,
        ], $vendorId, [
            'business_scope_user_id' => $contact->bsuid
        ]);
        $messageWamid = Arr::get($sendMessageResult, 'messages.0.id');
        if (! $messageWamid) {
            return $this->engineFailedResponse([
                'contact' => $contact,
            ], __tr('Failed to send message'));
        }
        $this->whatsAppMessageLogRepository->updateOrCreateWhatsAppMessageFromWebhook(
            getVendorSettings('current_phone_number_id', null, null, $vendorId),
            $contact->_id,
            $vendorId,
            $contact->wa_id,
            $messageWamid,
            'accepted',
            $sendMessageResult,
            $messageBody,
            null,
            $mediaData,
            false,
            $options
        );
        // update the client models by existing it
        updateClientModels([
            'whatsappMessageLogs' => $this->contactChatData($contact->_id)->data('whatsappMessageLogs'),
        ], 'prepend');

        return $this->engineSuccessResponse([
            'contact' => $contact,
        ], __tr('Message processed'));
    }

    /**
     * Actual Template Message Process
     *
     * @param integer $vendorId
     * @param integer $contactId
     * @param integer|string $contactWhatsappNumber
     * @param string $contactUid
     * @param string $whatsAppTemplateName
     * @param string $whatsAppTemplateLanguage
     * @param array $templateProforma
     * @param array $templateComponents
     * @param array $messageComponents
     * @param integer|null $campaignId
     * @param array|null $contactsData
     * @return EngineResponse
     */
    protected function sendActualWhatsAppTemplateMessage(
        int $vendorId,
        int $contactId,
        int|string $contactWhatsappNumber,
        string $contactUid,
        string|null $whatsAppTemplateName = null,
        string|null $whatsAppTemplateLanguage = null,
        array|null $templateProforma = null,
        array|null $templateComponents = null,
        array|null $messageComponents = null,
        ?int $campaignId = null,
        ?array $contactsData = null,
        $fromPhoneNumberId = null,
        $sendMessageResult = null,
        $options = [],
    ) {
        // options extend
        $options = array_merge([
            'message_body' => '',
            'interaction_message' => '',
            'media_message' => '',
            'send_message_via_marketing_message_api' => false
        ], $options);
        // sleep(2); // sleep for 2 seconds to avoid CPU overload
        $currentPhoneNumberId = $fromPhoneNumberId ?: getVendorSettings('current_phone_number_id', null, null, $vendorId);
        fromPhoneNumberIdForRequest($currentPhoneNumberId);
        // from number checks
        if ($fromPhoneNumberId) {
            $findPhoneNumber = Arr::first((getVendorSettings('whatsapp_phone_numbers', null, null, $vendorId) ?: []), function ($value, $key) use (&$fromPhoneNumberId) {
                return $value['id'] == $fromPhoneNumberId;
            });
            if (empty($findPhoneNumber)) {
                return $this->engineFailedResponse([], __tr('from phone number not available.'));
            }
            $currentDisplayPhoneNumber = $findPhoneNumber['display_phone_number'];
        } else {
            $currentDisplayPhoneNumber = getVendorSettings('current_phone_number_number', null, null, $vendorId);
        }
        if ($currentDisplayPhoneNumber == $contactWhatsappNumber) {
            return $this->engineFailedResponse([], __tr('You can not send message to your WhatsApp API number.'));
        }

        if (!app()->runningInConsole()) {
            if (!app('chukichTapasit')()) {
                return $this->engineFailedResponse([], __tr('Failed to send message'));
            }
        }

        $sendMessageViaMMApi = $options['send_message_via_marketing_message_api'];

        $contactDetails = $this->contactRepository->fetchIt($vendorId);

        if (!$sendMessageResult) {
            $sendMessageResult = $this->whatsAppApiService->sendTemplateMessage($whatsAppTemplateName, $whatsAppTemplateLanguage, $contactWhatsappNumber, $messageComponents, $vendorId, [
                'send_message_via_marketing_message_api' => $sendMessageViaMMApi,
                'business_scope_user_id' => ($contactDetails->bsuid) ?? ''
            ]);
        }
        $contactWaId = Arr::get($sendMessageResult, 'contacts.0.wa_id');
        // update contact number if not matched with wa id
        if ($contactWaId and (Arr::get($sendMessageResult, 'contacts.0.input') != $contactWaId)) {
            $this->contactRepository->updateIt([
                '_id' => $contactId,
                'vendors__id' => $vendorId,
            ], [
                'wa_id' => $contactWaId
            ]);
        }
        $messageResponseStatus = Arr::get($sendMessageResult, 'messages.0.message_status');
        // Check if status message is empty
        if (__isEmpty($messageResponseStatus) and !__isEmpty(Arr::get($sendMessageResult, 'messages.0.id'))) {
            $messageResponseStatus = 'accepted';
        }
        // $messageResponseStatus = Arr::get($sendMessageResult, 'messages.0.id') ? 'accepted' : null;
        // store it into db
        $recordCreated = $this->whatsAppMessageLogRepository->storeIt([
            'vendors__id' => $vendorId,
            'status' => $messageResponseStatus,
            'contacts__id' => $contactId,
            'campaigns__id' => $campaignId,
            'wab_phone_number_id' => $currentPhoneNumberId,
            'is_incoming_message' => 0,
            'contact_wa_id' => Arr::get($sendMessageResult, 'contacts.0.wa_id'),
            'wamid' => Arr::get($sendMessageResult, 'messages.0.id'),
            'message' => $options['message_body'],
            '__data' => [
                'contact_data' => $contactsData,
                'initial_response' => $sendMessageResult,
                'template_proforma' => $templateProforma,
                'template_components' => $templateComponents,
                'template_component_values' => $messageComponents,
                'media_values' => $options['media_message'],
                'interaction_message_data' => $options['interaction_message'],
                'send_message_via_marketing_message_api' => $sendMessageViaMMApi
            ],
        ]);
        if (($messageResponseStatus == 'accepted') or ($messageResponseStatus == 'held_for_quality_assessment')) {
            return $this->engineSuccessResponse(
                [
                    'messageUid' => $recordCreated->_uid,
                    'contactUid' => $contactUid,
                    'log_message' => $recordCreated,
                    'contact' => $this->contactRepository->fetchIt($contactUid),
                ],
                // response message
                ($messageResponseStatus == 'held_for_quality_assessment')
                    ? __tr('Message processed for WhatsApp contact')
                    : __tr('Message held for quality assessment, it may sent or dropped at this point.')
            );
        }

        return $this->engineFailedResponse([], __tr('Failed to process Message for WhatsApp contact. Status: __messageStatus__', [
            '__messageStatus__' => $messageResponseStatus,
        ]));
    }


    /**
     * Mark unread messages as read
     *
     * @param  eloquent  $contact
     * @return void
     */
    public function markAsReadProcess($contact, $vendorId)
    {
        if (!__isEmpty($contact)) {
            if ($contact->lastUnreadMessage) {
                ignoreFacebookApiError(true);
                try {
                    $this->whatsAppApiService->markAsRead($contact->lastUnreadMessage->wa_id, $contact->lastUnreadMessage->wamid, $vendorId, [
                        'business_scope_user_id' => $contact->bsuid
                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                }
                ignoreFacebookApiError(false);
            }
            $this->whatsAppMessageLogRepository->markAsRead($contact, $vendorId);
            // update unread count
            $this->updateUnreadCount();
        }
    }

    /**
     * Prepare chat window data
     *
     * @return EngineResponse
     */
    public function chatData(string|null $contactUid, string|null $assigned)
    {

        $vendorId = getVendorId();
        if (isDemo() and isDemoVendorAccount() and !$contactUid) {
            $contactUid = getVendorSettings('test_recipient_contact');
        }
        $this->hasMoreMessages = 0;
        $currentPaginatedPage = request()->page;

        $contact = $this->contactRepository->getVendorContactWithUnreadDetails($contactUid, $vendorId, $assigned);
        // if(!__isEmpty($contact)) {
        // mark unread chats as read
        $this->markAsReadProcess($contact, $vendorId);
        // }
        $dataToSend = [
            // check if received incoming message from contact in last 24 hours
            // the direct message won't be delivered if not received any message by user in last 24 hours
            'isDirectMessageDeliveryWindowOpened' => null,
            'directMessageDeliveryWindowOpenedTillMessage' => '',
            'contact' => null,
            'contacts' => [],
            'whatsappMessageLogs' => [],
            'assigned' => $assigned,
            'currentlyAssignedUserUid' => '',
            'isAiChatBotEnabled' => null,
            'isReplyBotEnable' => null
            // 'assignedLabelIds' => [],
        ];
        if (!request()->ajax()) {
            // labels
            $allLabels = $this->labelRepository->fetchItAll([
                'vendors__id' => $vendorId
            ]);
            // contact custom fields
            $vendorContactCustomFields = $this->contactCustomFieldRepository->fetchItAll([
                'vendors__id' => $vendorId,
            ]);
            // contact groups
            $vendorContactGroups = $this->contactGroupRepository->fetchItAll([
                'vendors__id' => $vendorId,
            ]);
            $dataToSend = array_merge($dataToSend, [
                // get the vendor users having messaging permission
                'vendorMessagingUsers' => $this->userRepository->getVendorMessagingUsers($vendorId),
                'vendorContactGroups' => $vendorContactGroups,
                'vendorContactCustomFields' => $vendorContactCustomFields,
                'allLabels' => $allLabels,
                // 'assignedLabelIds' => $contact?->labels->pluck('_id')->toArray() ?? [],
            ]);
        }
        if (__isEmpty($contact)) {
            return $this->engineSuccessResponse($dataToSend);
        }
        // $contactsData = $this->contactsData($contact)->data();
        updateClientModels([
            'assignedLabelIds' => $contact->labels->pluck('_id')->toArray() ?? [],
            // 'contacts' =>  $this->singleAsContactsData($contact->_uid)->data('contacts'),
            '@contacts' => 'extend'
        ]);

        if (__isEmpty($contact->__data)) {
            $contact->__data = [
                'is_blocked' => false
            ];
        } elseif (!__isEmpty($contact->__data) && !Arr::has($contact->__data, 'is_blocked')) {
            $contact->__data = array_merge($contact->__data, [
                'is_blocked' => false
            ]);
        }

        $contact->wa_id = maskString($contact->wa_id, 'phone');
        $contact->email = maskString($contact->email, 'email');

        $contactData = $this->singleAsContactsData($contact->_uid)->data('contacts');

        // Check if contact data exists
        if (!__isEmpty($contactData)) {
            foreach ($contactData as $contactDetails) {
                $contactDetails->wa_id = maskString($contactDetails->wa_id, 'phone');
            }
        }

        return $this->engineSuccessResponse(array_merge($dataToSend, [
            // check if received incoming message from contact in last 24 hours
            // the direct message won't be delivered if not received any message by user in last 24 hours
            'isDirectMessageDeliveryWindowOpened' => (! __isEmpty($contact->lastIncomingMessage) and ($contact->lastIncomingMessage?->messaged_at?->diffInHours() < 24)),
            'directMessageDeliveryWindowOpenedTillMessage' => __tr('Reply window open for __availableTimeForReply__', [
                '__availableTimeForReply__' => $contact->lastIncomingMessage?->messaged_at?->addHours(24)->diffForHumans([
                    'parts' => 2,
                    'join' => true,
                ])
            ]),
            'contact' => $contact,
            // 'contacts' => $contactsData['contacts'],
            'contacts' =>  $contactData,
            // 'contactsPaginatePage' => $contactsData['contactsPaginatePage'],
            'whatsappMessageLogs' => $this->getContactMessagesForChatBox($contact->_id),
            // get the vendor users having messaging permission
            // 'vendorMessagingUsers' => $this->userRepository->getVendorMessagingUsers($vendorId),
            // 'assigned' => $assigned,
            'currentlyAssignedUserUid' => $contact->assignedUser->_uid ?? '',
            'isAiChatBotEnabled' => !$contact->disable_ai_bot,
            'isReplyBotEnable' => !$contact->disable_reply_bot,
            // 'vendorContactGroups' => $vendorContactGroups,
            // 'vendorContactCustomFields' => $vendorContactCustomFields,
            // 'allLabels' => $allLabels,
            // 'assignedLabelIds' => $contact->labels->pluck('_id')->toArray() ?? [],
            'messagePaginatePage' => ($this->hasMoreMessages ? ($currentPaginatedPage ? ($currentPaginatedPage + 1) : 2) : 0)
        ]));
    }

    /**
     * Prepare the contact messages for the chat box
     *
     * @param integer $contactId
     * @param boolean $onlyRecent
     * @return object
     */
    protected $hasMoreMessages = false;
    protected function getContactMessagesForChatBox(int $contactId, bool $onlyRecent = false)
    {
        if (! $onlyRecent) {
            $resultOfMessages = $this->whatsAppMessageLogRepository->allMessagesOfContact($contactId);
        } else {
            $resultOfMessages = $this->whatsAppMessageLogRepository->recentMessagesOfContact($contactId);
        }

        $this->hasMoreMessages = $resultOfMessages->hasMorePages();
        return $resultOfMessages->keyBy('_uid')->transform(function ($item, string $key) {
            if (__isEmpty($item->__data) and __isEmpty($item->message)) {
                $item->message = '<i class="fas fa-trash"></i> ' . __tr('This message was deleted by system for cleanup.');
            } elseif ($item->is_system_message == 1 and __isEmpty(($item->message))) {
                $item->message = $this->formatSystemMessage(data_get($item->__data, 'system_message_data'));
            } else {
                $item->message = $this->formatWhatsAppText($item->message);
            }
            $item->template_message = null;
            if (! $item->message || Arr::get($item->__data, 'interaction_message_data')) {
                $item->template_message = $this->compileMessageWithValues($item->__data);
            };
            return $item;
        });
    }

    /**
     * Prepare Single chat logs for current selected user
     *
     * @return EngineResponse
     */
    public function contactChatData(string|int $contactIdOrUid)
    {
        $contactId = is_string($contactIdOrUid) ? $this->contactRepository->getVendorContact($contactIdOrUid)->_id : $contactIdOrUid;

        return $this->engineSuccessResponse([
            'whatsappMessageLogs' => $this->getContactMessagesForChatBox($contactId, true),
        ]);
    }

    /**
     * Prepare Single chat logs for current selected user
     *
     * @param  string|int  $contactUid
     * @param  string|null  $assigned
     * @return EngineResponse
     */
    protected $hasMoreContacts = false;
    public function contactsData($contactUid, $assigned = null)
    {
        $vendorId = getVendorId();
        if (is_string($contactUid)) {
            $contact = $this->contactRepository->getVendorContactWithUnreadDetails($contactUid, $vendorId, $assigned);
        } else {
            $contact = $contactUid;
        }
        $this->hasMoreContacts = 0;
        $currentPaginatedPage = request()->page;

        $vendorContactsWithUnreadDetails = $this->contactRepository->getVendorContactsWithUnreadDetails($vendorId, $assigned);
        $this->hasMoreContacts = $vendorContactsWithUnreadDetails->hasMorePages();
        if (!__isEmpty($contact) and $contact->unread_messages_count != 0) {
            $isContactInTheList = $vendorContactsWithUnreadDetails->where('_id', $contact->_id)->count();
            if (!$isContactInTheList) {
                $vendorContactsWithUnreadDetails = $vendorContactsWithUnreadDetails->toBase()->merge([$contact]);
            }
        }

        if (!__isEmpty($vendorContactsWithUnreadDetails)) {
            foreach ($vendorContactsWithUnreadDetails as $vendorContact) {
                $vendorContact->wa_id = maskString($vendorContact->wa_id, 'phone');
                $vendorContact->email = maskString($vendorContact->email, 'email');
                if (!__isEmpty($vendorContact->lastMessage)) {
                    $vendorContact->lastMessage->contact_wa_id = maskString($vendorContact->lastMessage->contact_wa_id, 'phone');
                }
            }
        }

        $responseEngineData = [
            'contacts' => $vendorContactsWithUnreadDetails->keyBy('_uid'),
        ];
        if (!request()->request_contact) {
            $responseEngineData['contactsPaginatePage'] = ($this->hasMoreContacts ? ($currentPaginatedPage ? ($currentPaginatedPage + 1) : 2) : 0);
        }
        // update unread count
        $this->updateUnreadCount();
        return $this->engineSuccessResponse($responseEngineData);
    }
    /**
     * Prepare Single chat logs for current selected user
     *
     * @param  string|int  $contactUid
     * @param  string|null  $assigned
     * @return EngineResponse
     */
    protected function singleAsContactsData($contactUid, $assigned = null)
    {
        $vendorId = getVendorId();
        if (is_string($contactUid)) {
            $contact = $this->contactRepository->getVendorContactWithUnreadDetails($contactUid, $vendorId, $assigned);
        } else {
            $contact = $contactUid;
        }
        // dummy query
        $vendorContactsWithUnreadDetails = $this->contactRepository->getVendorContactsWithUnreadDetails('NOTHING', $assigned);
        $this->hasMoreContacts = $vendorContactsWithUnreadDetails->hasMorePages();
        if (!__isEmpty($contact)) {
            $isContactInTheList = $vendorContactsWithUnreadDetails->where('_id', $contact->_id)->count();
            if (!$isContactInTheList) {
                $vendorContactsWithUnreadDetails = $vendorContactsWithUnreadDetails->toBase()->merge([$contact]);
            }
        }
        return $this->engineSuccessResponse([
            'contacts' => $vendorContactsWithUnreadDetails->keyBy('_uid')
        ]);
    }

    /**
     * Compile Message with required values
     *
     * @param array $messageData
     * @return view|string
     */
    protected function compileMessageWithValues($messageData)
    {
        if (isset($messageData['interaction_message_data'])) {
            return view('whatsapp-service.interaction-message-partial', [
                'mediaValues' => array_merge([
                    'media_link' => '',
                    'header_type' => '', // "text", "image", or "video"
                    'header_text' => '',
                    'body_text' => '',
                    'footer_text' => '',
                    'buttons' => [],
                ], $messageData['interaction_message_data']),
            ])->render();
        } elseif (isset($messageData['media_values'])) {
            $messageData['media_values']['caption'] = $this->formatWhatsAppText($messageData['media_values']['caption'] ?? '');
            return view('whatsapp-service.media-message-partial', [
                'mediaValues' => array_merge([
                    'link' => null,
                    'type' => null,
                    'caption' => null,
                    'file_name' => null,
                    'original_filename' => null,
                ], $messageData['media_values']),
            ])->render();
        } elseif (isset($messageData['other_message_data'])) {
            return view('whatsapp-service.other-message-partial', [
                'messageDataValues' => $messageData['other_message_data'],
            ])->render();
        } elseif ((isset($messageData['template_components'][1]['type'])) and ($messageData['template_components'][1]['type'] == 'CAROUSEL')) {
            return view('whatsapp-service.carousel-template-message', [
                'templateComponents' => $messageData['template_components'],
                'templateComponentValues' => $messageData['template_component_values']
            ])->render();
        } elseif (
            isset($messageData['webhook_responses']['incoming'][0]['changes'][0]['field'])
            and $messageData['webhook_responses']['incoming'][0]['changes'][0]['field'] == 'calls'
        ) {
            return view('whatsapp-service.calling-message', [
                'whatsappCallingData' => data_get($messageData, 'webhook_responses')
            ])->render();
        } elseif (!__isEmpty(data_get($messageData, 'webhook_responses.incoming.0.changes.0.value.messages.0.interactive.type')) and data_get($messageData, 'webhook_responses.incoming.0.changes.0.value.messages.0.interactive.type') == 'call_permission_reply') {
            return configItem('call_request_actions', data_get($messageData, 'webhook_responses.incoming.0.changes.0.value.messages.0.interactive.call_permission_reply.response'));
        }


        if (! isset($messageData['template_components']) or ! isset($messageData['template_component_values'])) {
            return null;
        }
        $templateComponents = $messageData['template_components'];
        $templateComponentValues = $messageData['template_component_values'];
        $bodyItemValues = [];
        $headerItemValues = [
            'image' => null,
            'video' => null,
            'document' => null,
            'location' => null,
            'text' => [],
        ];
        $buttonIndex = 1;
        $buttonValues = [];
        foreach ($templateComponentValues as $templateComponentValue) {
            if ($templateComponentValue['type'] == 'body') {
                if (isset($templateComponentValue['parameters'])) {
                    foreach ($templateComponentValue['parameters'] as $templateComponentValueParameterKey => $templateComponentValueParameter) {
                        $bodyItemValues[$templateComponentValueParameterKey] = $templateComponentValueParameter['text'];
                    }
                }
            } elseif ($templateComponentValue['type'] == 'header') {
                foreach ($templateComponentValue['parameters'] as $templateComponentValueParameterKey => $templateComponentValueParameter) {
                    if ($templateComponentValueParameter['type'] == 'image') {
                        $headerItemValues['image'] = $templateComponentValueParameter['image']['link'] ?? '';
                    } elseif ($templateComponentValueParameter['type'] == 'video') {
                        $headerItemValues['video'] = $templateComponentValueParameter['video']['link'] ?? '';
                    } elseif ($templateComponentValueParameter['type'] == 'document') {
                        $headerItemValues['document'] = $templateComponentValueParameter['document']['link'] ?? '';
                    } elseif ($templateComponentValueParameter['type'] == 'location') {
                        $headerItemValues['location'] = $templateComponentValueParameter['location'] ?? [
                            'name' => '',
                            'address' => '',
                            'latitude' => null,
                            'longitude' => null,
                        ];
                    } elseif ($templateComponentValueParameter['type'] == 'text') {
                        $headerItemValues['text'][] = $templateComponentValueParameter['text'];
                    }
                }
            } elseif ($templateComponentValue['type'] == 'button') {
                if ($templateComponentValue['sub_type'] == 'URL') {
                    if (isset($templateComponentValue['parameters'][0]['text'])) {
                        $buttonValues["{{{$buttonIndex}}}"] = $templateComponentValue['parameters'][0]['text'];
                        $buttonIndex++;
                    }
                } elseif ($templateComponentValue['sub_type'] == 'COPY_CODE') {
                    $buttonValues['COPY_CODE'] = $templateComponentValue['parameters'][0]['coupon_code'] ?? null;
                }
            }
        }

        return view('whatsapp-service.message-template-partial', array_merge($this->prepareTemplate('for_message', [
            'templateComponents' => $templateComponents,
        ]), [
            'templateComponentValues' => $templateComponentValues,
            'headerItemValues' => $headerItemValues,
            'bodyItemValues' => $bodyItemValues,
            'buttonValues' => $buttonValues,
        ]))->render();
    }

    /**
     * Clear Chat history for the Contact
     *
     * @param string $contactUid
     * @return void
     */
    public function processClearChatHistory($contactUid)
    {
        $vendorId = getVendorId();
        $contact = $this->contactRepository->getVendorContact($contactUid, $vendorId);
        abortIf(__isEmpty($contact));
        if ($this->whatsAppMessageLogRepository->clearChatHistory($contact->_id, $contact->vendors__id)) {
            updateClientModels([
                'whatsappMessageLogs' => $this->getContactMessagesForChatBox($contact->_id, true),
            ]);

            return $this->engineSuccessResponse([], __tr('Chat history has been cleared for __contactFullName__', [
                '__contactFullName__' => $contact->full_name,
            ]));
        }

        return $this->engineFailedResponse([], __tr('No chat history to clear for __contactFullName__', [
            '__contactFullName__' => $contact->full_name,
        ]));
    }

    /**
     * Process Media Data
     *
     * @param request $request
     * @param array $mediaMessageData
     * @return array
     */
    protected function processMediaData($mediaMessageData, $request = null)
    {
        $fileUrl = $fileName = $fileOriginalName = null;
        $rawUploadData = [];
        $caption = $request->caption ?? '';
        $mediaType = $request->media_type ?? '';
        $mediaUploadedId = null;
        $isNewMediaId = false;
        if ($mediaMessageData) {
            $fileName = $mediaMessageData['file_name'];
            $fileUrl = $mediaMessageData['media_link'];
            $fileOriginalName = $mediaMessageData['file_name'];
            $caption = $mediaMessageData['caption'];
            $mediaType = $mediaMessageData['header_type'];
            // check if media id is available and not expired
            if (
                isset($mediaMessageData['media_id'])
                and $mediaMessageData['media_id']
                and isset($mediaMessageData['media_id_expiry_at'])
                and Carbon::parse($mediaMessageData['media_id_expiry_at'])->isFuture()
            ) {
                $mediaUploadedId = $mediaMessageData['media_id'];
            }
        } elseif (!isValidUrl($request->media_url ?? null)) {
            $rawUploadData = json_decode($request->raw_upload_data, true);

            if ($request->is_recorded_audio ?? null) {
                $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $request->uploaded_media_file_name], 'whatsapp_' . $mediaType);
            } else {
                $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $request->uploaded_media_file_name], 'whatsapp_' . $mediaType);
            }

            if ($isProcessed->failed()) {
                return $isProcessed;
            }
            $fileUrl = $isProcessed->data('path');
            $fileName = $isProcessed->data('fileName');
            $fileOriginalName = Arr::get($rawUploadData, 'original_filename');
            $mediaUploadedId = $this->whatsAppApiService->uploadMedia($isProcessed->data('filePath'), $isProcessed->data('fileMimeType'));
            $isNewMediaId = true;
        } else {
            $fileName = $request->file_name ?? null;
            $fileUrl = $request->media_url ?? null;
            $fileOriginalName = $fileName;
        }
        return [
            'type' => $mediaType,
            'link' => $fileUrl,
            'media_id' => $mediaUploadedId ?? null,
            'media_id_expiry_at' => $isNewMediaId ? now()->addDays(29) : ($mediaMessageData['media_id_expiry_at'] ?? null), // media id expiry time
            'caption' => $caption,
            'mime_type' => Arr::get($rawUploadData, 'fileMimeType'),
            'file_name' => $fileName,
            'original_filename' => $fileOriginalName,
        ];
    }

    /**
     * Send Chat Message Process
     *
     * @param Request $request
     * @param boolean $isMediaMessage
     * @param integer $vendorId
     * @param array $options
     * @return EngineResponse
     */
    public function processSendChatMessage($request, bool $isMediaMessage = false, $vendorId = null, $options = [])
    {
        $vendorId = $vendorId ?: getVendorId();
        // check if vendor has active plan
        $vendorPlanDetails = vendorPlanDetails(null, null, $vendorId);
        if (!$vendorPlanDetails->hasActivePlan()) {
            return $this->engineResponse(22, null, $vendorPlanDetails['message']);
        }
        $interactionMessageData = null;
        $mediaMessageData = null;
        $fromPhoneNumberId = null;
        if (is_array($request) === true) {
            $messageBody = $request['messageBody'];
            $contactUid = $request['contactUid'];
            if (isset($options['interaction_message_data']) and !empty($options['interaction_message_data'])) {
                $interactionMessageData = $options['interaction_message_data'];
            }
            if (isset($options['media_message_data']) and !empty($options['media_message_data'])) {
                $mediaMessageData = $options['media_message_data'];
            }
        } else {
            $messageBody = $request->message_body;
            $contactUid = $request->contact_uid;
            $fromPhoneNumberId = $request->from_phone_number_id;
        }
        // options extend
        $options = array_merge([
            'from_phone_number_id' => $fromPhoneNumberId,
            'messageWamid' => null,
        ], $options);

        $contact = $this->contactRepository->getVendorContact($contactUid, $vendorId);
        if (__isEmpty($contact)) {
            if (isExternalApiRequest()) {
                $contact = $this->createAContactForApiRequest($request);
            } else {
                return $this->engineFailedResponse([], __tr('Requested contact does not found'));
            }
        }
        $currentBusinessPhoneNumber = $options['from_phone_number_id'] ?: ($contact->lastMessage->wab_phone_number_id ?? getVendorSettings('current_phone_number_id', null, null, $vendorId));
        fromPhoneNumberIdForRequest($currentBusinessPhoneNumber);
        if ($options['from_phone_number_id']) {
            $findPhoneNumber = Arr::first((getVendorSettings('whatsapp_phone_numbers', null, null, $vendorId) ?: []), function ($value, $key) use (&$options) {
                return $value['id'] == $options['from_phone_number_id'];
            });
            if (empty($findPhoneNumber)) {
                return $this->engineFailedResponse([], __tr('from phone number not available.'));
            }
            $currentDisplayPhoneNumber = $findPhoneNumber['display_phone_number'];
        } else {
            $currentDisplayPhoneNumber = getVendorSettings('current_phone_number_number', null, null, $vendorId);
        }
        if ($currentDisplayPhoneNumber == $contact->wa_id) {
            return $this->engineFailedResponse([], __tr('You can not send message to your WhatsApp API number.'));
        }


        if (!app()->runningInConsole()) {
            if (!app('anumanitNirikshan')()) {
                return $this->engineFailedResponse([], __tr('Failed to send message'));
            }
        }
        $initializeLogMessage = null;
        // to show message in chat instantly we have created first log entry
        // only for chat box
        if (!isExternalApiRequest() and $messageBody) {
            $initializeLogMessage = $this->whatsAppMessageLogRepository->storeIt([
                'is_incoming_message' => 0,
                'status' => 'initialize',
                'contact_wa_id' => $contact->wa_id,
                'contacts__id' => $contact->_id,
                'vendors__id' => $vendorId,
                'wab_phone_number_id' => $currentBusinessPhoneNumber,
                'message' => $messageBody,
                'messaged_at' => now(),
                '__data' => [
                    'options' => Arr::only($options, [
                        'bot_reply',
                        'ai_bot_reply',
                    ]),
                ],
            ]);
            usleep(200000);
            // update message list
            updateClientModelsViaEvent([
                'whatsappMessageLogs' => $this->contactChatData($contact->_id)->data('whatsappMessageLogs'),
            ], 'prepend');
            // useful for scrolling
            dispatchStreamEventData('onChatBoxMessageSubmit', []);
            $options['message_log_id'] = $initializeLogMessage->_id;
        }
        // do not mark messages as unread if bot replies sent or ai triggered
        if ((Arr::get($options, 'ai_error_triggered') != true) and (Arr::get($options, 'bot_reply') != true)) {
            // mark unread chats as read if any
            $this->markAsReadProcess($contact, $vendorId);
        }

        $businessScopeUserId = $contact->bsuid;

        $mediaData = [];
        $serviceName = getAppSettings('name');
        if ($interactionMessageData) {
            $interactionMessageData['body_text'] = isDemo() ? "{$serviceName} DEMO - " . $interactionMessageData['body_text'] : $interactionMessageData['body_text'];
            $sendMessageResult = $this->whatsAppApiService->sendInteractiveMessage($contact->wa_id, $interactionMessageData, $contact->vendors__id, [
                'business_scope_user_id' => $businessScopeUserId
            ]);
        } elseif ($isMediaMessage) {
            /* $fileUrl = $fileName = $fileOriginalName = null;
            $rawUploadData = [];
            $caption = $request->caption ?? '';
            $mediaType = $request->media_type ?? '';
            $mediaUploadedId = null;
            $isNewMediaId = false;
            if ($mediaMessageData) {
                $fileName = $mediaMessageData['file_name'];
                $fileUrl = $mediaMessageData['media_link'];
                $fileOriginalName = $mediaMessageData['file_name'];
                $caption = $mediaMessageData['caption'];
                $mediaType = $mediaMessageData['header_type'];
                // check if media id is available and not expired
                if (
                    isset($mediaMessageData['media_id'])
                    and $mediaMessageData['media_id']
                    and isset($mediaMessageData['media_id_expiry_at'])
                    and Carbon::parse($mediaMessageData['media_id_expiry_at'])->isFuture()
                ) {
                    $mediaUploadedId = $mediaMessageData['media_id'];
                }
            } elseif (!isValidUrl($request->media_url)) {
                $rawUploadData = json_decode($request->raw_upload_data, true);

                if ($request->is_recorded_audio) {
                    $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $request->uploaded_media_file_name], 'whatsapp_' . $mediaType);
                } else {
                    $isProcessed = $this->mediaEngine->whatsappMediaUploadProcess(['filepond' => $request->uploaded_media_file_name], 'whatsapp_' . $mediaType);
                }

                if ($isProcessed->failed()) {
                    return $isProcessed;
                }
                $fileUrl = $isProcessed->data('path');
                $fileName = $isProcessed->data('fileName');
                $fileOriginalName = Arr::get($rawUploadData, 'original_filename');
                $mediaUploadedId = $this->whatsAppApiService->uploadMedia($isProcessed->data('filePath'), $isProcessed->data('fileMimeType'));
                $isNewMediaId = true;
            } else {
                $fileName = $request->file_name;
                $fileUrl = $request->media_url;
                $fileOriginalName = $fileName;
            } */
            $mediaData = $this->processMediaData($mediaMessageData, $request);
            // if media data process failed then return with error message
            if(!isset($mediaData['link']) or !isset($mediaData['type'])) {
                return $this->engineFailedResponse([], __tr('Failed to process media data, please try again.'));
            }
            $sendMessageResult = $this->whatsAppApiService->sendMediaMessage($contact->wa_id, $mediaData['type'], $mediaData['link'], (isDemo() ? "{$serviceName} DEMO - " . $mediaData['caption'] : '' . $mediaData['caption']), $mediaData['original_filename'], $vendorId, [
                'business_scope_user_id' => $businessScopeUserId
            ]);
            /*  $mediaData = [
                'type' => $mediaType,
                'link' => $fileUrl,
                'media_id' => $mediaUploadedId ?? null,
                'media_id_expiry_at' => $isNewMediaId ? now()->addDays(29) : ($mediaMessageData['media_id_expiry_at'] ?? null), // media id expiry time
                'caption' => $caption,
                'mime_type' => Arr::get($rawUploadData, 'fileMimeType'),
                'file_name' => $fileName,
                'original_filename' => $fileOriginalName,
            ]; */
        } else {
            $sendMessageResult = $this->whatsAppApiService->sendMessage($contact->wa_id, (isDemo() ? "`{$serviceName} DEMO`\n\r\n\r " . $messageBody : '' . $messageBody), $vendorId, [
                'repliedToMessageWamid' => $options['messageWamid'],
                'business_scope_user_id' => $businessScopeUserId
            ]);
        }
        $messageWamid = Arr::get($sendMessageResult, 'messages.0.id');
        if (! $messageWamid) {
            if ($initializeLogMessage) {
                $initializeLogMessage->status =  'failed';
                $initializeLogMessage->save();
            }
            return $this->engineFailedResponse([
                'contact' => $contact,
            ], __tr('Failed to send message'));
        }
        if ($initializeLogMessage) {
            $initializeLogMessage->wamid =  $messageWamid;
            $initializeLogMessage->save();
        }
        $contactWaId = Arr::get($sendMessageResult, 'contacts.0.wa_id');
        // update contact number if not matched with wa id
        if ($contactWaId and (Arr::get($sendMessageResult, 'contacts.0.input') != $contactWaId)) {
            $this->contactRepository->updateIt([
                '_id' => $contact->_id,
                'vendors__id' => $vendorId,
            ], [
                'wa_id' => $contactWaId
            ]);
        }

        $logMessage = $this->whatsAppMessageLogRepository->updateOrCreateWhatsAppMessageFromWebhook(
            $currentBusinessPhoneNumber,
            $contact->_id,
            $vendorId,
            $contact->wa_id,
            $messageWamid,
            'accepted',
            $sendMessageResult,
            $messageBody,
            null,
            $mediaData,
            false,
            $options
        );
        // update the client models by existing it
        updateClientModels([
            'whatsappMessageLogs' => $this->contactChatData($contact->_id)->data('whatsappMessageLogs'),
        ], 'prepend');

        return $this->engineSuccessResponse([
            'contact' => $contact,
            'log_message' => $logMessage,
        ], isExternalApiRequest() ? __tr('Message processed') : '');
    }

    /**
     * Set the Parameters to to concerned template dynamic values
     *
     * @param object $contact
     * @param array $inputs
     * @param mixed $item
     * @return mixed
     */
    protected function setParameterValue(&$contact, &$inputs, $item)
    {
        $inputValue = $inputs[$item];

        if (isExternalApiRequest()) {
            return $this->dynamicValuesReplacement($inputValue, $contact);
        }
        // for any internal requests
        if (Str::startsWith($inputValue, 'dynamic_contact_')) {
            // assign phone value
            if ($inputValue == 'dynamic_contact_phone_number') {
                $inputValue = 'dynamic_contact_wa_id';
            }
            // check if value permitted
            if (!array_key_exists($inputValue, configItem('contact_data_mapping'))) {
                return null;
            }
            // correct the name
            $fieldName = str_replace('dynamic_contact_', '', $inputValue);
            // country value
            switch ($fieldName) {
                case 'country':
                    return $contact->country?->name ?: '-';
                    break;
            }
            return $contact->{$fieldName} ?: '-';
            // custom field values
        } elseif (Str::startsWith($inputValue, 'contact_custom_field_')) {
            $fieldName = str_replace('contact_custom_field_', '', $inputValue);
            // for api external request find value by field name
            if (isExternalApiRequest()) {
                return $contact->valueWithField?->firstWhere('customField.input_name', $fieldName)?->field_value ?: '-';
            }
            return $contact->customFieldValues?->firstWhere('contact_custom_fields__id', $fieldName)?->field_value ?: '-';
        }
        return $inputs[$item] ?: '-';
    }

    /**
     * Prepare string with values replacements
     *
     * @param string $inputValue
     * @param Eloquent $contact
     * @return string
     */
    protected function dynamicValuesReplacement($inputValue, &$contact, $options = [])
    {
        $options = array_merge([
            'assign_tem_member_name' => '',
        ], $options);

        $dynamicFieldsToReplace = [
            '{first_name}' => $contact->first_name,
            '{last_name}' => $contact->last_name,
            '{full_name}' => $contact->first_name . ' ' . $contact->last_name,
            '{phone_number}' => $contact->wa_id,
            '{email}' => $contact->email,
            '{country}' => $contact->country?->name,
            '{language_code}' => $contact->language_code,
            '{assigned_team_member}' => $options['assign_tem_member_name']
        ];
        // Review this code and make the appropriate changes
        $valueWithFields = $contact->valueWithField;
        foreach ($valueWithFields as $valueWithField) {
            if ($valueWithField?->customField?->input_name) {
                $dynamicFieldsToReplace["{{$valueWithField->customField->input_name}}"] = $valueWithFields?->firstWhere('customField.input_name', $valueWithField->customField->input_name)?->field_value;
            }
        }
        // assign dynamic values
        return strtr($inputValue, $dynamicFieldsToReplace);
    }

    /**
     * Send message processed by bot reply
     *
     * @param string $contactUid
     * @param string $replyText
     * @param int $vendorId
     * @param array|null $interactionMessageData
     * @return void
     */
    protected function sendReplyBotMessage($contactUid, $replyText, $vendorId, $interactionMessageData = null, $options = [])
    {
        $options = array_merge([
            'from_phone_number_id' => null,
            'messageWamid' => null,
        ], $options);

        $mediaMessageData = $options['mediaMessageData'] ?? null;
        $isTriggerFromQuickReply = Arr::get($options, 'isTriggerFromQuickReply') ? false : true;
        return $this->processSendChatMessage(
            [
                'contactUid' => $contactUid,
                'messageBody' => $replyText,
            ],
            $mediaMessageData ? true : false,
            $vendorId,
            [
                'bot_reply' => $isTriggerFromQuickReply,
                'ai_bot_reply' => $options['ai_bot_reply'] ?? false,
                'ai_error_triggered' => $options['ai_error_triggered'] ?? false,
                'media_message_data' => $mediaMessageData,
                'interaction_message_data' => $interactionMessageData,
                'from_phone_number_id' => $options['from_phone_number_id'],
                'messageWamid' => $options['messageWamid'],
            ]
        );
    }

    /**
     * Validate Bot Reply
     *
     * @param integer $testBotId
     * @return EngineResponse
     */
    public function validateTestBotReply(int $testBotId)
    {
        $testContactUid = getVendorSettings('test_recipient_contact');
        if (!$testContactUid) {
            return $this->engineFailedResponse([], __tr('Test Contact missing, You need to set the Test Contact first, do it under the WhatsApp Settings'));
        }
        $contact = $this->contactRepository->getVendorContact($testContactUid);
        if (__isEmpty($contact)) {
            return $this->engineFailedResponse([], __tr('Test contact does not found'));
        }
        return $this->processReplyBot($contact, '', $testBotId, ['validateTestBotReply' => true]);
    }

    /**
     * Process the bot reply if required
     *
     * @param Eloquent Object $contact
     * @param string $messageBody
     * @return object
     */
    public function processReplyBot($contact, $messageBody, $testBotId = null, $options = [])
    {
        if (__isEmpty($contact)) {
            return false;
        }
        $options = array_merge([
            'fromPhoneNumberId' => null,
            'messageWamid' => null,
            'isTriggerFromQuickReply' => null,
            'validateTestBotReply' => null,
        ], $options);
        // check if vendor has active plan
        $vendorPlanDetails = vendorPlanDetails(null, null, $contact->vendors__id);
        if (!$vendorPlanDetails->hasActivePlan()) {
            return false;
        }
        $messageBody = strtolower(trim($messageBody));
        $dataFetchConditions = [
            'vendors__id' => $contact->vendors__id,
        ];
        if ($testBotId) {
            $dataFetchConditions['_id'] = $testBotId;
        } else {
            if (!$messageBody) {
                return $this->engineFailedResponse([], __tr('Message body is empty'));
            }
        }

        $isBotTimingsEnabled = getVendorSettings('enable_bot_timing_restrictions', null, null, $contact->vendors__id);
        $isAiBotTimingsEnabled = getVendorSettings('enable_ai_bot_timing_restrictions', null, null, $contact->vendors__id);
        $isBotTimingsInTime = $this->isInAllowedBotTiming($contact->vendors__id);
        $selectedOtherBotsForTimingRestrictions = getVendorSettings('enable_selected_other_bot_timing_restrictions', null, null, $contact->vendors__id) ?: [];
        // search for only likewise records or welcome messages
        $allBotReplies = $this->botReplyRepository->getRelatedOrWelcomeBots($dataFetchConditions)->sortBy('priority_index');
        $isBotMatched = false;

        if ((!$contact->disable_reply_bot or $options['isTriggerFromQuickReply'] or $options['validateTestBotReply']) and !__isEmpty($allBotReplies)) {
            // check if we already have incoming message 2 days
            $isIncomingMessageExists = $this->whatsAppMessageLogRepository->countIt([
                'vendors__id' => $contact->vendors__id,
                'contacts__id' => $contact->_id,
                'is_incoming_message' => 1,
                [
                    // send welcome message again after a day if there is no incoming response
                    'created_at',
                    '>',
                    now()->subHours(24)
                ],
            ]) > 1;
            foreach ($allBotReplies as $botReply) {
                // if time restrictions
                if ($isBotTimingsEnabled and !$isBotTimingsInTime and array_key_exists($botReply->trigger_type, $selectedOtherBotsForTimingRestrictions)) {
                    continue;
                }
                // get reply triggers
                $replyTriggers = strtolower($botReply->reply_trigger);
                if (!$testBotId and ($botReply->trigger_type != 'welcome') and $replyTriggers == '') {
                    continue;
                }
                // testing the bot reply
                if ($testBotId and ($testBotId != $botReply->_id)) {
                    continue;
                } elseif ($testBotId) {
                    $messageBody = $replyTriggers;
                }
                // create array of comma separated triggers
                $replyTriggers = array_filter(explode(',', $replyTriggers) ?? []);
                if ($testBotId or (empty($replyTriggers) and ($botReply->trigger_type == 'welcome'))) {
                    $replyTriggers = [
                        'dummy'
                    ];
                }
                foreach ($replyTriggers as $replyTrigger) {
                    $replyTrigger = trim($replyTrigger);
                    if (!$testBotId and ($botReply->trigger_type != 'welcome') and $replyTrigger == '') {
                        continue;
                    }
                    if ($testBotId) {
                        $messageBody = $replyTrigger;
                    }
                    // if not the test bot
                    if (!$testBotId) {
                        // if normal bot and it is inactive
                        if (!$botReply->botFlow and ($botReply->status == 2)) {
                            continue;
                        }
                        // if bot flow inactive
                        if ($botReply->botFlow?->status == 2) {
                            continue;
                        }
                    }

                    $replyText = null;
                    $isBotMatchedForThisReply = false;
                    $interactionMessageData = $botReply->__data['interaction_message'] ?? null;
                    $mediaMessageData = $botReply->__data['media_message'] ?? null;
                    $templateMessageData = $botReply->__data['template_message'] ?? null;
                    if ($botReply->trigger_type == 'welcome') {
                        if (!$isIncomingMessageExists or $testBotId) {
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    }
                    if ($botReply->trigger_type == 'start_promotional') {
                        if (Str::is($replyTrigger, $messageBody)) {
                            if ($contact->whatsapp_opt_out) {
                                $this->contactRepository->updateIt($contact, [
                                    'whatsapp_opt_out' => null
                                ]);
                            }
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'stop_promotional') {
                        if (Str::is($replyTrigger, $messageBody)) {
                            if (!$contact->whatsapp_opt_out) {
                                $this->contactRepository->updateIt($contact, [
                                    'whatsapp_opt_out' => 1
                                ]);
                            }
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'start_ai_bot') {
                        if (Str::is($replyTrigger, $messageBody)) {
                            if ($contact->disable_ai_bot) {
                                $this->contactRepository->updateIt($contact, [
                                    'disable_ai_bot' => 0
                                ]);
                            }
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'stop_ai_bot') {
                        if (Str::is($replyTrigger, $messageBody)) {
                            if (!$contact->disable_ai_bot) {
                                $this->contactRepository->updateIt($contact, [
                                    'disable_ai_bot' => 1
                                ]);
                            }
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'is') {
                        if (Str::is($replyTrigger, $messageBody)) {
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'starts_with') {
                        if (Str::startsWith($messageBody, $replyTrigger)) {
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'ends_with') {
                        if (Str::endsWith($messageBody, $replyTrigger)) {
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'contains_word') {
                        // Prepare the pattern to search for the whole word
                        // \b represents a word boundary in regex
                        $pattern = '/\b' . preg_quote($replyTrigger, '/') . '\b/u';
                        // Use preg_match to search the haystack for the whole word
                        if (preg_match($pattern, $messageBody) > 0) {
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    } elseif ($botReply->trigger_type == 'contains') {
                        if (Str::contains($messageBody, $replyTrigger)) {
                            $replyText = $botReply->reply_text;
                            $isBotMatchedForThisReply = true;
                        }
                    }
                    // if reply text is ready
                    if ($isBotMatchedForThisReply) {

                        // Get Action Bot data
                        $actionBot = data_get($botReply, '__data.bot_actions');
                        // Assign team member name for assigning to contact purpose
                        $assignTeamMemberName = '';

                        // Check if action bot exists
                        if (!__isEmpty($actionBot)) {
                            $assignUserId = data_get($actionBot, 'assigned_users_id');

                            if (!__isEmpty($assignUserId) && $assignUserId !== 'no_action') {

                                $finalAssignUserId = null;

                                if ($assignUserId === 'random') {

                                    $randomTeamMember = $this->userRepository->getRandomTemMember($contact->vendors__id);

                                    if (!__isEmpty($randomTeamMember) and __isEmpty($contact->assigned_users__id)) {
                                        $finalAssignUserId = $randomTeamMember->_id;
                                    } else {
                                        $finalAssignUserId = $contact->assigned_users__id;
                                    }
                                } elseif ($assignUserId !== 'no_one') {

                                    $finalAssignUserId = $assignUserId;
                                }

                                // Update contact (assign or unassign)
                                $isUserAssigned = $this->contactRepository->updateIt($contact, [
                                    'assigned_users__id' => $finalAssignUserId
                                ]);

                                $action = __isEmpty($finalAssignUserId)
                                    ? 'UNASSIGN_TEAM_MEMBER'
                                    : 'ASSIGN_TEAM_MEMBER';

                                if (!__isEmpty($finalAssignUserId)) {
                                    $assignTeamMemberData = $this->userRepository->fetchIt($finalAssignUserId);

                                    if (!__isEmpty($assignTeamMemberData)) {
                                        $assignTeamMemberName = trim($assignTeamMemberData->first_name . ' ' . $assignTeamMemberData->last_name);
                                    }
                                }

                                // Log only if update successful
                                if ($isUserAssigned) {
                                    logSystemVendorChatMessage($contact, $action, $assignTeamMemberName);
                                }
                            }

                            // Get contact labels
                            $contactLabels = data_get($actionBot, 'contact_labels');

                            // Check if contact labels exists
                            if (!__isEmpty($contactLabels)) {
                                // extract exiting label ids
                                $existingLabelIds = $contact->labels->pluck('_id')->toArray();
                                // prepare group ids needs to be assign to the contact
                                $labelsToBeAddedIds = array_diff($contactLabels ?? [], $existingLabelIds);

                                // Check if labels to be added not empty
                                if (!__isEmpty($labelsToBeAddedIds)) {
                                    $labelsToBeAdded = $this->labelRepository->fetchItAll($labelsToBeAddedIds, [], '_id');
                                    $assignLabels = [];
                                    foreach ($labelsToBeAdded as $labelsToBeAdded) {
                                        $assignLabels[] = [
                                            'labels__id' => $labelsToBeAdded->_id,
                                            'contacts__id' => $contact->_id,
                                        ];
                                        logSystemVendorChatMessage($contact, 'LABEL_ADDED', $labelsToBeAdded->title);
                                    }
                                    $this->contactLabelRepository->storeItAll($assignLabels);
                                }
                            }

                            // Get unassign contact labels
                            $unassignContactLabels = data_get($actionBot, 'unassign_contact_labels');

                            // Check if unassign contact labels exists
                            if (!__isEmpty($unassignContactLabels)) {
                                // extract exiting label ids
                                $existingLabelIds = $contact->labels->pluck('_id')->toArray();
                                // prepare group ids needs to be assign to the contact
                                $labelsToBeRemovedIds = array_values(array_intersect($existingLabelIds, $unassignContactLabels ?? []));

                                if (!__isEmpty($labelsToBeRemovedIds)) {
                                    $labelsToBeRemoved = $this->labelRepository->fetchItAll($labelsToBeRemovedIds, [], '_id');
                                    if ($this->contactLabelRepository->deleteItAll($labelsToBeRemovedIds, 'labels__id', [
                                        'where' => [
                                            'contacts__id' => $contact->_id,
                                        ]
                                    ])) {
                                        foreach ($labelsToBeRemoved as $removeLabel) {
                                            logSystemVendorChatMessage($contact, 'LABEL_REMOVED', $removeLabel->title);
                                        }
                                    }
                                }
                            }

                            // Get promotional data
                            $promotionalData = data_get($actionBot, 'promotional');

                            // Check if promotional data exists
                            if ($promotionalData != 'no_action') {
                                // Start promotional message
                                if ($promotionalData == 'start_promotional') {
                                    if ($contact->whatsapp_opt_out) {
                                        $this->contactRepository->updateIt($contact, [
                                            'whatsapp_opt_out' => null
                                        ]);
                                    }
                                }
                                // Stop promotional message
                                if ($promotionalData == 'stop_promotional') {
                                    if (!$contact->whatsapp_opt_out) {
                                        $this->contactRepository->updateIt($contact, [
                                            'whatsapp_opt_out' => 1
                                        ]);
                                    }
                                }
                            }

                            // Get AI bot action
                            $aiBotAction = data_get($actionBot, 'ai_bot');

                            // Check if AI Bot actions exists
                            if ($aiBotAction != 'no_action') {
                                // Start AI bot for this contact
                                if ($aiBotAction == 'start_ai_bot') {
                                    if ($contact->disable_ai_bot) {
                                        $this->contactRepository->updateIt($contact, [
                                            'disable_ai_bot' => 0
                                        ]);
                                        logSystemVendorChatMessage($contact, 'ENABLE_AI_BOT', '');
                                    }
                                }

                                // Stop AI Bot for this contact
                                if ($aiBotAction == 'stop_ai_bot') {
                                    if (!$contact->disable_ai_bot) {
                                        $this->contactRepository->updateIt($contact, [
                                            'disable_ai_bot' => 1
                                        ]);
                                        logSystemVendorChatMessage($contact, 'DISABLE_AI_BOT', '');
                                    }
                                }
                            }

                            // Get reply bot action
                            $replyBotAction = data_get($actionBot, 'reply_bot');

                            // Check if Reply Bot actions exists
                            if ($replyBotAction != 'no_action') {
                                // Start Reply bot for this contact
                                if ($replyBotAction == 'start_reply_bot') {
                                    if ($contact->disable_reply_bot) {
                                        $this->contactRepository->updateIt($contact, [
                                            'disable_reply_bot' => 0
                                        ]);
                                        logSystemVendorChatMessage($contact, 'ENABLE_REPLY_BOT', '');
                                    }
                                }

                                // Stop Reply Bot for this contact
                                if ($replyBotAction == 'stop_reply_bot') {
                                    if (!$contact->disable_reply_bot) {
                                        $this->contactRepository->updateIt($contact, [
                                            'disable_reply_bot' => 1
                                        ]);
                                        logSystemVendorChatMessage($contact, 'DISABLE_REPLY_BOT', '');
                                    }
                                }
                            }
                        }

                        $isBotMatched = true;
                        if ($replyText) {
                            $replyText = $this->dynamicValuesReplacement($replyText, $contact, [
                                'assign_tem_member_name' => $assignTeamMemberName
                            ]);
                        }
                        // if interaction message
                        if ($interactionMessageData) {
                            // body text
                            $interactionMessageData['body_text'] = $replyText;
                            // header text assignments
                            if ($interactionMessageData['header_text']) {
                                $interactionMessageData['header_text'] = $this->dynamicValuesReplacement($interactionMessageData['header_text'], $contact, [
                                    'assign_tem_member_name' => $assignTeamMemberName
                                ]);
                            }
                            // footer text assignments
                            if ($interactionMessageData['footer_text']) {
                                $interactionMessageData['footer_text'] = $this->dynamicValuesReplacement($interactionMessageData['footer_text'], $contact, [
                                    'assign_tem_member_name' => $assignTeamMemberName
                                ]);
                            }
                        } elseif ($mediaMessageData) {
                            // caption text
                            $mediaMessageData['caption'] = $this->dynamicValuesReplacement($mediaMessageData['caption'], $contact, [
                                'assign_tem_member_name' => $assignTeamMemberName
                            ]);
                        }

                        // If ai bot is wa template message then get data from __data column
                        if ($templateMessageData) {
                            $contactsData = [
                                '_id' => $contact->_id,
                                '_uid' => $contact->_uid,
                                'first_name' => $contact->first_name,
                                'last_name' => $contact->last_name,
                                'countries__id' => $contact->countries__id
                            ];

                            $templateData = Arr::get($templateMessageData, 'template_data.log_message.__data');
                            $templateProforma = Arr::get($templateData, 'template_proforma');
                            // missing or invalid template data
                            if (!$templateData) {
                                continue;
                            }
                            $sendReplyBotMessageResponse = $this->sendActualWhatsAppTemplateMessage(
                                $contact->vendors__id,
                                $contact->_id,
                                $contact->wa_id,
                                $contact->_uid,
                                Arr::get($templateProforma, 'name'),
                                Arr::get($templateProforma, 'language'),
                                $templateProforma,
                                $templateData['template_components'],
                                $templateData['template_component_values'],
                                null,
                                $contactsData,
                                null,
                                null,
                                [
                                    'send_message_via_marketing_message_api' => data_get($templateMessageData, 'template_data.inputs.send_message_via_marketing_message_api') == 'on' ? true : false
                                ]
                            );
                        } else {
                            $sendReplyBotMessageResponse = $this->sendReplyBotMessage($contact->_uid, $replyText, $contact->vendors__id, $interactionMessageData, [
                                'mediaMessageData' => $mediaMessageData,
                                'from_phone_number_id' => $options['fromPhoneNumberId'],
                                'messageWamid' => $options['messageWamid'],
                                'isTriggerFromQuickReply' => $options['isTriggerFromQuickReply']
                            ]);
                        }

                        if ($testBotId) {
                            return $sendReplyBotMessageResponse;
                        }
                        break;
                    }
                }
            }
        }
        if ($testBotId) {
            return $this->engineFailedResponse([], __tr('Bot Validation Failed due to unmatched'));
        }
        // if time restriction
        if ($isBotTimingsEnabled and !$isBotTimingsInTime and $isAiBotTimingsEnabled) {
            return false;
        }
        // initial ai bot only if manual bot didn't replied
        // ai bot is enabled
        // bot url has been set
        // contact ai bot replies is not disabled
        // has in subscription plan
        $vendorPlanDetails = vendorPlanDetails('ai_chat_bot', 1, $contact->vendors__id);
        if (!$isBotMatched and $vendorPlanDetails['is_limit_available'] and !$contact->disable_ai_bot) {
            $aiBotReplyText = null;
            // open ai
            if (!$aiBotReplyText and getVendorSettings('enable_open_ai_bot', null, null, $contact->vendors__id) and getVendorSettings('open_ai_access_key', null, null, $contact->vendors__id)) {
                try {
                    $aiBotReplyText = app()->make(OpenAiService::class)->generateAnswerFromMultipleSections($messageBody, $contact, $contact->vendors__id);
                    // check if got the reply
                    if ($aiBotReplyText) {
                        $botName = getVendorSettings('open_ai_bot_name', null, null, $contact->vendors__id);
                        if ($botName and !Str::startsWith($aiBotReplyText, $botName . ':')) {
                            $aiBotReplyText =  $botName ? ($botName . ":\n\n" . $aiBotReplyText) : $aiBotReplyText;
                        }
                        $this->sendReplyBotMessage($contact->_uid, $aiBotReplyText, $contact->vendors__id, null, [
                            'ai_bot_reply' => true,
                            'open_ai_reply' => true,
                            'from_phone_number_id' => $options['fromPhoneNumberId'],
                            'messageWamid' => $options['messageWamid'],
                        ]);
                    }
                } catch (\Throwable $e) {
                    // if openai issues
                    if ($e instanceof \OpenAI\Exceptions\ErrorException) {
                        logSystemVendorChatMessage($contact, 'ERROR', $e->getMessage());
                    }
                    __logDebug($e->getMessage());
                    // send error message to the customers
                    if (getVendorSettings('open_ai_failed_message', null, null, $contact->vendors__id)) {
                        $this->sendReplyBotMessage($contact->_uid, getVendorSettings('open_ai_failed_message', null, null, $contact->vendors__id), $contact->vendors__id, null, [
                            'ai_error_triggered' => true,
                            'from_phone_number_id' => $options['fromPhoneNumberId'],
                            'messageWamid' => $options['messageWamid'],
                        ]);
                    }
                }
            }
            // flowise ai
            if (!$aiBotReplyText and getVendorSettings('enable_flowise_ai_bot', null, null, $contact->vendors__id) and getVendorSettings('flowise_url', null, null, $contact->vendors__id)) {
                try {
                    // base request start
                    $botRequest = Http::throw(function ($response, $e) {
                        __logDebug($e->getMessage());
                    });
                    // set the token if required
                    if ($bearerToken = getVendorSettings('flowise_access_token', null, null, $contact->vendors__id)) {
                        $botRequest->withToken($bearerToken);
                    }
                    $aiBotReplyText = $botRequest->post(getVendorSettings('flowise_url', null, null, $contact->vendors__id), [
                        'question' => $messageBody,
                        'overrideConfig' => [
                            'sessionId' => $contact->_uid
                        ],
                    ])->json('text');
                    // check if got the reply
                    if ($aiBotReplyText) {
                        $this->sendReplyBotMessage($contact->_uid, $aiBotReplyText, $contact->vendors__id, null, [
                            'ai_bot_reply' => true,
                            'open_flowise_ai_reply' => true,
                            'from_phone_number_id' => $options['fromPhoneNumberId'],
                            'messageWamid' => $options['messageWamid'],
                        ]);
                    }
                } catch (\Throwable $e) {
                    __logDebug($e->getMessage());
                    // send error message to the customers
                    if (getVendorSettings('flowise_failed_message', null, null, $contact->vendors__id)) {
                        $this->sendReplyBotMessage($contact->_uid, getVendorSettings('flowise_failed_message', null, null, $contact->vendors__id), $contact->vendors__id, null, [
                            'ai_error_triggered' => true,
                            'from_phone_number_id' => $options['fromPhoneNumberId'],
                            'messageWamid' => $options['messageWamid'],
                        ]);
                    }
                }
            }
        }
    }
    /**
     * Formate json data to display
     *
     * @param string $data
     * @param integer $indentLevel
     * @return string
     */
    public function jsonToListRecursive($data, $indentLevel = 0)
    {
        if (is_string($data)) {
            $decodedData = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return "Invalid JSON format.";
            }
        } else {
            $decodedData = $data;
        }
        // Initialize an empty string to hold the list output
        $listOutput = '';
        // Create indentation for nested levels
        $indent = str_repeat('&nbsp;', $indentLevel * 4);
        // Iterate over each key-value pair to build the list format
        foreach ($decodedData as $key => $value) {
            // If the value is an array or object, call the function recursively to list nested data
            if (is_array($value)) {
                $listOutput .= $indent . $key . ":<br>";
                $listOutput .= $this->jsonToListRecursive($value, $indentLevel + 1);
            } else {
                // For simple key-value pairs, append each item to the list output
                $listOutput .= $indent . $key . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "<br>";
            }
        }
        return $listOutput;
    }
    public function processWebhook($request, $vendorUid)
    {
        $monitor = new ServerPerformanceMonitorService();
        if ($monitor->isCritical()) {
            // if server is not in normal state then return
            return $monitor->terminate();
        }

        // Only dispatch Whatsapp calling related webhook for WhatsJetCallingAddon
        $messageEntry = $request->get('entry');
        $webhookField = Arr::get($messageEntry, '0.changes.0.field');
        if ($webhookField == 'calls') {
            event(new WhatsappWebhookReceived($request->all(), $vendorUid));
            return response()->json(['status' => 'success']);
        }

        // if not using db for webhook processing
        // then process the webhook request directly
        if (!getAppSettings('enable_wa_webhook_process_using_db')) {
            return $this->processWebhookRequest($request, $vendorUid);
        }
        // if using db for webhook processing
        // then store the webhook data
        // and process it later
        $vendorId = is_int($vendorUid) ? $vendorUid : getPublicVendorId($vendorUid);
        if (! $vendorId) {
            return false;
        }
        // Store the raw payload and headers
        $webhookEntry = WhatsAppWebhookModel::create([
            '_uid' => YesSecurity::generateUid(),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'vendors__id' => $vendorId,
        ]);
        if (__isEmpty($webhookEntry)) {
            return response()->json(['status' => 'failed'], 500);
        }
        if (getAppSettings('enable_queue_jobs_for_campaigns')) {
            ProcessMessageWebhookJob::dispatch();
        }
        // response
        return response()->json(['status' => 'success']);
    }

    public function sanitizeSdp($sdp)
    {
        // Split SDP by new line (\r\n or \n)
        $lines = preg_split('/\r\n|\r|\n/', $sdp);
        $clean = [];

        foreach ($lines as $line) {
            $clean[] = $line;
        }

        // Join lines back with \r\n (as per SDP spec)
        return implode("\r\n", $clean) . "\r\n";
    }

    /**
     * Webhook for message handling
     *
     * @param Request $request
     * @param string|int $vendorUid
     * @return void
     */
    public function processWebhookRequest($request, $vendorUid)
    {
        emptyFlashCache(); // Clear flash cache before processing
        $vendorId = is_int($vendorUid) ? $vendorUid : getPublicVendorId($vendorUid);
        if (! $vendorId) {
            return false;
        }
        if (is_int($vendorUid)) {
            $vendorUid = getPublicVendorUid($vendorId);
        }
        // check if vendor has active plan
        $vendorPlanDetails = vendorPlanDetails(null, null, $vendorId);
        if (!$vendorPlanDetails->hasActivePlan()) {
            // abort(403, 'no active subscription');
            return false;
        }
        $messageEntry = $request->get('entry');
        $webhookField = Arr::get($messageEntry, '0.changes.0.field');

        // If call_permission_reply is "user_action" then process further
        // else if it is "automatic" then don't process this webhook.
        if (data_get($messageEntry, '0.changes.0.value.messages.0.interactive.type') == 'call_permission_reply' and data_get($messageEntry, '0.changes.0.value.messages.0.interactive.call_permission_reply.response_source') == 'automatic') {
            return true;
        }

        switch ($webhookField) {
            case 'account_update':
                return $this->accountUpdateWebhook($messageEntry, $vendorId);
                break;
            case 'history':
                return $this->historyWebhook($messageEntry, $vendorId);
            case 'smb_message_echoes':
                return true;
                // contacts sync
            case 'smb_app_state_sync':
                return $this->appContactSyncWebhook($messageEntry, $vendorId);
                break;
        }
        $phoneNumberId = Arr::get($messageEntry, '0.changes.0.value.metadata.phone_number_id');
        $messageStatusObject = Arr::get($messageEntry, '0.changes.0.value.statuses');
        $messageObject = Arr::get($messageEntry, '0.changes.0.value.messages');
        $contactBsuid = Arr::get($messageEntry, '0.changes.0.value.contacts.0.user_id');
        $contactUserName = Arr::get($messageEntry, '0.changes.0.value.contacts.0.profile.username');

        $isFromBizAppReplied = false;
        if (!$messageObject) {
            $messageObject = Arr::get($messageEntry, '0.changes.0.value.message_echoes');
            $isFromBizAppReplied = true;
        }
        $messageStatus = null;
        $contactStatus = 'existing';
        // set the webhook messages field as configured if not already done
        if (
            !getVendorSettings('webhook_messages_field_verified_at', null, null, $vendorUid)
            and (Arr::get($messageEntry, '0.changes.0.field') == 'messages')
        ) {
            $this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
                'webhook_messages_field_verified_at' => now()
            ], $vendorId);
            // messages
            updateModelsViaVendorBroadcast($vendorUid, [
                'isWebhookMessagesFieldVerified' => true
            ]);
            // if its test message notification then get back
            if (Arr::get($messageObject, '0.text.body') == 'this is a text message') {
                return false;
            }
        }
        $mediaData = null;
        $waId = null;
        $contactUid = null;
        $campaignUid = null;
        $messageWamid = null;
        // mainly for incoming message
        $messageBody = null;
        $isNewIncomingMessage = false;
        $repliedToWamid = null;
        $messageLogEntry = null;
        if (!$this->whatsAppMessageLogRepository) {
            $this->whatsAppMessageLogRepository = new WhatsAppMessageLogRepository();
        }
        if ($messageStatusObject) {
            $waId = Arr::get($messageStatusObject, '0.recipient_id'); // recipient
            $messageWamid = Arr::get($messageStatusObject, '0.id');
            $messageStatus = Arr::get($messageStatusObject, '0.status');
            $timestamp = Arr::get($messageStatusObject, '0.timestamp');
            $messageLogEntry = $this->whatsAppMessageLogRepository->fetchIt([
                'wamid' => $messageWamid,
                'vendors__id' => $vendorId,
            ]);
            if (__isEmpty($messageLogEntry)) {
                // abort(403, 'message does not found.');
                return false;
            }
            $contact = $this->contactRepository->fetchIt([
                'vendors__id' => $vendorId,
                '_id' => $messageLogEntry->contacts__id,
            ]);
            if (__isEmpty($contact)) {
                // abort(403, 'contact does not found.');
                return false;
            }
            $contactUid = $contact->_uid;
            // Update Record for sent message
            $this->whatsAppMessageLogRepository->updateOrCreateWhatsAppMessageFromWebhook(
                $phoneNumberId,
                $contact->_id,
                $vendorId,
                $waId,
                $messageWamid,
                $messageStatus,
                $messageEntry,
                null,
                $timestamp,
                null,
                true, // do not create new record if not found
                [
                    'full_name' => trim($contact->first_name . ' ' . $contact->last_name),
                ]
            );
        }
        // incoming message
        elseif ($messageObject) {
            // verify the phone number id assigned with is account
            $findPhoneNumber = Arr::first((getVendorSettings('whatsapp_phone_numbers', null, null, $vendorId) ?: []), function ($value, $key) use (&$phoneNumberId) {
                return $value['id'] == $phoneNumberId;
            });
            if (getVendorSettings('current_phone_number_id', null, null, $vendorId) != $phoneNumberId) {
                if (empty($findPhoneNumber)) {
                    // return false;
                    // abort(403, 'from phone number not available.');
                    return false;
                }
            }
            if ($isFromBizAppReplied) {
                $waId = Arr::get($messageObject, '0.to');
            } else {
                $waId = Arr::get($messageObject, '0.from');
            }
            $messageWamid = Arr::get($messageObject, '0.id');
            $messageType = Arr::get($messageObject, '0.type');
            // welcome trigger
            if ($messageType == 'request_welcome') {
                return false;
            }
            // deleted message
            /**
             * @link https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/payload-examples#status--message-deleted
             */
            if (Arr::get($messageObject, '0.errors.code') == 131051) {
                return false;
            }
            $messageBody = null;
            if ($isFromBizAppReplied) {
                $isNewIncomingMessage = false;
            } else {
                $isNewIncomingMessage = true;
            }
            $mediaData = [];
            $otherMessageData = [];
            if (in_array($messageType, [
                'text',
            ])) {
                $messageBody = Arr::get($messageObject, '0.text.body');
            } elseif (in_array($messageType, [
                'interactive',
            ])) {
                $messageBody = Arr::get($messageObject, '0.interactive.button_reply.title');
                if (!$messageBody) {
                    $messageBody = Arr::get($messageObject, '0.interactive.list_reply.title');
                }
                if (!$messageBody) {
                    $nfmReply = Arr::get($messageObject, '0.interactive.nfm_reply.response_json');
                    if (!__isEmpty($nfmReply)) {
                        $messageBody = $this->jsonToListRecursive($nfmReply);
                    }
                }
            } elseif (in_array($messageType, [
                'button',
            ])) {
                $messageBody = Arr::get($messageObject, '0.button.text');
            } elseif (in_array($messageType, [
                'image',
                'video',
                'audio',
                'document',
                'sticker',
            ])) {
                $downloadedFileInfo = $this->mediaEngine->downloadAndStoreMediaFile($this->whatsAppApiService->downloadMedia(Arr::get($messageObject, "0.$messageType.id"), $vendorId), $vendorUid, $messageType);
                $mediaData = [
                    'type' => $messageType,
                    'link' => Arr::get($downloadedFileInfo, 'path'),
                    'caption' => Arr::get($messageObject, "0.$messageType.caption"),
                    'mime_type' => Arr::get($messageObject, "0.$messageType.mime_type"),
                    'file_name' => Arr::get($downloadedFileInfo, 'fileName'),
                    'original_filename' => Arr::get($downloadedFileInfo, 'fileName'),
                ];
            } elseif (in_array($messageType, [
                'location',
                'contacts',
            ])) {
                $otherMessageData = [
                    'type' => $messageType,
                    'data' => Arr::get($messageObject, "0.$messageType"),
                ];
            }
            $timestamp = Arr::get($messageObject, '0.timestamp');
            $reactionMessage = Arr::get($messageObject, '0.reaction.emoji');
            if ($reactionMessage) {
                $repliedToMessage = $repliedToWamid = Arr::get($messageObject, '0.reaction.message_id');
                $messageBody = $reactionMessage;
            } else {
                // replied message
                $repliedToMessage = $repliedToWamid = Arr::get($messageObject, '0.context.id');
            }
            if ($repliedToMessage) {
                $repliedToMessage = $this->whatsAppMessageLogRepository->fetchIt([
                    'wamid' => $repliedToMessage,
                    'vendors__id' => $vendorId,
                ]);
                if (! __isEmpty($repliedToMessage)) {
                    $repliedToMessage = $repliedToMessage->_uid;
                } else {
                    $repliedToMessage = null;
                }
            }
            $isForwarded = Arr::get($messageObject, '0.context.forwarded');
            $contact = $this->contactRepository->getVendorContactByWaId($waId, $vendorId);
            if (!__isEmpty($contact)) {
                // update contact name
                if (!$contact->first_name) {
                    $profileName = Arr::get($messageEntry, '0.changes.0.value.contacts.0.profile.name');
                    $firstName = Arr::get(explode(' ', $profileName), '0');
                    $contact = $this->contactRepository->updateIt($contact, [
                        'first_name' => $firstName,
                        'last_name' => str_replace($firstName, ' ', $profileName),
                    ], $vendorId);
                    $contactStatus = 'updated';
                }
            } else {
                // check the feature limit
                $vendorPlanDetails = vendorPlanDetails('contacts', $this->contactRepository->countIt([
                    'vendors__id' => $vendorId
                ]), $vendorId);
                if (!$vendorPlanDetails['is_limit_available']) {
                    // return false;
                    // abort(403, 'plan limit not available.');
                    return false;
                }
                $profileName = Arr::get($messageEntry, '0.changes.0.value.contacts.0.profile.name');
                $firstName = Arr::get(explode(' ', $profileName), '0');
                $contact = $this->contactRepository->storeContact([
                    'first_name' => $firstName,
                    'last_name' => str_replace($firstName, ' ', $profileName),
                    'phone_number' => $waId,
                ], $vendorId);
                $contactStatus = 'new';
            }
            // check it again
            if (__isEmpty($contact)) {
                $contact = $this->contactRepository->getVendorContactByWaId($waId, $vendorId);
                // if still not found
                if (__isEmpty($contact)) {
                    return false;
                }
            }
            $contactUid = $contact->_uid;
            $hasLogEntryOfMessage = false;
            if ($messageWamid) {
                $hasLogEntryOfMessage = $this->whatsAppMessageLogRepository->countIt([
                    'wamid' => $messageWamid,
                    'vendors__id' => $vendorId,
                ]);
            }
            // prevent repeated message creation
            if ($hasLogEntryOfMessage) {
                return false;
            }
            // create Record for sent message
            $this->whatsAppMessageLogRepository->storeIncomingMessage(
                $phoneNumberId,
                $contact->_id,
                $vendorId,
                $waId, // sender
                $messageWamid,
                $messageEntry,
                $messageBody,
                $timestamp,
                $mediaData,
                $repliedToMessage,
                $isForwarded,
                $otherMessageData,
                $isNewIncomingMessage
            );
        }
        if ($messageWamid) {
            if (__isEmpty($messageLogEntry)) {
                $messageLogEntry = $this->whatsAppMessageLogRepository->fetchIt([
                    'wamid' => $messageWamid,
                    'vendors__id' => $vendorId,
                ]);
            }
            // get the campaign if required
            if (!__isEmpty($messageLogEntry) and $messageLogEntry->campaigns__id) {
                $campaign = $this->campaignRepository->fetchIt([
                    'vendors__id' => $vendorId,
                    '_id' => $messageLogEntry->campaigns__id,
                ]);
                if (!__isEmpty($campaign)) {
                    $campaignUid = $campaign->_uid;
                    $queueItem = $this->whatsAppMessageQueueRepository->fetchIt([
                        'vendors__id' => $vendorId,
                        'campaigns__id' => $campaign->_id,
                        'contacts__id' => $contact->_id,
                    ]);
                    if (!__isEmpty($queueItem)) {
                        $itemRetries = $queueItem->retries ?: 1;
                        // Marketing messages failed due to healthy ecosystem to delivery we may reschedule it
                        if (getAppSettings('enable_requeue_healthy_error_msg') and (Arr::get($messageStatusObject, '0.errors.0.code') == '131049') and ($itemRetries < 5)) {
                            $reScheduledAt = now()->addHours($itemRetries + 3); // try in next three hours
                            $this->whatsAppMessageQueueRepository->updateIt($queueItem, [
                                'status' => 1, // re queue
                                'retries' => $itemRetries + 1,
                                'scheduled_at' => $reScheduledAt,
                                '__data' => [
                                    'process_response' => [
                                        'error_message' => Arr::get($messageStatusObject, '0.errors.0.message'),
                                        'error_status' => 'requeued_maintain_healthy_ecosystem',
                                    ]
                                ]
                            ]);
                            // delete the log entry
                            $this->whatsAppMessageLogRepository->deleteIt([
                                'wamid' => $messageWamid,
                                'vendors__id' => $vendorId,
                            ]);
                            // if queue worker is set add the queue to process it
                            if (getAppSettings('enable_queue_jobs_for_campaigns')) {
                                ProcessCampaignMessagesJob::dispatch()->delay($reScheduledAt->addMinute());
                            }
                            return false;
                        } else {
                            // delete queue entry
                            $this->whatsAppMessageQueueRepository->deleteIt([
                                'vendors__id' => $vendorId,
                                'campaigns__id' => $campaign->_id,
                                'contacts__id' => $contact->_id,
                            ]);
                        }
                    }
                }
            }
        }

        if ($contactUid) {
            $contact = $this->contactRepository->with('lastMessage')->getVendorContactByWaId($contactUid, $vendorId);
            // Handling error when contact not exist in our system
            if (__isEmpty($contact)) {
                return false;
            }

            // Check if BSUID exists
            if (!__isEmpty($contactBsuid)) {
                $this->contactRepository->updateIt($contact, [
                    'username' => $contactUserName,
                    'bsuid' => $contactBsuid
                ]);
            }
            // Dispatch event for message
            event(new VendorChannelBroadcast($vendorUid, [
                'message_status' => $messageStatus ?? null,
                'contactUid' => $contactUid,
                'contactWaId' => $waId,
                'isNewIncomingMessage' => $isNewIncomingMessage,
                'campaignUid' => $campaignUid,
                'lastMessageUid' => $contact->lastMessage?->_uid,
                'assignedUserId' => $contact->assigned_users__id,
                'formatted_last_message_time' => $contact->lastMessage?->formatted_message_time,
                'contactDescription' => ($contact->full_name ?: $contact->wa_id),
            ]));

            if ($messageBody and !$isFromBizAppReplied) {
                fromPhoneNumberIdForRequest($phoneNumberId);
                // process the bot if needed any
                $this->processReplyBot($contact, $messageBody, null, [
                    'fromPhoneNumberId' => $phoneNumberId,
                    'messageWamid' => $messageWamid,
                ]);
            }
            // call webhook
            dispatchVendorWebhook($vendorId, [
                'contact' => array_merge([
                    'status' => $contactStatus,
                    'phone_number' => $contact->wa_id,
                    'uid' => $contact->_uid,
                ], $contact->only([
                    'first_name',
                    'last_name',
                    'email',
                    'language_code',
                ]), [
                    'country' => $contact->country?->name,
                ]),
                'message' => [
                    'whatsapp_business_phone_number_id' => $phoneNumberId ?? null,
                    'whatsapp_message_id' => $messageWamid ?? null,
                    'replied_to_whatsapp_message_id' => $repliedToWamid,
                    'is_new_message' => $isNewIncomingMessage,
                    'body' => $messageBody,
                    'status' => $messageStatus ?? null,
                    'media' => $mediaData,
                ],
                'whatsapp_webhook_payload' => $request->all()
            ]);
            return true;
        }
        // something may not available try again
        // abort(403, 'may not matched anything to process.');
        return false;
    }
    /**
     * Account updates via webhook
     *
     * @param array $request
     * @param int $vendorId
     * @return void
     */
    function accountUpdateWebhook($entry, $vendorId)
    {
        // PARTNER_REMOVED
        $event = Arr::get($entry, '0.changes.0.value.event');
        if ($event === 'PARTNER_REMOVED') {
            if ($vendorId and getVendorSettings('embedded_setup_done_at', null, null, $vendorId)) {
                $this->processDisconnectAccount($vendorId);
            }
        }
    }
    /**
     * Contact sync via webhook
     *
     * @param array $entry
     * @param int $vendorId
     * @return void
     */
    function appContactSyncWebhook($entry, $vendorId)
    {
        $rawContacts = Arr::get($entry, '0.changes.0.value.state_sync');
        $contacts = [];
        $numbersToProcess = [];
        foreach ($rawContacts as $rawContactKey => $rawContact) {
            if (($rawContact['contact']['action'] ?? '') == 'remove') {
                unset($rawContacts[$rawContactKey]);
                continue;
            }
            $numbersToProcess[] = $rawContact['contact']['phone_number'];
        }

        $vendorAllContacts = $this->contactRepository->with(['groups', 'customFieldValues'])->fetchItAll(
            $numbersToProcess,
            ['_id', '_uid', 'wa_id', 'disable_ai_bot'],
            'wa_id',
            [
                // for this vendor
                'where' => [
                    'vendors__id' => $vendorId,
                ]
            ]
        )?->keyBy('wa_id')?->toArray() ?: [];
        foreach ($rawContacts as $rawContactKey => $rawContact) {
            if (($rawContact['contact']['action'] ?? '') == 'remove') {
                unset($rawContacts[$rawContactKey]);
                continue;
            }
            $numbersToProcess[] = $rawContact['contact']['phone_number'];
            $firstName = Arr::get(explode(' ', $rawContact['contact']['full_name'] ?? ''), '0');
            $phoneNumber = $rawContact['contact']['phone_number'];
            $contactToUpdate = [
                // '_uid' => (string) Str::uuid(),
                'first_name' => $firstName,
                'last_name' => str_replace($firstName, ' ', $rawContact['contact']['full_name'] ?? ''),
                'wa_id' => $phoneNumber,
                'vendors__id' => $vendorId,
            ];
            // $contactToUpdate['_uid'] = $vendorAllContacts[$phoneNumber]['_uid'] ?? (string) Str::uuid();
            $contacts[] = [
                '_uid' => $vendorAllContacts[$phoneNumber]['_uid'] ?? (string) Str::uuid(),
                'first_name' => $firstName,
                'last_name' => str_replace($firstName, ' ', $rawContact['contact']['full_name'] ?? ''),
                'wa_id' => $phoneNumber,
                'vendors__id' => $vendorId,
            ];
        }
        if (!empty($contacts)) {
            // check the feature limit
            $vendorPlanDetails = vendorPlanDetails('contacts', $this->contactRepository->countIt([
                'vendors__id' => $vendorId
            ]) + count($contacts), $vendorId);
            if (!$vendorPlanDetails['is_limit_available']) {
                return false;
            }
            // update or create contacts
            $this->contactRepository->bunchUpsert($contacts, [
                'vendors__id',
                'wa_id',
            ]);
        }
    }
    /**
     * history updates via webhook
     *
     * @param array $request
     * @param int $vendorId
     * @return void
     *
     * @link https://developers.facebook.com/docs/whatsapp/embedded-signup/custom-flows/onboarding-business-app-users/#step-1--initiate-contacts-synchronization
     */
    function historyWebhook($entry, $vendorId) {}
    /**
     * Update the unread count via client model updates
     *
     * @return EngineResponse
     */
    public function updateUnreadCount()
    {
        $updateData = [
            'unreadMessagesCount' => 0,
            'myUnassignedUnreadMessagesCount' => 0,
            'usersUnreadMessagesCount' => [],
            'myAssignedUnreadMessagesCount' => $this->whatsAppMessageLogRepository->getMyAssignedUnreadMessagesCount()
        ];
        if (isVendorAdmin(getVendorId()) or !hasVendorAccess('assigned_chats_only')) {
            $updateData['unreadMessagesCount'] = $this->whatsAppMessageLogRepository->getUnreadCount();
            $updateData['myUnassignedUnreadMessagesCount'] = $this->whatsAppMessageLogRepository->getMyAssignedUnreadMessagesCount(null, null, null);
            $vendorMessagingUsers = $this->userRepository->getVendorMessagingUsers(getVendorId());
            foreach ($vendorMessagingUsers as $vendorMessagingUser) {
                $updateData['usersUnreadMessagesCounts'][$vendorMessagingUser->_uid] = $this->whatsAppMessageLogRepository->getMyAssignedUnreadMessagesCount(null, null, $vendorMessagingUser->_id);
            }
        } else {
            $updateData['unreadMessagesCount'] = $updateData['myAssignedUnreadMessagesCount'];
        }
        updateClientModels($updateData);
        return $this->engineSuccessResponse([]);
    }

    /**
     * Update the unread count via client model updates
     *
     * @return EngineResponse
     */
    public function refreshHealthStatus()
    {
        $healthStatus = $this->whatsAppApiService->healthStatus();
        $whatsAppBusinessAccountId = getVendorSettings('whatsapp_business_account_id');
        if (!$whatsAppBusinessAccountId) {
            return $this->engineFailedResponse([], __tr('WhatsApp Business Account ID not found'));
        }
        $now = now();
        $healthData = [
            'whatsapp_health_status_data' => [
                $whatsAppBusinessAccountId => [
                    'whatsapp_business_account_id' => $whatsAppBusinessAccountId,
                    'health_status_updated_at' => $now,
                    'health_status_updated_at_formatted' => formatDateTime($now),
                    'health_data' => $healthStatus,
                ]
            ]
        ];
        // store information
        $this->vendorSettingsEngine->updateProcess('internals', $healthData);
        // update models
        updateClientModels([
            'healthStatusData' => $healthData['whatsapp_health_status_data'][$whatsAppBusinessAccountId]
        ]);
        return $this->engineSuccessResponse([], __tr('WhatsApp Business Health Data Refreshed'));
    }
    /**
     * Update the unread count via client model updates
     *
     * @return EngineResponse
     */
    public function processSyncPhoneNumbers()
    {
        $phoneNumbers = $this->whatsAppApiService->phoneNumbers()['data'] ?? [];
        if (empty($phoneNumbers)) {
            return $this->engineFailedResponse([], __tr('Phone numbers not available'));
        }
        foreach ($phoneNumbers as $key => $phoneNumber) {
            // get phone info
            $phoneNumbers[$key]['phone_status_info'] = $this->whatsAppApiService->phoneInfo($phoneNumber['id']);
        }
        $whatsAppBusinessAccountId = getVendorSettings('whatsapp_business_account_id');
        if (!$whatsAppBusinessAccountId) {
            return $this->engineFailedResponse([], __tr('WhatsApp Business Account ID not found'));
        }
        $vendorId = getVendorId();
        $phoneNumberRecord = Arr::first(($phoneNumbers ?? []), function ($value, $key) {
            return $value['id'] == getVendorSettings('current_phone_number_id');
        });
        if (!$phoneNumberRecord) {
            $phoneNumberRecord = $phoneNumbers[0];
        }
        $marketingMessageOnboardingStatus = $this->whatsAppApiService->getMarketingMessageOnboardingStatus()['marketing_messages_onboarding_status'] ?? '';

        if (!$this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
            'whatsapp_phone_numbers' => $phoneNumbers,
            'current_phone_number_number' => cleanDisplayPhoneNumber($phoneNumberRecord['display_phone_number']),
            'current_phone_number_id' => $phoneNumberRecord['id'],
            'marketing_messages_onboarding_status' => $marketingMessageOnboardingStatus
        ], $vendorId)) {
            return $this->engineFailedResponse(['show_message' => true], __tr('Failed to update Phone Numbers.'));
        };
        // update models
        updateClientModels([
            'whatsAppPhoneNumbers' => $phoneNumbers,
            'marketingMessageOnboardingStatus' => $marketingMessageOnboardingStatus
        ]);
        return $this->engineSuccessResponse([], __tr('WhatsApp Business Phone Numbers Synced'));
    }

    /**
     * Process enable template analytics
     *
     * @return EngineResponse
     */
    public function processEnableTemplateAnalytics()
    {
        $templateAnalyticsData = $this->whatsAppApiService->processEnableTemplateAnalytics();

        if (__isEmpty($templateAnalyticsData['id'] ?? null)) {
            return $this->engineFailedResponse(['show_message' => true], __tr('Failed to enable template analytics.'));
        }

        if (!$this->vendorSettingsEngine->updateProcess('whatsapp_cloud_api_setup', [
            'template_analytics_status' => 'ENABLED'
        ], getVendorId())) {
            return $this->engineFailedResponse(['show_message' => true], __tr('Failed to enable template analytics.'));
        };
        // update models
        updateClientModels([
            'templateAnalyticsStatus' => 'ENABLED'
        ]);
        return $this->engineSuccessResponse([], __tr('Template analytics enabled.'));
    }

    /**
     * Format the message like whatsapp do
     *
     * @param string $text
     * @return string
     */
    protected function formatWhatsAppText($text)
    {
        return formatWhatsAppText($text);
    }

    /**
     * Format system message
     *
     * @param array $text
     * @return string
     */
    protected function formatSystemMessage($systemMessageData)
    {
        $message = '';
        if (!__isEmpty($systemMessageData)) {
            $action = configItem('system_message_actions', $systemMessageData['action']);
            $dynamicKey = $systemMessageData['dynamicKey'];
            $dynamicValue = $systemMessageData['dynamicValue'];

            $message = strtr($action, [$dynamicKey => $dynamicValue]);
        }

        return $message;
    }

    public function setupWhatsAppEmbeddedSignUpProcess($request)
    {
        $processedResponse = $this->whatsAppConnectApiService->processEmbeddedSignUp($request);
        if ($processedResponse->success()) {
            sleep(1);
            updateProgressTextModel(
                __tr('Updating health status...')
            );
            $this->refreshHealthStatus();
            sleep(1);
            updateProgressTextModel(
                __tr('syncing phone numbers...')
            );
            $this->processSyncPhoneNumbers();
        }
        return $processedResponse;
    }
    /**
     * Disconnect Account
     *
     * @return EngineResponse
     */
    public function processDisconnectAccount($vendorId = null)
    {
        $vendorId  = $vendorId ?: getVendorId();
        if (!isWhatsAppBusinessAccountReady($vendorId)) {
            return $this->engineFailedResponse([], __tr('Account should be ready in order to disconnect.'));
        }
        try {
            ignoreFacebookApiError(true);
            // remove webhooks
            if (!getVendorSettings('embedded_setup_done_at', null, null, $vendorId)) {
                $this->processDisconnectWebhook($vendorId);
            } else {
                $this->whatsAppConnectApiService->removeExistingWebhooks(getVendorSettings('whatsapp_business_account_id', null, null, $vendorId), getVendorSettings('whatsapp_access_token', null, null, $vendorId));
            }
            ignoreFacebookApiError(false);
        } catch (\Throwable $th) {
            ignoreFacebookApiError(false);
            //throw $th;
        }
        if ($this->vendorSettingsEngine->deleteItemProcess([
            'embedded_setup_done_at',
            'facebook_app_id',
            'facebook_app_secret',
            'whatsapp_access_token',
            'whatsapp_business_account_id',
            'current_phone_number_number',
            'current_phone_number_id',
            'webhook_verified_at',
            'webhook_messages_field_verified_at',
            'whatsapp_phone_numbers_data',
            'whatsapp_onboarding_raw_data',
            'whatsapp_token_info_data',
        ], $vendorId)->success()) {
            return $this->engineSuccessResponse([], __tr('Account has been disconnected successfully'));
        }
        return $this->engineFailedResponse([], __tr('Failed to disconnect account'));
    }
    /**
     * Disconnect Webhook
     *
     * @return EngineResponse
     */
    public function processDisconnectWebhook($vendorId = null)
    {
        $vendorId  = $vendorId ?: getVendorId();
        if (!getVendorSettings('facebook_app_secret', null, null, $vendorId)) {
            return $this->engineFailedResponse([], __tr('Missing App Secret'));
        }
        $processedResponse = $this->whatsAppConnectApiService->disconnectBaseWebhook(getVendorSettings('facebook_app_id', null, null, $vendorId), getVendorSettings('facebook_app_secret', null, null, $vendorId), getVendorSettings('whatsapp_business_account_id', null, null, $vendorId));
        if (isset($processedResponse['success']) and $processedResponse['success']) {
            if ($this->vendorSettingsEngine->deleteItemProcess([
                'webhook_verified_at',
                'webhook_messages_field_verified_at',
            ], $vendorId)->success()) {
                // messages
                updateClientModels([
                    'isWebhookMessagesFieldVerified' => false,
                    'isWebhookVerified' => false,
                ]);
                return $this->engineSuccessResponse([], __tr('Webhook Disconnected'));
            }
        }
        return $this->engineFailedResponse([], __tr('Nothing to disconnect'));
    }
    /**
     * Connect Webhook
     *
     * @return void
     */
    public function processConnectWebhook()
    {
        $vendorUid = getVendorUid();
        if (!getVendorSettings('whatsapp_access_token')) {
            return $this->engineFailedResponse([], __tr('Invalid Request'));
        }
        if (getVendorSettings('embedded_setup_done_at')) {
            $processedResponse = $this->whatsAppConnectApiService->connectWebhookOverrides($vendorUid, getVendorSettings('whatsapp_business_account_id'));
        } else {
            $processedResponse = $this->whatsAppConnectApiService->connectBaseWebhook(getVendorSettings('facebook_app_id'), getVendorSettings('facebook_app_secret'), $vendorUid);
        }
        if (isset($processedResponse['success']) and $processedResponse['success']) {
            sleep(1); // wait for a second to reflect the changes
            $this->processSyncPhoneNumbers();
            return $this->engineSuccessResponse([], __tr('Webhook Connected'));
        }
        return $this->engineFailedResponse([], __tr('Nothing to connect'));
    }

    /**
     * Requeue the failed messages
     *
     * @param BaseRequestTwo $request
     * @param sting $campaignUid
     * @return EngineResponse
     */
    public function processRequeueFailedMessages($request, $campaignUid)
    {

        $campaign = $this->campaignRepository->fetchIt([
            '_uid' => $campaignUid,
            'vendors__id' => getVendorId(),
            'status' => 1,
        ]);

        if (__isEmpty($campaign)) {
            return $this->engineFailedResponse([], __tr('No active campaign found'));
        }
        $updateMessagesWhere = [
            'campaigns__id' => $campaign->_id
        ];
        // stuck in processing messages
        $updatingMessages = $this->whatsAppMessageQueueRepository->countIt($updateMessagesWhere);
        if (getAppSettings('enable_queue_jobs_for_campaigns')) {
            if ($updatingMessages) {
                $numberOfJobs = ceil($updatingMessages / getAppSettings('cron_process_messages_per_lot'));
                $numberOfJobs = $numberOfJobs + ceil($numberOfJobs * 0.1);
                for ($i = 0; $i < $numberOfJobs; $i++) {
                    ProcessCampaignMessagesJob::dispatch();
                }
            }
        }
        if ($this->whatsAppMessageQueueRepository->updateItAll($updateMessagesWhere, [
            'status' => 1
        ])) {
            return $this->engineSuccessResponse([], __tr('Requeued the failed messages'));
        };

        return $this->engineFailedResponse([], __tr('Nothing to requeue'));
    }

    /**
     * Request Business Profile Information
     *
     * @param int $phoneNumberId
     * @return EngineResponse
     */
    public function requestBusinessProfile($phoneNumberId)
    {
        $businessProfile = $this->whatsAppApiService->businessProfile($phoneNumberId);
        return $this->engineSuccessResponse([
            'phoneNumberId' => $phoneNumberId,
            'businessProfile' => $businessProfile['data'][0] ?? []
        ]);
    }

    /**
     * Update WhatsApp Business Number Profile
     *
     * @param BaseRequestTwo $request
     * @return EngineResponse
     */
    public function requestUpdateBusinessProfile($request)
    {
        $dataToUpdate = array_filter($request->only([
            'address',
            'description',
            'vertical',
            'about',
            'email',
            'websites',
        ]));
        if ($request->uploaded_media_file_name) {
            $resumableUploadFileId = $this->whatsAppApiService->uploadResumableMedia($request->uploaded_media_file_name, [
                'binary' => true
            ]);
            $dataToUpdate['profile_picture_handle'] = $resumableUploadFileId;
        }
        $businessProfile = $this->whatsAppApiService->updateBusinessProfile($request->phoneNumberId, $dataToUpdate);
        if ($businessProfile['success'] ?? null) {
            return $this->engineSuccessResponse([], __tr('Business Profile Updated'));
        }
        return $this->engineFailedResponse([], __tr('Failed to update Business Profile'));
    }

    /**
     * Request Display Name
     *
     * @param int $phoneNumberId
     * @return EngineResponse
     */
    public function requestDisplayName($phoneNumberId)
    {
        $displayNameData = $this->whatsAppApiService->displayName($phoneNumberId);
        $newDisplayNameData = $this->whatsAppApiService->newDisplayName($phoneNumberId);
        $newNameStatus = data_get($newDisplayNameData, 'name_status', '-');
        $newDisplayName = data_get($newDisplayNameData, 'new_display_name', '-');
        $pendingRequestData = null;
        // Check if new display name status is approved and display name are same
        if (($newDisplayName != '-') and ($newDisplayName != ($displayNameData['verified_name'] ?? '')) and ($newNameStatus == 'APPROVED')) {
            // update pin
            $this->whatsAppApiService->requestTwoStepVerificationSet($phoneNumberId, [
                'pin' => '123456',
            ]);
            // register phone
            $this->whatsAppApiService->registerPhoneNumber($phoneNumberId, [
                'messaging_product' => 'whatsapp',
                'pin' => '123456',
            ]);
            $displayNameData = $this->whatsAppApiService->displayName($phoneNumberId);
        } else {
            $pendingRequestData = $newDisplayNameData;
        }

        return $this->engineSuccessResponse([
            'phoneNumberId' => $phoneNumberId,
            'displayNameData' => $displayNameData,
            'pendingRequestData' => $pendingRequestData,
        ]);
    }

    /**
     * Update Display Name
     *
     * @param BaseRequestTwo $request
     * @return EngineResponse
     */
    public function requestUpdateDisplayName($request)
    {
        // Check if input verified name and existing display name match
        if (getVendorSettings('whatsapp_display_name') == $request->verified_name) {
            return $this->engineResponse(14, null, __tr('Nothing updated.'));
        }

        $displayName = $this->whatsAppApiService->updateDisplayName($request);

        if ($displayName['success'] ?? null) {
            setVendorSettings(
                'internals',
                [
                    'whatsapp_display_name' => $request->verified_name
                ],
                getVendorId()
            );

            return $this->engineSuccessResponse([], __tr('Request to change display name has been submitted.'));
        }
        return $this->engineFailedResponse([], __tr('Failed to submit request to change display name.'));
    }

    /**
     * Register Phone Number
     *
     * @param array $inputData
     * @return EngineResponse
     */
    public function processRegisterPhoneNumber($inputData)
    {
        $registerPhoneNumberData = $this->whatsAppApiService->registerPhoneNumber($inputData['phone_number_id'], [
            'messaging_product' => 'whatsapp',
            'pin' => $inputData['pin'],
        ]);

        if ($registerPhoneNumberData['success'] ?? null) {
            $displayNameData = $this->whatsAppApiService->displayName($inputData['phone_number_id']);
            return $this->engineSuccessResponse([], __tr('Phone number register successfully.'));
        }

        return $this->engineFailedResponse([], __tr('Failed to submit request to register phone number.'));
    }

    /**
     * Update WhatsApp Business Number Profile
     *
     * @param BaseRequestTwo $request
     * @return EngineResponse
     */
    public function processTwoStepVerification($request)
    {
        $dataToUpdate = array_filter($request->only([
            'pin',
        ]));

        $isCodeReset = $this->whatsAppApiService->requestTwoStepVerificationSet($request->phoneNumberId, $dataToUpdate);
        if ($isCodeReset['success'] ?? null) {
            $this->processSyncPhoneNumbers();
            return $this->engineSuccessResponse([], __tr('Two Step Verification PIN Updated'));
        }
        return $this->engineFailedResponse([], __tr('Failed to update Two Step Verification PIN'));
    }

    /**
     * In allowed bot timings
     *
     * @param int $vendorId
     * @return boolean
     */
    protected function isInAllowedBotTiming($vendorId)
    {
        if (getVendorSettings('enable_bot_timing_restrictions', null, null, $vendorId)) {
            $now = Carbon::now(getVendorSettings('bot_timing_timezone'));
            $botStartTime = Carbon::createFromFormat('H:i', getVendorSettings('bot_start_timing', null, null, $vendorId), getVendorSettings('bot_timing_timezone', null, null, $vendorId));
            $botEndTime = Carbon::createFromFormat('H:i', getVendorSettings('bot_end_timing', null, null, $vendorId), getVendorSettings('bot_timing_timezone', null, null, $vendorId));
            if ($botEndTime->lte($botStartTime)) {
                $botEndTime = $botEndTime->addDay();
            }
            if ($now->between($botStartTime, $botEndTime)) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * fetch
     *
     * @param int $vendorId
     * @return boolean
     */
    public function fetchWhatsappMessageLogData($type, $startDate, $endDate)
    {
        $vendorId = getVendorId();
        $messageLogCollection = $this->whatsAppMessageLogRepository->fetchMessageLogDataTableSource($type, $startDate, $endDate);
        $isDemoModeAndAccount = isThisDemoVendorAccountAccess();
        // required columns for DataTables
        $requireColumns = [
            '_id',
            '_uid',
            'message',
            'messageVia' => function ($rowData) {
                $campaignId = $rowData['campaigns__id'] ?? null; // Ensure key exists and assign null if missing
                // Ensure '__data' exists and is an array
                $data = $rowData['__data'] ?? [];

                // Ensure 'options' exists and is an array
                $options = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
                // Extract bot replies safely
                $botReply = $options['bot_reply'] ?? '';
                $aiBotReply = $options['ai_bot_reply'] ?? '';

                if (!empty($aiBotReply)) {
                    // If AI bot reply exists (even if botReply also exists), classify as AI Bot
                    return 'aibot';
                }

                if (!empty($botReply)) {
                    // If only bot reply exists (and aiBotReply is empty), classify as Bot
                    return 'bot';
                }

                if ($campaignId) {
                    // fetch campaign
                    $campaign = $this->campaignRepository->fetchIt($campaignId);

                    // Ensure '_uid' exists and return UID
                    return $campaign['_uid'];
                }

                $callDirection = data_get($data, 'webhook_responses.incoming.0.changes.0.value.calls.0.direction');
                $isFreeFormCallPermissionRequest = data_get($data, 'options.is_free_form_call_permission_request');

                // Check if user initiate the call
                if ($callDirection == 'USER_INITIATED' or $callDirection == 'BUSINESS_INITIATED' or $isFreeFormCallPermissionRequest) {
                    return 'whatsapp_call';
                }

                return null;
            },
            //type
            'is_incoming_message' => function ($rowData) {

                //  return normal Incoming/Outgoing
                return $rowData['is_incoming_message'] ? __tr('Incoming') : __tr('Outgoing');
            },

            'status',
            //sender
            'from' => function ($rowData) use (&$isDemoModeAndAccount) {
                $numberToShow = $rowData['is_incoming_message'] ? $rowData['contact_wa_id'] : $rowData['wab_phone_number_id'];
                return $isDemoModeAndAccount ? maskForDemo($numberToShow, 'phone', $isDemoModeAndAccount) : maskString($numberToShow, 'phone');
            },
            //receiver
            'recepient' => function ($rowData) use (&$isDemoModeAndAccount) {
                $numberToShow = $rowData['is_incoming_message'] ? $rowData['wab_phone_number_id'] : $rowData['contact_wa_id'];
                return $isDemoModeAndAccount ? maskForDemo($numberToShow, 'phone', $isDemoModeAndAccount) : maskString($numberToShow, 'phone');
            },
            //messaged at
            'messaged_at' => function ($rowData) {
                return $rowData['messaged_at'] === null ? '-' : formatDateTime($rowData['messaged_at']);
            },
        ];

        // prepare data for the DataTables
        return $this->dataTableResponse($messageLogCollection, $requireColumns);
    }

    /**
     * Message  data
     *
     * @param  mix  $messageIdOrUid
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function prepareMessageUpdateData($messageIdOrUid)
    {
        $message = $this->whatsAppMessageLogRepository->fetchIt($messageIdOrUid);

        // Check if the message does not exist
        if (__isEmpty($message)) {
            return $this->engineResponse(18, null, __tr('Message not found.'));
        }
        // Check if message was deleted
        if (__isEmpty($message->__data) and __isEmpty($message->message)) {
            $message->message = '<i class="fas fa-trash"></i> ' . __tr('This message was deleted by system for cleanup.');
        } else {
            // Format the WhatsApp message
            $message->message = $this->formatWhatsAppText($message->message);
        }

        $message->template_message = null;
        // If the message is empty or has interaction message data, compile a template message
        if (__isEmpty($message->message) || Arr::get($message->__data, 'interaction_message_data')) {
            $message->template_message = $this->compileMessageWithValues($message->__data);
        }
        return $this->engineSuccessResponse([
            'messageData' => $message,
        ]);
    }

    public function prepareAiBotPreviewData($botReply, $contact)
    {
        $replyText = $botReply->reply_text;
        $interactionMessageData = $botReply->__data['interaction_message'] ?? null;
        $mediaMessageData = $botReply->__data['media_message'] ?? null;
        $templateMessageData = $botReply->__data['template_message'] ?? null;

        if ($replyText) {
            $replyText = $this->dynamicValuesReplacement($replyText, $contact);
        }
        // if interaction message
        if ($interactionMessageData) {
            // body text
            $interactionMessageData['body_text'] = $replyText;
            // header text assignments
            if ($interactionMessageData['header_text']) {
                $interactionMessageData['header_text'] = $this->dynamicValuesReplacement($interactionMessageData['header_text'], $contact);
            }
            // footer text assignments
            if ($interactionMessageData['footer_text']) {
                $interactionMessageData['footer_text'] = $this->dynamicValuesReplacement($interactionMessageData['footer_text'], $contact);
            }
        } elseif ($mediaMessageData) {
            // caption text
            $mediaMessageData['caption'] = $this->dynamicValuesReplacement($mediaMessageData['caption'], $contact);
        } elseif ($templateMessageData) {
            $templatePreview = $this->prepareTemplate($templateMessageData['template_data']['inputs']['template_uid'], ['pageType' => 'bot_reply_form']);
            return $templatePreview['template'];
        }

        $renameKeyArray = [
            'media_link' => 'link',
            'header_type' => 'type'
        ];

        if (__isEmpty($interactionMessageData) and __isEmpty($mediaMessageData) and __isEmpty($templateMessageData)) {
            return "<div class='p-2 lw-plain-message-text'>$replyText</div>";
        }

        return $this->compileMessageWithValues([
            'interaction_message_data' => $interactionMessageData,
            'media_values' => $this->renameArrayKey($mediaMessageData, $renameKeyArray)
        ]);
    }

    /**
     * Prepare Message Status
     *
     * @param  mix  $messageIdOrUid
     * @return EngineResponse
     *---------------------------------------------------------------- */
    public function prepareMessageStatus()
    {
        $logUid = request()->get('log_uid');
        $wamid = request()->get('wamid');

        // Check if log uid or wamid not exists
        if (__isEmpty($logUid) and __isEmpty($wamid)) {
            return $this->engineResponse(2, null, __tr('log_uid or wamid is required.'));
        }

        $messageLogWhereClause = [
            'vendors__id' => getVendorId()
        ];

        // Check if log uid exists
        if (!__isEmpty($logUid)) {
            $messageLogWhereClause['_uid'] = $logUid;
        } elseif (!__isEmpty($wamid)) {
            $messageLogWhereClause['wamid'] = $wamid; // Check if whatsapp message exists
        }

        $message = $this->whatsAppMessageLogRepository->fetchIt($messageLogWhereClause);

        // Check if the message does not exist
        if (__isEmpty($message)) {
            return $this->engineResponse(18, null, __tr('Message not found.'));
        }

        return $this->engineSuccessResponse([
            'status' => $message->status,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at
        ]);
    }

    protected function renameArrayKey($originalArray, $renameKeyArray)
    {
        if (__isEmpty($originalArray)) {
            return null;
        }

        $newArray = [];
        foreach ($originalArray as $key => $value) {
            // Check if key needs to be renamed
            if (array_key_exists($key, $renameKeyArray)) {
                $newKey = $renameKeyArray[$key];
                $newArray[$newKey] = $value;
            } else {
                $newArray[$key] = $value;
            }
        }

        return $newArray;
    }

    /**
     * Send webhook response addon template message
     *
     * @param  Array  $templateMessageData
     * @param object $contact
     * @return Array
     *---------------------------------------------------------------- */
    public function sendWebhookAddonTemplateMessage($templateMessageData, $contact)
    {
        $templateData = Arr::get($templateMessageData, 'log_message.__data');
        $contactsData = Arr::get($templateData, 'contact_data');
        $templateProforma = Arr::get($templateData, 'template_proforma');
        // missing or invalid template data
        if (!$templateData) {
            return false;
        }

        $processedResponse = $this->sendActualWhatsAppTemplateMessage(
            $contact->vendors__id,
            $contact->_id,
            $contact->wa_id,
            $contact->_uid,
            Arr::get($templateProforma, 'name'),
            Arr::get($templateProforma, 'language'),
            $templateProforma,
            $templateData['template_components'],
            $templateData['template_component_values'],
            null,
            $contactsData,
            null
        );

        if ($processedResponse->success()) {
            return [
                'status' => true,
                'data' => $processedResponse->data()
            ];
        }

        return [
            'status' => false,
            'data' => []
        ];
    }
}
