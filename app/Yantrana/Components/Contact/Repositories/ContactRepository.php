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
 * ContactRepository.php - Repository file
 *
 * This file is part of the Contact component.
 *-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Contact\Repositories;

use Illuminate\Support\Facades\DB;
use libphonenumber\PhoneNumberUtil;
use App\Yantrana\Base\BaseRepository;
use libphonenumber\NumberParseException;
use Illuminate\Database\Query\JoinClause;
use App\Yantrana\Support\Country\Models\Country;
use App\Yantrana\Components\Contact\Models\ContactModel;
use App\Yantrana\Components\Contact\Interfaces\ContactRepositoryInterface;

class ContactRepository extends BaseRepository implements ContactRepositoryInterface
{
    /**
     * primary model instance
     *
     * @var object
     */
    protected $primaryModel = ContactModel::class;

    /**
     * Fetch contact datatable source
     *
     * @return mixed
     *---------------------------------------------------------------- */
    public function fetchContactDataTableSource($groupContactIds = null, $contactGroupUid = null, $returnDTResponse = true)
    {
        // basic configurations for dataTables data
        $dataTableConfig = [
            'fieldAlias' => [
                'phone_number' => 'wa_id'
            ],
            // searchable columns
            'searchable' => [
                'first_name',
                'last_name',
                'countries__id',
                'wa_id',
                'email',
            ],
        ];

        // get Model result for dataTables
        $query = $this->primaryModel::with([
            'groups' => function ($query) {
                $query->distinct('_id');
            },
            'lastIncomingMessage'
        ])->where('vendors__id', getVendorId());

        if ($contactGroupUid) {
            $query->whereIn('_id', $groupContactIds);
        }
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }

