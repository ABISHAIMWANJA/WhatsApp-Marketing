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
 * WhatsAppMessageLogRepository.php - Repository file
 *
 * This file is part of the WhatsAppService component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\WhatsAppService\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppMessageLogModel;
use App\Yantrana\Components\WhatsAppService\Interfaces\WhatsAppMessageLogRepositoryInterface;

class WhatsAppMessageLogRepository extends BaseRepository implements WhatsAppMessageLogRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = WhatsAppMessageLogModel::class;

    public function updateOrCreateWhatsAppMessageFromWebhook(
        $phoneNumberId,
        $contactId,
        $vendorId,
        $messageRecipientId,
        $messageWamid,
        $messageStatus,
        $messageEntry,
        $message = '',
        $timestamp = null,
        ?array $mediaData = null,
        ?bool $preventCreation = false,
        ?array $options = []
    ) {
        if (!empty($options)) {
            $options = array_merge([
                'bot_reply' => false,
                'interaction_message_data' => null,
                'full_name' => null,
            ], $options);
        }

        $findTheExistingLogEntry = [
            // 'wab_phone_number_id' => $phoneNumberId,
            // 'contact_wa_id' => (string) $messageRecipientId,
            // 'wamid' => $messageWamid,
            'vendors__id' => $vendorId,
        ];
        if ($options['message_log_id'] ?? null) {
            $findTheExistingLogEntry['_id'] = $options['message_log_id'];
        } else {
            $findTheExistingLogEntry['wamid'] = $messageWamid;
        }

        $messageLogModel = $this->fetchIt($findTheExistingLogEntry);

        // may the message deleted from db but webhook is received for delivery or read receipt
        // in such a case no need to record the action
        if (__isEmpty($messageLogModel) and $preventCreation) {
            return false;
        }
        $dataToUpdate = [
            // 'status' => $messageStatus,
            'is_incoming_message' => (data_get($options, 'is_incoming_message') == true) ? 1 : 0,
        ];
        if (!__isEmpty($messageLogModel)) {
            if (($messageLogModel->status != 'read') and ($messageLogModel->status != 'played')) {
                $dataToUpdate['status'] = $messageStatus;
            }
        } else {
            $dataToUpdate['status'] = $messageStatus;
        }
        if ($timestamp and ($messageStatus == 'delivered')) {
            $dataToUpdate['messaged_at'] = Carbon::createFromTimestamp($timestamp);
        }
        if ($message || $mediaData) {
            $dataToUpdate['message'] = $message;
            $dataToUpdate['wab_phone_number_id'] = $phoneNumberId;
            $dataToUpdate['__data'] = [
                'options' => Arr::only($options, [
                    'bot_reply',
                    'ai_bot_reply',
                ]),
                'interaction_message_data' => $options['interaction_message_data'] ?? null,
                'initial_response' => [
                    $messageStatus => $messageEntry,
                ],
            ];
            if (! empty($mediaData)) {
                $dataToUpdate['__data']['media_values'] = $mediaData;
            }
        }
        if ($options['full_name']) {
            $dataToUpdate['full_name'] = $options['full_name'];
        }
        if (__isEmpty($messageLogModel)) {
            $dataToUpdate['contacts__id'] = $contactId;
            $dataToUpdate['contact_wa_id'] = (string) $messageRecipientId;
            return $this->storeIt(arrayExtend($findTheExistingLogEntry, $dataToUpdate));
        }

        if ($this->updateIt($findTheExistingLogEntry, arrayExtend($dataToUpdate, [
            '__data' => [
                'options' => $options,
                'webhook_responses' => [
                    $messageStatus => $messageEntry,
                ],
            ],
        ]))) {
            return $findTheExistingLogEntry;
        }

        return false;
    }

    public function storeIncomingMessage(
        $phoneNumberId,
        $contactId,
        $vendorId,
        $messageRecipientId,
        $messageWamid,
        $messageEntry,
        $message,
        $timestamp,
        ?array $mediaData = null,
        ?string $repliedToMessage = null,
        ?bool $isForwarded = null,
        ?array $otherMessageData = null,
        // useful when message get sent from WhatsApp Business App
        ?bool $isActualIncomingMessage = true
    ) {
        $additionalData = [
            'webhook_responses' => [
                'incoming' => $messageEntry,
            ],
        ];
        if (! empty($mediaData)) {
            $additionalData['media_values'] = $mediaData;
        }
        if (! empty($otherMessageData)) {
            $additionalData['other_message_data'] = $otherMessageData;
        }

        return $this->storeIt([
            'wab_phone_number_id' => $phoneNumberId,
            'contact_wa_id' => $messageRecipientId,
            'wamid' => $messageWamid,
            'status' => 'received',
            'message' => $message,
            'is_incoming_message' => $isActualIncomingMessage ? 1 : 0,
            'vendors__id' => $vendorId,
            'contacts__id' => $contactId,
            '__data' => $additionalData,
            'messaged_at' => is_numeric($timestamp) ? Carbon::createFromTimestamp($timestamp) : $timestamp,
            'replied_to_whatsapp_message_logs__uid' => $repliedToMessage,
            'is_forwarded' => $isForwarded
        ]);
    }

    /**
     * Mark unread messages as read
     *
     * @param onject $contact
     * @param integer $vendorId
     * @return mixed
     */
    public function markAsRead($contact, $vendorId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        return $this->primaryModel::where([
            'contacts__id' => $contact->_id,
            'vendors__id' => $vendorId,
            // 'wab_phone_number_id' => (string) getVendorSettings('current_phone_number_id', null, null, $vendorId),
            'is_incoming_message' => 1,
            'status' => 'received',
        ])->update([
            'status' => 'read',
        ]);
    }

    /**
     * Get the all messages of the particular contact
     *
     * @param integer $contactId
     * @return object
     */
    public function allMessagesOfContact(int $contactId)
    {
        return $this->primaryModel::where([
            'contacts__id' => $contactId,
        ])->latest()->orderBy('messaged_at', 'desc')->simplePaginate(16);
    }
    /**
     * Get the recent messages of the particular contact
     *
     * @param integer $contactId
     * @return object
     */
    public function recentMessagesOfContact(int $contactId)
    {
        return $this->allMessagesOfContact($contactId);
    }

    /**
     * Get unread count for vendor
     *
     * @param int $vendorId
     * @param int $phoneNumberId
     * @return int
     */
    public function getUnreadCount($vendorId = null, $phoneNumberId = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        $query = WhatsAppMessageLogModel::leftJoin('contacts', 'whatsapp_message_logs.contacts__id', '=', 'contacts._id')->where([
            'whatsapp_message_logs.vendors__id' => $vendorId,
            'is_incoming_message' => 1,
            [
                'contacts__id',
                '!=',
                null
            ],
            'whatsapp_message_logs.status' => 'received',
        ]);
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('contacts.wa_id', getDemoNumbersForTest());
        }
        return $query->count();
    }
    /**
     * Get unread count for vendor
     *
     * @param int $vendorId
     * @param int $phoneNumberId
     * @return int
     */
    public function getMyAssignedUnreadMessagesCount($vendorId = null, $phoneNumberId = null, $userId = 'self')
    {
        $vendorId = $vendorId ?: getVendorId();
        $userIdValue = ($userId === 'self')
            ? getUserID()
            : (is_int($userId) ? $userId : null);

        $query = WhatsAppMessageLogModel::query()
            ->leftJoin('contacts', 'whatsapp_message_logs.contacts__id', '=', 'contacts._id')
            ->where('whatsapp_message_logs.vendors__id', $vendorId)
            ->where('whatsapp_message_logs.is_incoming_message', 1)
            ->whereNotNull('whatsapp_message_logs.contacts__id')
            ->where('whatsapp_message_logs.status', 'received')
            ->where('contacts.assigned_users__id', $userIdValue);

        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('contacts.wa_id', getDemoNumbersForTest());
        }
        return $query->count();
    }

    /**
     * Clear chat history all the messages excluding campaign messages
     *
     * @param int $contactId
     * @param int $vendorId
     * @return bool|mixed
     */
    function clearChatHistory($contactId, $vendorId)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId,
            'contacts__id' => $contactId,
        ])->whereNull('campaigns__id')->delete();
    }

    /**
     * fetch all message log 
     *
     * 
     * @param int $vendorId
     * @return bool|mixed
     */
    public function fetchMessageLogDataTableSource($type, $startDate, $endDate)
    {
        $vendorId = getVendorId();
        // basic configurations for dataTables data
        $dataTableConfig = [
            'fieldAlias' => [
                'recepient' => 'wab_phone_number_id',
                'from' => 'contact_wa_id',
            ],
            // searchable columns
            'searchable' => [
                'messaged_at',
                'status',
                'is_incoming_message',
                'contact_wa_id',
                'wab_phone_number_id',
            ],
        ];
        $query = $this->primaryModel::where([
            'vendors__id' => $vendorId,
            'is_system_message' => null
        ]);
        if (isset($type) and ($type != 'all')) { // Use isset() to allow "0"
            $query->where('is_incoming_message', $type);
        }

        try {
            if ($startDate) {
                $msgStartDate = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
                $query->where('messaged_at', '>=', $msgStartDate);
            }

            if ($endDate) {
                $msgEndDate = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
                $query->where('messaged_at', '<=', $msgEndDate);
            }
        } catch (InvalidFormatException $e) {
            // echo 'Failed to parse time string';
        }


        return $query->dataTables($dataTableConfig)->toArray();
    }
}
