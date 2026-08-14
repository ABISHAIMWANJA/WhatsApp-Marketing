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
 * WhatsAppApiService.php -
 *
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Services;

use App\Yantrana\Base\BaseEngine;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppServiceEngineInterface;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class WhatsAppApiService extends BaseEngine implements WhatsAppServiceEngineInterface
{
    protected $baseApiRequestEndpoint = 'https://graph.facebook.com/v25.0/'; // Base Request endpoint

    protected $waAccountId; // WhatsApp Business Account ID
    protected $whatsAppPhoneNumberId; // Phone number ID
    protected $accessToken; // Access token
    protected $vendorId = null;

    /**
     * Constructor
     *
     *
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct() {}

    /**
     * Configure settings based on vendor id
     *
     * @param string $serviceItem
     * @return mixed
     */
    protected function getServiceConfiguration($serviceItem)
    {
        if ($serviceItem == 'current_phone_number_id') {
            return fromPhoneNumberIdForRequest() ?: getVendorSettings($serviceItem, null, null, $this->vendorId ?: getVendorId());
        }
        return getVendorSettings($serviceItem, null, null, $this->vendorId ?: getVendorId());
    }

    /**
     * Fetch All the templates of the account
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->apiGetRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}/message_templates", [
            'limit' => 500
        ])['data'];
    }
    /**
     * Fetch template details
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/
     * @return array
     */
    public function getTemplate($templateId)
    {
        return $this->apiGetRequest("$templateId", [
            // 'fields' =>'rejected_reason,status,quality_score'
        ]);
    }
    /**
     * Get template rejected reason and status
     *
     * @param int $templateId
     * @return json
     */
    public function getTemplateRejectionReason($templateId)
    {
        return $this->apiGetRequest("$templateId", [
            'fields' => 'rejected_reason,status'
        ]);
    }

    /**
     * Delete template
     *
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/#Deleting
     *
     * @return void
     */
    public function deleteTemplate($whatsAppTemplateName, $whatsAppTemplateId)
    {
        return $this->apiDeleteRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}/message_templates", [
            'name' => $whatsAppTemplateName,
            'hsm_id' => $whatsAppTemplateId,
        ]);
    }

    /**
     * Send Template Message
     *
     * @param  object  $whatsAppTemplate
     * @param  int  $toNumber
     * @param  array  $components
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
     *
     * @return array
     */
    public function sendTemplateMessage($whatsAppTemplateName, $whatsAppTemplateLanguage, $toNumber, $components = [], $vendorId = null, $options = [])
    {
        $options = array_merge([
            'send_message_via_marketing_message_api' => false,
            'business_scope_user_id' => ''
        ], $options);

        if ($vendorId) {
            $this->vendorId = $vendorId;
        }

        $messageEndpoint = 'messages';

        // https://developers.facebook.com/documentation/business-messaging/whatsapp/marketing-messages/get-started
        if ($options['send_message_via_marketing_message_api']) {
            $messageEndpoint = 'marketing_messages';
        }

        $templateMessagePayload = [
            'type' => 'template',
            'template' => [
                'name' => $whatsAppTemplateName,
                'language' => [
                    'code' => $whatsAppTemplateLanguage,
                ],
                'components' => $this->cleanMediaLinks($components),
            ],
        ];

        // Check if business scope user id exists
        if (__isEmpty($toNumber) and !__isEmpty($options['business_scope_user_id'])) {
            $templateMessagePayload['recipient'] = $options['business_scope_user_id'];
        } else {
            $templateMessagePayload['to'] = $toNumber;
        }
        
        return $this->apiPostRequest("{$this->getServiceConfiguration('current_phone_number_id')}/{$messageEndpoint}", $templateMessagePayload);
    }

    private function cleanMediaLinks(array $data): array
    {
        $mediaTypes = ['image', 'video', 'document'];
        $isCarouselTemplate = data_get($data, '1.type'); // carousel

        if ($isCarouselTemplate == 'carousel') {
            $data = $this->cleanCarouselMediaLink($data);
        } else {
            foreach ($data as &$item) {
                if (!isset($item['parameters']) || !is_array($item['parameters'])) {
                    continue;
                }

                foreach ($item['parameters'] as &$param) {
                    foreach ($mediaTypes as $type) {
                        if (isset($param[$type]) and is_array($param[$type])) {
                            if (isset($param[$type]['id']) and !empty($param[$type]['id'])) {
                                unset($param[$type]['link']);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Send Template Message
     *
     * @param  array  $whatsAppTemplate
     * @param  int  $toNumber
     * @param  array  $components
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
     *
     * @return object
     */
    private function cleanCarouselMediaLink($array)
    {
        $collection = collect($array)->map(function ($item) {
            if ($item instanceof Collection) {
                return $this->cleanCarouselMediaLink($item->toArray());
            }

            if (is_array($item)) {
                return $this->cleanCarouselMediaLink($item);
            }

            return $item;
        })->reject(function ($value, $key) {
            return $key === 'link';
        });

        return $collection->toArray();
    }
    /**
     * Send Template Message
     *
     * @param  object  $whatsAppTemplate
     * @param  int  $toNumber
     * @param  array  $components
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
     *
     * @return object
     */
    public function sendTemplateMessageViaPool(&$pool, $queueUid, $whatsAppTemplateName, $whatsAppTemplateLanguage, $toNumber, $components = [], $vendorId = null, $options = [])
    {
        $options = array_merge([
            'send_message_via_marketing_message_api' => false,
            'business_scope_user_id' => ''
        ], $options);
        
        if ($vendorId) {
            $this->vendorId = $vendorId;
        }

        $messageEndpoint = 'messages';

        // https://developers.facebook.com/documentation/business-messaging/whatsapp/marketing-messages/get-started
        if ($options['send_message_via_marketing_message_api']) {
            $messageEndpoint = 'marketing_messages';
        }

        $poolRequestPayload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'type' => 'template',
            'template' => [
                'name' => $whatsAppTemplateName,
                'language' => [
                    'code' => $whatsAppTemplateLanguage,
                ],
                'components' => $this->cleanMediaLinks($components),
            ],
        ];

        // Check if business scope user id exists
        if (__isEmpty($toNumber) and !__isEmpty($options['business_scope_user_id'])) {
            $poolRequestPayload['recipient'] = $options['business_scope_user_id'];
        } else {
            $poolRequestPayload['to'] = $toNumber;
        }

        return $this->baseApiRequest($pool->as($queueUid))->post("{$this->baseApiRequestEndpoint}{$this->getServiceConfiguration('current_phone_number_id')}/{$messageEndpoint}", $poolRequestPayload);
    }

    /**
     * Send Message
     *
     * @param  int  $toNumber
     * @param  int  $toNumber
     * @param  array  $options
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages/#sending-free-form-messages

     *
     * @return array
     */
    public function sendMessage($toNumber, $body, $vendorId = null, $options = [])
    {
        $options = array_merge([
            'repliedToMessageWamid' => null,
            'pool' => null,
            'queueUid' => null,
            'business_scope_user_id' => ''
        ], $options);

        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        $dataToProcess = [
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $body,
            ],
        ];
        if ($options['repliedToMessageWamid']) {
            $dataToProcess['context'] = [
                'message_id' => $options['repliedToMessageWamid']
            ];
        }
        if( $options['pool'] and $options['queueUid']) {
            $dataToProcess['messaging_product'] = 'whatsapp';
            $dataToProcess['recipient_type'] = 'individual';
            return $this->baseApiRequest($options['pool']->as($options['queueUid']))->post("{$this->baseApiRequestEndpoint}{$this->getServiceConfiguration('current_phone_number_id')}/messages", $dataToProcess);
        }

        // Check if business scope user id exists
        if (__isEmpty($toNumber) and !__isEmpty($options['business_scope_user_id'])) {
            $dataToProcess['recipient'] = $options['business_scope_user_id'];
        } else {
            $dataToProcess['to'] = $toNumber;
        }

        return $this->apiPostRequest("{$this->getServiceConfiguration('current_phone_number_id')}/messages", $dataToProcess);
    }

    /**
     * Send Interactive Message
     *
     * @param  int  $toNumber
     * @param  array  $messageData
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages/#interactive-messages

     *
     * @return array
     */
    public function sendInteractiveMessage($toNumber, $messageData, $vendorId = null, $options = [])
    {
        $options = array_merge([
            'pool' => null,
            'queueUid' => null,
            'business_scope_user_id' => ''
        ], $options);

        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        $messageData = array_merge([
            'interactive_type' => 'button',
            'media_link' => '',
            'header_type' => '', // "text", "image", or "video"
            'header_text' => '',
            'body_text' => '',
            'footer_text' => '',
            'buttons' => [],
            'cta_url' => null,
            'action' => null,
            'list_data' => null,
        ], $messageData);
        $interactiveData = [
            'type' => $messageData['interactive_type'],
        ];

        if ($messageData['header_type'] and ($messageData['header_type'] != 'text')) {
            $interactiveData['header'] = [
                'type' => $messageData['header_type'], // Header types can be "text", "image", or "video"
                $messageData['header_type'] => [
                    'link' => $messageData['media_link'], // your media link
                ]
            ];
        } elseif ($messageData['header_type'] and ($messageData['header_type'] == 'text')) {
            $interactiveData['header'] = [
                'type' => 'text', // Header types can be "text", "image", or "video"
                'text' => $messageData['header_text'], // Your header text here
            ];
        }
        if ($messageData['body_text']) {
            $interactiveData['body'] = [
                'text' => $messageData['body_text'], // Your footer text here
            ];
        }
        if ($messageData['footer_text']) {
            $interactiveData['footer'] = [
                'text' => $messageData['footer_text'], // Your footer text here
            ];
        }
        if ($messageData['interactive_type'] == 'list') {
            $sections = [];
            $sectionIndex = 0;
            foreach (((array) $messageData['list_data']['sections']) ?? [] as $sectionKey => $section) {
                $sections[$sectionIndex] = [
                    'title' => $section['title'],
                    'rows' => [],
                ];
                foreach ((array) $section['rows'] as $row) {
                    $sections[$sectionIndex]['rows'][] = [
                        'id' => $row['row_id'],
                        'title' => $row['title'],
                        'description' => $row['description'],
                    ];
                }
                $sectionIndex++;
            }
            $interactiveData['action'] = [
                'button' => $messageData['list_data']['button_text'],
                'sections' => $sections
            ];
        } elseif ($messageData['interactive_type'] == 'cta_url') {
            $interactiveData['action'] = [
                'name' => 'cta_url',
                'parameters' => $messageData['cta_url']
            ];
        } elseif ($messageData['interactive_type'] == 'button') {
            $buttons = [];
            if ($messageData['buttons']) {
                $buttonIndex = 1;
                foreach ($messageData['buttons'] as $button) {
                    $buttons[] = [
                        'type' => 'reply',
                        'reply' => [
                            'id' => 'button-id' . $buttonIndex,
                            'title' => $button,
                        ],
                    ];
                    $buttonIndex++;
                }
                $interactiveData['action'] = [
                    'buttons' => $buttons
                ];
            }
        }

        $dataToProcess = [
            'type' => 'interactive',
            'interactive' => $interactiveData,
        ];

        // Check if business scope user id exists
        if (__isEmpty($toNumber) and !__isEmpty($options['business_scope_user_id'])) {
            $dataToProcess['recipient'] = $options['business_scope_user_id'];
        } else {
            $dataToProcess['to'] = $toNumber;
        }

        if( $options['pool'] and $options['queueUid']) {
            $dataToProcess['messaging_product'] = 'whatsapp';
            $dataToProcess['recipient_type'] = 'individual';
            return $this->baseApiRequest($options['pool']->as($options['queueUid']))->post("{$this->baseApiRequestEndpoint}{$this->getServiceConfiguration('current_phone_number_id')}/messages", $dataToProcess);
        }
        return $this->apiPostRequest("{$this->getServiceConfiguration('current_phone_number_id')}/messages", $dataToProcess);
    }

    /**
     * Send Media Message
     *
     * @param  int  $toNumber
     * @param  int  $toNumber
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages/#media-messages
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages#media-object
     *
     * @return array
     */
    public function sendMediaMessage($toNumber, string $type, string|array $mediaLink, $caption = '', $filename = '', $vendorId = null, $options = [])
    {
        $options = array_merge([
            'pool' => null,
            'queueUid' => null,
            'business_scope_user_id' => ''
        ], $options);

        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        $typeDetails = [];
        if (is_array($mediaLink)) {
            if (isset($mediaLink['id'])) {
                $typeDetails['id'] = $mediaLink['id'];
            } else {
                $typeDetails['link'] = $mediaLink['link'];
            }
        } else {
            $typeDetails['link'] = $mediaLink;
        }
        // if not audio or sticker
        if (! in_array($type, [
            'audio',
            'sticker',
        ])) {
            $typeDetails['caption'] = $caption;
        }
        // if its document
        if (in_array($type, [
            'document',
        ])) {
            $typeDetails['filename'] = $filename;
        }

        $dataToProcess = [
            'type' => $type,
            $type => $typeDetails,
        ];

        if( $options['pool'] and $options['queueUid']) {
            $dataToProcess['messaging_product'] = 'whatsapp';
            $dataToProcess['recipient_type'] = 'individual';
            return $this->baseApiRequest($options['pool']->as($options['queueUid']))->post("{$this->baseApiRequestEndpoint}{$this->getServiceConfiguration('current_phone_number_id')}/messages", $dataToProcess);
        }

        // Check if business scope user id exists
        if (__isEmpty($toNumber) and !__isEmpty($options['business_scope_user_id'])) {
            $dataToProcess['recipient'] = $options['business_scope_user_id'];
        } else {
            $dataToProcess['to'] = $toNumber;
        }

        return $this->apiPostRequest("{$this->getServiceConfiguration('current_phone_number_id')}/messages", $dataToProcess);
    }

    /**
     * Send Message
     *
     * @param  int  $toNumber
     * @param  int  $toNumber
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/mark-message-as-read
     *
     * @return array
     */
    public function markAsRead($toNumber, $messageId, $vendorId = null, $options = [])
    {
        $options = array_merge([
            'business_scope_user_id' => ''
        ], $options);

        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        return $this->apiPostRequest("{$this->getServiceConfiguration('current_phone_number_id')}/messages", [
            'to' => $toNumber,
            'status' => 'read',
            'message_id' => $messageId,
        ]);
    }

    /**
     * Health Status
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/health-status
     *
     * @return array
     */
    public function healthStatus()
    {
        return $this->apiGetRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}", [
            'fields' => 'health_status',
        ]);
    }

    /**
     * Get Phone Numbers
     *
     * @link https://developers.facebook.com/docs/whatsapp/business-management-api/manage-phone-numbers#all-phone-numbers
     *
     * @return array
     */
    public function phoneNumbers()
    {
        $phoneNumbers = $this->apiGetRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}/phone_numbers?fields=display_phone_number,certificate,name_status,new_certificate,new_name_status,last_onboarded_time", []);
        if (!empty($phoneNumbers['data'] ?? [])) {
            foreach ($phoneNumbers['data'] as $phoneNumber) {
                if(!isset($phoneNumber['webhook_configuration']['phone_number']) or empty($phoneNumber['webhook_configuration']['phone_number'])) {
                    continue;
                }
                // delete phone number webhook configuration to avoid webhook issues as we will be using app level webhook configuration
                $this->apiPostRequest($phoneNumber['id'], [
                    'webhook_configuration' => [
                        'override_callback_uri' => ''
                    ]
                ]);
            }
        }
        return $phoneNumbers;
    }

    /**
     * Health Status
     *
     * @link https://developers.facebook.com/documentation/business-messaging/whatsapp/marketing-messages/onboarding#check-waba-onboarding-status-and-eligibility
     *
     * @return array
     */
    public function getMarketingMessageOnboardingStatus()
    {
        return $this->apiGetRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}", [
            'fields' => 'marketing_messages_onboarding_status',
        ]);
    }

    /**
     * Health Status
     *
     * @link https://developers.facebook.com/documentation/business-messaging/whatsapp/analytics/#template-analytics
     *
     * @return array
     */
    public function processEnableTemplateAnalytics()
    {
        return $this->apiPostRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}?is_enabled_for_insights=true");
    }

    /**
     * Get Business Profile
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/reference/business-profiles
     *
     * @return array
     */
    public function businessProfile($whatsAppPhoneNumberId)
    {
        return $this->apiGetRequest("{$whatsAppPhoneNumberId}/whatsapp_business_profile", [
            'fields' => 'about,address,description,email,profile_picture_url,websites,vertical'
        ]);
    }
    /**
     * Update Business Profile request
     *
     * @param int $whatsAppPhoneNumberId
     * @param array $updateData
     * @return response
     */
    public function updateBusinessProfile($whatsAppPhoneNumberId, $updateData)
    {
        return $this->apiPostRequest("{$whatsAppPhoneNumberId}/whatsapp_business_profile", $updateData);
    }

    /**
     * Get Display Name
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/phone-numbers/#getting-display-name-and-display-name-status-via-api
     *
     * @return array
     */
    public function displayName($whatsAppPhoneNumberId)
    {
        return $this->apiGetRequest("{$whatsAppPhoneNumberId}", [
            'fields' => 'verified_name,name_status'
        ]);
    }

    /**
     * Get phone data information
     *
     * @param number $whatsAppPhoneNumberId
     * @return array
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-account-to-number-current-status
     */
    public function phoneInfo($whatsAppPhoneNumberId)
    {
        return $this->apiGetRequest("{$whatsAppPhoneNumberId}", [
            'fields' => 'messaging_limit_tier,status,is_on_biz_app,is_pin_enabled,last_onboarded_time'
        ]);
    }

    /**
     * Get New Display Name
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/phone-numbers/#getting-display-name-and-display-name-status-via-api
     *
     * @return array
     */
    public function newDisplayName($whatsAppPhoneNumberId)
    {
        return $this->apiGetRequest("{$whatsAppPhoneNumberId}", [
            'fields' => 'new_display_name,name_status'
        ]);
    }

    /**
     * Update Display Name
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/phone-numbers/#updating-display-name-via-api
     * 
     * @param obj $request
     * @return response
     */
    public function updateDisplayName($request)
    {
        return $this->apiPostRequest("{$request->phoneNumberId}", [
            'new_display_name' => $request->verified_name
        ]);
    }

    /**
     * Register Phone Number
     *
     * @link https://developers.facebook.com/docs/whatsapp/solution-providers/phone-numbers/registering-phone-numbers/#step-4--register-the-number
     * 
     * @param obj $request
     * @return response // 
     */
    public function registerPhoneNumber($whatsAppPhoneNumberId, $updateData)
    {
        return $this->apiPostRequest("{$whatsAppPhoneNumberId}/register", $updateData);
    }

    /**
     * Update Business Profile request
     *
     * @param int $whatsAppPhoneNumberId
     * @param array $updateData
     * @return response
     */
    public function requestTwoStepVerificationSet($whatsAppPhoneNumberId, $updateData)
    {
        return $this->apiPostRequest("{$whatsAppPhoneNumberId}", $updateData);
    }

    /**
     * Upload Media to WhatsApp server
     * Useful if your uploaded media url is barred by facebook as you may get error like:
     * Your message couldn't be sent because it includes content that other people on Facebook have reported as abusive
     *
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media/#upload-media
     *
     * @param  string  $file  - file local path or url
     * @param  string|null  $mimeType  - required if provided file is url based
     * @return array
     */
    public function uploadMedia(string $file, ?string $mimeType = null)
    {
        try {
            if (Str::startsWith($file, 'http')) {
                if (! $mimeType) {
                    return new Exception(__tr('For the url based media type is required'), 400);
                }
            } else {
                if(!$mimeType) {
                    $mimeType = mime_content_type($file);
                }
            }
            $ch = curl_init();
            $url = $this->baseApiRequestEndpoint . $this->getServiceConfiguration('current_phone_number_id') . '/media';
            $data = [
                'file' => new \CURLFile($file, $mimeType),
                'type' => $mimeType,
                'messaging_product' => 'whatsapp',
            ];
            $headers = [];
            $headers[] = 'Authorization: Bearer ' . $this->getServiceConfiguration('whatsapp_access_token');
            $headers[] = 'Content-type: multipart/form-data';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            if ($result === false) {
                $result = curl_error($ch) . ' - ' . curl_errno($ch);
            } else {
                $resultDecode = json_decode($result, true);
                if ($resultDecode) {
                    $result = $resultDecode;
                    if (! isset($result['error'])) {
                        return $result['id'] ?? null;
                    } else {
                        return new Exception($result['error']['message'], $result['error']['code'] ? $result['error']['code'] : 500);
                    }
                }

                return $result['id'] ?? null;
            }
            curl_close($ch);
        } catch (Exception $e) {
            abortIf(
                true,
                $e->getCode(),
                $e->getMessage()
            );
        }
    }
    /**
     * Resumable Media upload useful for template example
     *
     * @link https://developers.facebook.com/docs/graph-api/guides/upload
     *
     * @param  string  $file  - file local path or url
     * @return array
     */
    public function uploadResumableMedia(string $fileName, $options = [])
    {
        $options = array_merge([
            'binary' => false
        ], $options);
        try {
            $file = getTempUploadedFile($fileName);
            $mimeType = mime_content_type($file);
            $fileLength = filesize($file);
            $createdUploadSessionId = null;
            $uploadSessionRequest = $this->baseApiRequest()->post("{$this->baseApiRequestEndpoint}" . $this->getServiceConfiguration('facebook_app_id') . "/uploads?file_length=$fileLength&file_type=$mimeType", [])->json();
            $ch = curl_init();
            $url = $this->baseApiRequestEndpoint . $uploadSessionRequest['id'];
            $data = [];
            if (!$options['binary']) {
                $data = [
                    'file' => new \CURLFile($file, $mimeType),
                    'type' => $mimeType,
                ];
            }
            $headers = [];
            $headers[] = 'Authorization: OAuth ' . $this->getServiceConfiguration('whatsapp_access_token');
            $headers[] = 'file_offset: 0';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            // required in some cases like whatsapp business profile
            if ($options['binary']) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($ch);
            if ($result === false) {
                $result = curl_error($ch) . ' - ' . curl_errno($ch);
            } else {
                $resultDecode = json_decode($result, true);
                if ($resultDecode) {
                    $result = $resultDecode;
                    if (! isset($result['error'])) {
                        return $result['h'] ?? null;
                    } else {
                        return new Exception($result['error']['message'], $result['error']['code'] ? $result['error']['code'] : 500);
                    }
                }

                return $result;
            }
            curl_close($ch);
        } catch (Exception $e) {
            abortIf(
                true,
                $e->getCode(),
                $e->getMessage()
            );
        }
    }

    /**
     * Download media using media id
     *
     * @param string $mediaId
     * @param int $vendorId
     * @return array
     */
    public function downloadMedia($mediaId, $vendorId = null)
    {
        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        $retrievedMedia = $this->apiGetRequest("$mediaId", []);
        $mediaResponse = $this->baseApiRequest()->get($retrievedMedia['url']);

        return array_merge($retrievedMedia, [
            'body' => $mediaResponse->body(),
        ]);
    }

    /**
     * ----------------------------------------------------------------------------------------------------------------
     * Below are the BASE Requests like get,post, delete etc
     * -----------------------------------------------------------------------------------------------------------------
     */

    /**
     * Manual API requests
     *
     * @return array
     */
    protected function apiGetRequest(string $requestSubject, array $parameters = [])
    {
        return $this->baseApiRequest()->get("{$this->baseApiRequestEndpoint}{$requestSubject}", $parameters)->json();
    }

    /**
     * Manual API requests
     *
     * @return array
     */
    protected function apiPostRequest(string $requestSubject, array $parameters = [])
    {
        return $this->baseApiRequest()->post("{$this->baseApiRequestEndpoint}$requestSubject", array_merge(
            [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
            ],
            $parameters
        ))->json();
    }

    /**
     * Manual API requests
     *
     * @return array
     */
    protected function apiDeleteRequest(string $requestSubject, array $parameters = [])
    {
        return $this->baseApiRequest()->delete("{$this->baseApiRequestEndpoint}$requestSubject", $parameters)->json();
    }

    /**
     * Base API requests
     *
     * @return Http query request
     */
    protected function baseApiRequest($requestBaseObject = null)
    {
        if ($requestBaseObject) {
            $baseRequest = $requestBaseObject->withToken($this->getServiceConfiguration('whatsapp_access_token'));
        } else {
            $baseRequest = Http::withToken($this->getServiceConfiguration('whatsapp_access_token'));
        }
        return $baseRequest->throw(function ($response, $e) {
            $getContents = $response->getBody()->getContents();
            $getContentsDecoded = json_decode($getContents, true);
            $userMessage = Arr::get($getContentsDecoded, 'error.error_user_title', '') . ' '
                . Arr::get($getContentsDecoded, 'error.message', '') . ' '
                . Arr::get($getContentsDecoded, 'error.error_user_msg', '') . ' '
                . Arr::get($getContentsDecoded, 'error.error_data.details');
            if (!$userMessage) {
                $userMessage = $e->getMessage();
            }
            // __logDebug($userMessage);
            // set notification as your key is token expired
            if (
                Str::contains($e->getMessage(), 'Session has expired') and !getVendorSettings(
                    'whatsapp_access_token_expired',
                    null,
                    null,
                    $this->vendorId ?? getVendorId()
                )
            ) {
                setVendorSettings(
                    'internals',
                    [
                        'whatsapp_access_token_expired' => true
                    ],
                    $this->vendorId ?? getVendorId()
                );
            }
            // stop and response back for error if any
            if (!ignoreFacebookApiError()) {
                // stop and response back for error if any
                abortIf(
                    true,
                    $response->status(),
                    $userMessage
                );
            }
        });
    }

    /**
     * Create Template Request API
     *
     * @param string $whatsAppTemplateName
     * @param string $whatsAppTemplateLanguage
     * @param string $category
     * @param array $components
     * @param int $vendorId
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/#Creating
     * @return json
     */
    public function createTemplate($whatsAppTemplateName, $whatsAppTemplateLanguage, $category, $components, $vendorId = null)
    {
        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        return $this->apiPostRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}/message_templates", [
            'name' => $whatsAppTemplateName,
            'language' => $whatsAppTemplateLanguage,
            'category' => $category,
            'components' => $components,
            'allow_category_change' => false,
        ]);
    }

    /**
     * Update Template Request API
     *
     * @param int $whatsAppTemplateId
     * @param array $components
     * @param int $vendorId
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/#Updating
     * @return json
     */
    public function updateTemplate($whatsAppTemplateId, $whatsAppTemplateName, $components, $vendorId = null)
    {
        if ($vendorId) {
            $this->vendorId = $vendorId;
        }
        return $this->apiPostRequest("$whatsAppTemplateId", [
            'name' => $whatsAppTemplateName,
            'components' => $components,
        ]);
    }

    /**
     * Get Template Analytics
     *
     * @param string $whatsappTemplateId
     * 
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/#Creating
     * @return json
     */
    public function getTemplateAnalytics($inputData, $vendorId = null)
    {
        if ($vendorId) {
            $this->vendorId = $vendorId;
        }

        $timezone = getVendorSettings('timezone');

        $startLocal = Carbon::createFromFormat('Y-m-d', $inputData['analytics_start_date'], $timezone);

        $endLocal = Carbon::createFromFormat('Y-m-d', $inputData['analytics_end_date'], $timezone);

        // Convert to UTC for API
        $startUtc = $startLocal->setTimezone('UTC');
        $endUtc = $endLocal->setTimezone('UTC');

        $parameters = [
            'start' => $startUtc->toDateString(),
            'end' => $endUtc->toDateString(),
            'granularity' => 'daily',
            'template_ids' => [$inputData['template_id']],
            'product_type' => $inputData['analytics_product_type'],
            'waba_timezone' => true
        ];

        if (!__isEmpty($inputData['cursor_after'] ?? null)) {
            $parameters['after'] = $inputData['cursor_after'];
        }
        
        return $this->apiGetRequest("{$this->getServiceConfiguration('whatsapp_business_account_id')}/template_analytics", $parameters);
    }

    /**
     * Block Contact
     *
     * @param int $whatsAppTemplateId
     * @param array $components
     * @param int $vendorId
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/#Updating
     * @return json
     */
    public function blockContact($phoneNumber)
    {
        return $this->apiPostRequest("{$this->getServiceConfiguration('current_phone_number_id')}/block_users", [
            "block_users" => [
                [
                    "user" => $phoneNumber
                ]
            ]
        ]);
    }

    /**
     * Unblock Contact
     *
     * @param int $whatsAppTemplateId
     * @param array $components
     * @param int $vendorId
     * @link https://developers.facebook.com/docs/graph-api/reference/whats-app-business-hsm/#Updating
     * @return json
     */
    public function unBlockContact($phoneNumber)
    {
        return $this->apiDeleteRequest("{$this->getServiceConfiguration('current_phone_number_id')}/block_users", [
            "messaging_product" => "whatsapp",
            "block_users" => [
                [
                    "user" => $phoneNumber
                ]
            ]
        ]);
    }
}