        if (__isEmpty($groupContactIds) and __isEmpty($contactGroupUid)) {
            // Get contact filter data and if exists then apply filter
            $filterData = getVendorSettings('contact_advance_filter_data', getUserUID());
            // Check if filter data exists
            if ($filterData) {
                $query->where(function ($filterQuery) use ($filterData) {
                    // Check if first name exists
                    if (!__isEmpty($filterData['first_name'] ?? null)) {
                        $filterQuery->where('first_name', 'like', "%{$filterData['first_name']}%");
                    }
                    // Check if last name exists
                    if (!__isEmpty($filterData['last_name'] ?? null)) {
                        $filterQuery->where('last_name', 'like', "%{$filterData['last_name']}%");
                    }
                    // Check if country ids exists
                    if (!__isEmpty(data_get($filterData, 'countries_id'))) {
                        $filterQuery->whereIn('countries__id', $filterData['countries_id']);
                    }
                    // Check if phone number exists
                    if (!__isEmpty($filterData['wa_id'] ?? null)) {
                        $filterQuery->where('wa_id', 'like', "%{$filterData['wa_id']}%");
                    }
                    // Check if languages exists
                    if (!__isEmpty(data_get($filterData, 'language_codes'))) {
                        $languages = include app_path('Yantrana/Support/languages.php');
                        $languageCodes = [];
                        foreach ($filterData['language_codes'] as $langCode) {
                            $languageCodes[] = data_get($languages, $langCode . '.code');
                        }
                        $filterQuery->whereIn('language_code', $languageCodes);
                    }
                    // Check if email exists
                    if (!__isEmpty($filterData['email'] ?? null)) {
                        $filterQuery->where('email', 'like', "%{$filterData['email']}%");
                    }
                    // Check if assigned user ids exists
                    if (!__isEmpty(data_get($filterData, 'assigned_users_ids'))) {
                        // Check if unassigned option selected
                        if (in_array('null', $filterData['assigned_users_ids'])) {
                            $filterQuery->whereNull('assigned_users__id');
                        }
                        // Remove null value from array
                        $assignedUserIds = array_filter($filterData['assigned_users_ids'], function ($item) {
                            return $item != 'null';
                        });
                        // Only search by assigned user id
                        if (!__isEmpty($assignedUserIds)) {
                            $filterQuery->orWhereIn('assigned_users__id', $assignedUserIds);
                        }
                    }
                    // Check if start date and end date exists
                    if (!__isEmpty($filterData['msg_start_date'] ?? null) and !__isEmpty($filterData['msg_end_date'] ?? null)) {
                        $filterQuery->whereBetween('created_at', [$filterData['msg_start_date'], $filterData['msg_end_date']]);
                    }
                    // Check if whatsapp opt out exists
                    if (data_get($filterData, 'whatsapp_opt_out') == 'on') {
                        $filterQuery->where('whatsapp_opt_out', 1);
                    }
                    // Check if enable ai bot exists
                    if (data_get($filterData, 'enable_ai_bot') == 'on') {
                        $filterQuery->where(function ($subQuery) {
                            $subQuery->whereNull('disable_ai_bot')
                                ->orWhere('disable_ai_bot', 0);
                        });
                    }
                    // Check if enable reply bot exists
                    if (data_get($filterData, 'enable_reply_bot') == 'on') {
                        $filterQuery->where(function ($subQuery) {
                            $subQuery->whereNull('disable_reply_bot')
                                ->orWhere('disable_reply_bot', 0);
                        });
                    }
                });
                // Check group ids exists
                if (!__isEmpty(data_get($filterData, 'group_ids'))) {
                    $query->whereHas('groups', function ($q) use ($filterData) {
                        $q->whereIn('contact_groups__id', $filterData['group_ids']);
                    });
                }
                // Check filter by labels
                if (!__isEmpty(data_get($filterData, 'contact_labels'))) {
                    $query->whereHas('labels', function ($q) use ($filterData) {
                        $q->whereIn('labels__id', $filterData['contact_labels']);
                    });
                }
                // Check if contacts whose WhatsApp 24-hour service window active or inactive
                if (
                    !__isEmpty(data_get($filterData, 'whatsapp_service_window'))
                    and data_get($filterData, 'whatsapp_service_window') != 'all'
                ) {
                    $query->leftJoin(
                        DB::raw('(
                            SELECT contacts__id, MAX(messaged_at) as last_messaged_at
                            FROM whatsapp_message_logs
                            WHERE is_incoming_message = 1
                            GROUP BY contacts__id
                        ) as last_msg'),
                        'contacts._id',
                        '=',
                        'last_msg.contacts__id'
                    )
                        ->when($filterData['whatsapp_service_window'] === 'active', function ($q) {
                            $q->whereRaw("last_msg.last_messaged_at >= UTC_TIMESTAMP() - INTERVAL 24 HOUR");
                        })
                        ->when($filterData['whatsapp_service_window'] === 'inactive', function ($q) {
                            $q->whereRaw("last_msg.last_messaged_at < UTC_TIMESTAMP() - INTERVAL 24 HOUR");
                        });
                }
                // Check if filter by custom fields
                if (!__isEmpty(data_get($filterData, 'custom_input_fields'))) {
                    foreach ($filterData['custom_input_fields'] as $customInputFields) {
                        $query->whereHas('valueWithField', function ($valueFieldQuery) use ($customInputFields) {
                            $valueFieldQuery->where('field_value', 'like', "%{$customInputFields}%");
                        });
                    }
                }
            }
        }
        // __dd($query->dataTables($dataTableConfig)->toArray());
        if ($returnDTResponse) {
            return $query->dataTables($dataTableConfig)->toArray();
        } else {
            return $query->get();
        }
    }

    /**
     * Delete $contact record and return response
     *
     * @param  object  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function deleteContact($contact)
    {
        // Check if $contact deleted
        if ($contact->deleteIt()) {
            // if deleted
            return true;
        }

        // if failed to delete
        return false;
    }

    /**
     * Store new contact record and return response
     *
     * @param  array  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function storeContact($inputData, $vendorId = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        // prepare data to store
        $keyValues = [
            'first_name',
            'last_name',
            'countries__id' => $inputData['country'] ?? null,
            'email',
            'language_code',
            'whatsapp_opt_out' => (isset($inputData['whatsapp_opt_out']) and $inputData['whatsapp_opt_out']) ? 1 : null,
            'wa_id' => $inputData['phone_number'],
            'vendors__id' => $vendorId,
        ];
        if (isset($inputData['enable_ai_bot'])) {
            $keyValues['disable_ai_bot'] = ($inputData['enable_ai_bot']) ? 0 : 1;
        } else {
            $keyValues['disable_ai_bot'] = getVendorSettings('default_enable_flowise_ai_bot_for_users', null, null, $vendorId) ? 0 : 1;
        }

        if (isset($inputData['enable_reply_bot'])) {
            $keyValues['disable_reply_bot'] = ($inputData['enable_reply_bot']) ? 0 : 1;
        }

        return $this->storeIt($inputData, $keyValues);
    }
    /**
     * Get vendor contact based on _id,_uid or phone_number which is wa_id
     *
     * @param string|integer|null $contactIdOrUid
     * @param string|null $vendorId
     * @return Eloquent object
     */
    public function getVendorContact(string|int|null $contactIdOrUid, ?string $vendorId = null)
    {
        $findBy = [
            'vendors__id' => $vendorId ? $vendorId : getVendorId(),
        ];

        if (request()->phone_number and isExternalApiRequest()) {
            $findBy['wa_id'] = (string) request()->phone_number;
        } else {
            if (is_numeric($contactIdOrUid)) {
                $findBy['_id'] = $contactIdOrUid;
            } else {
                $findBy['_uid'] = $contactIdOrUid;
            }
        }

        return $this->with([
            'lastMessage',
            'customFieldValues'
        ])->fetchIt($findBy);
    }

    /**
     * Get contact by phone number and vendor id
     * If the contact does not found we will check db if it is stored without country code and will update the same.
     *
     * @param int $waId
     * @param int|null $vendorId
     * @return Eloquent
     */
    public function getVendorContactByWaId($waId, $vendorId = null)
    {
        $contactWhere = [
            'vendors__id' => $vendorId ? $vendorId : getVendorId(),
        ];
        if(is_numeric($waId)) {
            $contactWhere['wa_id'] = (string) $waId;
        } else {
            $contactWhere['_uid'] = $waId;
        }
        $contact = $this->fetchIt($contactWhere);

        if (__isEmpty($contact)) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $localNumber = null;
            $countryCode = null;
            try {
                // Remove non-numeric characters
                $phoneNumber = preg_replace('/[^0-9]/', '', $waId);
                // Parse the phone number assuming it's from a default region (e.g., India)
                $numberProto = $phoneUtil->parse('+' . $phoneNumber, null);
                // Get the country code and national (local) number
                $countryCode = $numberProto->getCountryCode();
                $localNumber = $numberProto->getNationalNumber();
            } catch (NumberParseException $e) {
                $localNumber = null; // Invalid phone number
            }

            if ($localNumber) {
                $contact = $this->fetchIt([
                    'vendors__id' => $vendorId ? $vendorId : getVendorId(),
                    'wa_id' => (string) $localNumber,
                ]);
                if (!__isEmpty($contact)) {
                    $dataToUpdate = [
                        'wa_id' => $waId,
                    ];
                    if (!$contact->countries__id and $countryCode) {
                        $dataToUpdate['countries__id'] = findRequestedCountryId($countryCode);
                    }
                    $this->updateIt($contact, $dataToUpdate);
                    $contact = $contact->fresh();
                }
            }
        }
        return $contact;
    }

    /**
     * Get the contact with unread message details using contact uid and vendor uid
     *
     * @param string|null $contactUid
     * @param int|null $vendorId
     * @param string|null $assigned
     * @return Eloquent
     */
    public function getVendorContactWithUnreadDetails($contactUid = null, $vendorId = null, $assigned = null)
    {
        $whereClause = [
            'vendors__id' => $vendorId ?: getVendorId(),
        ];
        if ($contactUid) {
            $whereClause['_uid'] = $contactUid;
        }
        $query = $this->primaryModel::where($whereClause)->with([
            'lastMessage',
            'lastUnreadMessage',
            'lastIncomingMessage',
            'labels'
        ])->withCount('unreadMessages');

        if ($assigned == 'to-me') {
            $query->where('assigned_users__id', getUserID());
        } elseif ($assigned == 'unassigned') {
            $query->whereNull('assigned_users__id');
        } elseif (is_numeric($assigned)) {
            $query->where('assigned_users__id', $assigned);
        }

        if (!$contactUid) {
            $query->has('lastIncomingMessage');
        }

        $contact = $query->first();
        // Mask email and wa_id in contact and related last message, last incoming message and last unread message data if exists
        if(__isEmpty($contact)) {
            return $contact;
        }
        $contact->email = maskString($contact->email, 'email');

        // Check if incoming message data exists
        if (!__isEmpty($contact->lastIncomingMessage)) {
            $contact->lastIncomingMessage->contact_wa_id = maskString($contact->lastIncomingMessage->contact_wa_id, 'phone');
        }

        // Check if last message data exists
        if (!__isEmpty($contact->lastMessage)) {
            $contact->lastMessage->contact_wa_id = maskString($contact->lastMessage->contact_wa_id, 'phone');
        }

        // Check if last unread message data exists
        if (!__isEmpty($contact->lastUnreadMessage)) {
            $contact->lastUnreadMessage->contact_wa_id = maskString($contact->lastUnreadMessage->contact_wa_id, 'phone');
        }

        return $contact;
    }

    /**
     * Get contacts by vendor id
     *
     * @param string|null $vendorId
     * @return Eloquent
     */
    public function getVendorContactsWithUnreadDetails($vendorId = null, $assigned = null)
    {
        $vendorId = $vendorId ?: getVendorId();
        $userId = getUserID();

        $searchQuery = trim(strip_tags(request()->search));
        $unreadOnly  = filter_var(request()->unread_only, FILTER_VALIDATE_BOOLEAN);
        $assigned    = trim(strip_tags($assigned ?: request()->assigned));
        $selectedLabel = request()->selected_labels;
        $requestContactUid = trim(strip_tags(request()->request_contact));

        $isRestrictedVendorUser = !hasVendorAccess()
            ? hasVendorAccess('assigned_chats_only')
            : false;

        // -----------------------------------------
        // BASE QUERY
        // -----------------------------------------
        $query = $this->primaryModel::query()
            ->where('contacts.vendors__id', $vendorId);

        // -----------------------------------------
        // ASSIGNMENT FILTER
        // -----------------------------------------
        if ($assigned === 'to-me') {
            $query->where('assigned_users__id', $userId);
        } elseif ($assigned === 'unassigned') {
            $query->whereNull('assigned_users__id');
        } elseif (is_numeric($assigned)) {
            $query->where('assigned_users__id', $assigned);
        }

        if ($isRestrictedVendorUser && $assigned !== 'unassigned') {
            $query->where('assigned_users__id', $userId);
        }

        // -----------------------------------------
        // UID FILTER
        // -----------------------------------------
        if (!empty($requestContactUid)) {
            $query->where('_uid', $requestContactUid);
        }

        // -----------------------------------------
        // LATEST MESSAGE JOIN
        // -----------------------------------------
        $query->joinSub(
            DB::table('whatsapp_message_logs')
                ->selectRaw('contacts__id, MAX(messaged_at) AS latest_message')
                ->groupBy('contacts__id'),
            'latest_messages',
            'contacts._id',
            '=',
            'latest_messages.contacts__id'
        )->orderBy('latest_messages.latest_message', 'desc');

        // -----------------------------------------
        // UNREAD COUNT JOIN (LEFT)
        // -----------------------------------------
        $query->leftJoinSub(
            DB::table('whatsapp_message_logs')
                ->selectRaw('contacts__id, COUNT(*) AS unread_messages_count')
                ->where('status', 'received')
                ->where('is_incoming_message', 1)
                ->groupBy('contacts__id'),
            'unread_counts',
            'contacts._id',
            '=',
            'unread_counts.contacts__id'
        );

        // -----------------------------------------
        // LABEL FILTER
        // -----------------------------------------
        if (!empty($selectedLabel)) {

            $labelJoin = DB::table('contact_labels')
                ->select('contact_labels.contacts__id', 'contact_labels.labels__id')
                ->join('labels', 'contact_labels.labels__id', '=', 'labels._id')
                ->where('labels._id', $selectedLabel);

            $query->leftJoinSub($labelJoin, 'label_map', function ($join) {
                $join->on('contacts._id', '=', 'label_map.contacts__id');
            })->where('label_map.labels__id', $selectedLabel);
        }

        // -----------------------------------------
        // UNREAD FILTER
        // -----------------------------------------
        if ($unreadOnly) {
            $query->whereNotNull('unread_counts.unread_messages_count');
        }

        // -----------------------------------------
        // SEARCH FILTER
        // -----------------------------------------
        if (!empty($searchQuery)) {
            $like = '%' . $searchQuery . '%';

            $query->where(function ($q) use ($like) {
                $q->where(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', $like)
                    ->orWhere('wa_id', 'LIKE', $like);
            });
        }

        // -----------------------------------------
        // DEMO ACCOUNT FILTER
        // -----------------------------------------
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }

        // -----------------------------------------
        // FINAL RETURN
        // -----------------------------------------
        return $query->with(['lastMessage', 'labels'])
            ->has('lastIncomingMessage')
            ->simplePaginate(12);
    }

    /**
     * Delete the selected contacts based on uids provided
     * for the logged in vendor
     *
     * @param array $contactUids
     * @param integer|null $vendorId
     * @return mixed
     */
    public function deleteSelectedContacts(array $contactUids, int|null $vendorId = null)
    {
        return $this->primaryModel::where([
            'vendors__id' => $vendorId ?: getVendorId()
        ])->whereIn('_uid', $contactUids)->delete();
    }

    /**
     * Get all the contacts for vendor lazily with croup and custom field values
     *
     * @param int $vendorId
     * @param function $callBack
     *
     * @return LazyCollection
     */
    public function getAllContactsForTheVendorLazily($vendorId, $callback)
    {
        $query = $this->primaryModel::with(['groups', 'customFieldValues'])->where([
            'vendors__id' => $vendorId
        ]);
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }
        return $query->lazy()->each($callback);
    }

    /**
     * Count for campaigns
     *
     * @param array $whereClauses
     * @param array $groupContactIds
     * @param array $labelIds
     * @return Collection
     */
    public function countContactsForCampaign($whereClauses, $groupContactIds, $labelIds)
    {
        $query = $this->primaryModel::where($whereClauses);
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }

        if (!empty($labelIds)) {
            $query->join('contact_labels', 'contacts._id', '=', 'contact_labels.contacts__id')
                ->join('labels', 'contact_labels.labels__id', '=', 'labels._id')
                ->whereIn('labels._id', $labelIds);
        }

        if (!empty($groupContactIds)) {
            $query->whereIn('contacts._id', $groupContactIds);
        }

        return $query->distinct()->count('contacts._id');
    }

    /**
     * Get Contacts for campaign in chunks
     *
     * @param array $whereClauses
     * @param array $groupContactIds
     * @param array $labelIds
     * @param function $callback
     * @return Object
     */
    public function getContactsForCampaignInChunks($whereClauses, $groupContactIds, $labelIds, $callback)
    {
        $query = $this->primaryModel::where($whereClauses);
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }
        if (!empty($labelIds)) {
            $query->select('contacts.*');
            $query->join('contact_labels', 'contacts._id', '=', 'contact_labels.contacts__id');
            $query->join('labels', 'contact_labels.labels__id', '=', 'labels._id');
            $query->whereIn('labels._id', $labelIds); // Match any of the given label IDs
            $query->distinct();
        }
        if (empty($groupContactIds)) {
            return $query->chunk(500, $callback);
        }
        return $query->whereIn('contacts._id', $groupContactIds)->chunk(500, $callback);
    }

    /**
     * Get total contacts count for the vendor
     *
     * @param int $vendorId
     * @return int
     */
    public function totalContactsCountForVendor($vendorId)
    {
        $query = $this->primaryModel::where([
            'vendors__id' => $vendorId
        ]);
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }
        // demo account restriction
        if (isThisDemoVendorAccountAccess()) {
            $query->whereIn('wa_id', getDemoNumbersForTest());
        }

        return $query->count();
    }

    /**
     * Fetch contact with assigned user
     *
     * @param int $contactIdOrUid
     * @return int
     */
    public function fetchContactWithAssignUser($contactIdOrUid)
    {
        return $this->primaryModel::where([
            '_uid' => $contactIdOrUid
        ])->with('assignedUser')->first();
    }

    public function assignTeamMemberToContacts($contactUIDs, $inputData)
    {
        return $this->primaryModel::whereIn('_uid', $contactUIDs)
            ->update([
                'assigned_users__id' => $inputData['assign_user_id']
            ]);
    }

    public function deleteAllContact($vendorId, $testContactUid)
    {
        return ContactModel::where('vendors__id', $vendorId)
            ->where('_uid', '!=', $testContactUid)
            ->chunkById(500, function ($contacts) use ($vendorId) {
                $ids = $contacts->pluck('_id');
                if(!empty($ids)) {
                   $deletedContacts = ContactModel::where('vendors__id', $vendorId)->whereIn('_id', $ids)->delete();
                }
            });
    }

    public function fetchContactListPaginatedData()
    {
        $paginateCount = request()->get('page_size') ?? 100;
        $searchTerm = request()->get('search_term');

        return $this->primaryModel::where('vendors__id', getVendorId())->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'like', "%{$searchTerm}%")
                ->orWhere('last_name', 'like', "%{$searchTerm}%")
                ->orWhere('wa_id', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%");
        })
            ->with([
                'groups' => function ($groupQuery) {
                    $groupQuery->select('contact_groups._id', 'contact_groups._uid', 'contact_groups.status', 'contact_groups.title', 'contact_groups.description', 'contact_groups.created_at');
                },
                'labels' => function ($labelQuery) {
                    $labelQuery->select('labels._id', 'labels._uid', 'labels.status', 'labels.title', 'labels.created_at');
                },
            ])
            ->withoutColumn(['__data'])
            ->paginate($paginateCount);
    }

    public function fetchContactByPhoneNumberOrEmail($phoneNumberOrEmail)
    {
        return $this->primaryModel::where('vendors__id', getVendorId())->where(function ($q) use ($phoneNumberOrEmail) {
            $q->where('wa_id', $phoneNumberOrEmail)
                ->orWhere('email', $phoneNumberOrEmail);
        })
            ->with([
                'groups' => function ($groupQuery) {
                    $groupQuery->select('contact_groups._id', 'contact_groups._uid', 'contact_groups.status', 'contact_groups.title', 'contact_groups.description', 'contact_groups.created_at');
                },
                'labels' => function ($labelQuery) {
                    $labelQuery->select('labels._id', 'labels._uid', 'labels.status', 'labels.title', 'labels.created_at');
                },
            ])
            ->withoutColumn(['__data'])
            ->first();
    }

    public function getContactViaCondition($vendorId, $findContactData)
    {
        return $this->primaryModel::where('vendors__id', $vendorId)->where(function ($q) use ($findContactData) {
            foreach ($findContactData as $contactDataKey => $contactData) {
                $q->orWhere($contactDataKey, $contactData);
            }
        })
            ->first();
    }
}
