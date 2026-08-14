@extends('layouts.app', ['title' => __tr('WhatsApp Chat')])
@section('content')
@include('users.partials.header', [
// 'title' => __tr('WhatsApp Chat'),
'description' => '',
// 'class' => 'col-lg-7'
])
@push('head')
{!! __yesset('dist/css/whatsapp-chat.css', true) !!}
@endpush
<div x-data="initialMessageData"> 
{{-- @if ($contact) --}}
<div class="container-fluid lw-chat-main-container" x-data="{myAssignedUnreadMessagesCount:null,myUnassignedUnreadMessagesCount:null,showUnreadContactsOnly:false,usersUnreadMessagesCounts:{}}">
    <div class="">
        <div class="card lw-whatsapp-chat-block-container">
            @if (!getVendorSettings('current_phone_number_number'))
            <div class="card-header">
            <div class="text-danger">
                {{  __tr('Phone number does not configured yet.') }}
            </div>
            </div>
            @endif
            <div id="lwWhatsAppChatWindow"
                class="card-body lw-whatsapp-chat-window p-sm-4" x-init="$watch('messagePaginatePage', function(value) {window.messagePaginatePage = value;});$watch('contactsPaginatePage', function(value) {window.contactsPaginatePage = value; });" :data-paginate-page="messagePaginatePage" :data-unread-only="showUnreadContactsOnly" :data-search-value="search" :data-contact-uid="contact?._uid">
                <div class="row" x-cloak x-data="{isContactListOpened:true,isContactCrmBlockOpened:false}">
                    <div class="col-sm-12 col-md-3 col-lg-3 col-xl-3 mb-4 lw-contact-list-block" x-show="isContactListOpened">
                        {{-- <h1>{{  __tr('WhatsApp Chat') }}</h1> --}}
                        {{-- <hr class="my-2"> --}}
                        <h2 class="lw-contacts-header"> <span class="btn btn-light btn-sm float-right d-md-none" @click.prevent="isContactListOpened = false"><i class="fa fa-arrow-left"></i> {{  __tr('Back to Chat') }}</span> </h2>
                        <nav>
                            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            @if (isVendorAdmin(getVendorId()) or !hasVendorAccess('assigned_chats_only'))
                              <a class="nav-link {{ ($assigned ?? null) ? '' : 'active' }}" href="{{ route('vendor.chat_message.contact.view') }}" id="lw-all-contacts-tab"  data-target="#lwAllContactsTab" type="button" role="tab" aria-controls="lwAllContactsTab" aria-selected="true">{{  __tr('All') }} <span x-cloak x-show="unreadMessagesCount" class="badge bg-yellow text-dark badge-white rounded-pill ml-1" x-text="unreadMessagesCount"></span></a>
                            @endif
                              <a href="{{ route('vendor.chat_message.contact.view', [
                                'assigned' => 'to-me',
                              ]) }}" class="nav-link {{ (($assigned ?? null) == 'to-me') ? 'active' : '' }}" id="lw-to-me-tab"  data-target="#lwAssignedToMeTab" type="button" role="tab" aria-controls="lwAssignedToMeTab" aria-selected="false">{{  __tr('Mine') }} <span x-cloak x-show="myAssignedUnreadMessagesCount" class="badge bg-yellow text-dark badge-white rounded-pill ml-1" x-text="myAssignedUnreadMessagesCount"></span></a>
                              @if (isVendorAdmin(getVendorId()) or !hasVendorAccess('assigned_chats_only'))
                              <a href="{{ route('vendor.chat_message.contact.view', [
                                'assigned' => 'unassigned',
                              ]) }}" class="nav-link {{ ($assigned ?? null) == 'unassigned' ? 'active' : '' }}" id="lw-unassigned-tab"  data-target="#lwUnassignedTab" type="button" role="tab" aria-controls="lwUnassignedTab" aria-selected="false">{{  __tr('Unassigned') }} <span x-cloak x-show="myUnassignedUnreadMessagesCount" class="badge bg-yellow text-dark badge-white rounded-pill ml-1" x-text="myUnassignedUnreadMessagesCount"></span></a>
                             @if(!__isEmpty($vendorMessagingUsers) and ($vendorMessagingUsers->count() > 1))
                             @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                @if($vendorMessagingUser->_uid != getUserUID())
                                @if ((($assigned ?? null) == $vendorMessagingUser->_id) or ($vendorMessagingUsers->count() == 2))
                                    <a href="{{ route('vendor.chat_message.contact.view', [
                                'assigned' => $vendorMessagingUser->_id,
                              ]) }}" class="nav-link {{ ($assigned ?? null) == $vendorMessagingUser->_id ? 'active' : '' }}"> {{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }} <span x-cloak x-show="usersUnreadMessagesCounts['{{ $vendorMessagingUser->_uid }}']" class="badge bg-yellow text-dark badge-white rounded-pill ml-1" x-text="usersUnreadMessagesCounts['{{ $vendorMessagingUser->_uid }}']"></span></a>
                              @break
                                @endif
                              @endif
                                @endforeach
                                @if ($vendorMessagingUsers->count() > 2)
                              <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false">{{  __tr('Others') }}</a>
                                <div class="dropdown-menu dropdown-menu-right">
                                  @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                @if($vendorMessagingUser->_uid != getUserUID())
                                 @if (($assigned ?? null) == $vendorMessagingUser->_id)
                                    @continue
                                 @endif
                                <a href="{{ route('vendor.chat_message.contact.view', [
                                'assigned' => $vendorMessagingUser->_id,
                              ]) }}" class="dropdown-item {{ ($assigned ?? null) == $vendorMessagingUser->_id ? 'active' : '' }}" id="lw-unassigned-tab"  data-target="#lwUnassignedTab" type="button" role="tab" aria-controls="lwUnassignedTab" aria-selected="false"> {{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }} <span x-cloak x-show="usersUnreadMessagesCounts['{{ $vendorMessagingUser->_uid }}']" class="badge bg-yellow text-dark badge-white rounded-pill ml-1" x-text="usersUnreadMessagesCounts['{{ $vendorMessagingUser->_uid }}']"></span></a>
                              @endif
                                @endforeach
                                </div>
                              </li>
                              @endif
                              @endif
                            @endif
                          </nav>
                          <div class="tab-content lw-contact-list-header" id="nav-tabContent" x-cloak>
                            <div class="tab-pane fade show active pl-2" id="lwAllContactsTab" role="tabpanel" aria-labelledby="lw-all-contacts-tab" x-data="{isExpandedLabels:false}">
                                @if (isset($allLabels) && count($allLabels) > 0)
                                <fieldset class="py-0 lw-labels-filter-fieldset">
                                    <legend class="py-0"><small>
                                        {{  __tr('Filter by labels') }}
                                       <sup>
                                        <a class="" href="#" x-show="!isExpandedLabels" @click="isExpandedLabels = !isExpandedLabels">{{  __tr('Expand') }}</a>
                                        <a class="" href="#" x-show="isExpandedLabels" @click="isExpandedLabels = !isExpandedLabels">{{  __tr('Collapse') }}</a>
                                       </sup>
                                    </small>
                                    </legend>
                                <div @click="function(){
                                    _.defer(function() {
                                        window.searchContacts();
                                    });
                                }" class="btn-group-toggle my-1 lw-labels-selector-box" x-bind:class="isExpandedLabels ? 'lw-expanded-labels' : ''" data-toggle="buttons">
                                    <label class="btn btn-outline-light btn-sm active">
                                    <input class="lw-search-labels" type="radio" checked name="selected_label" value="" autocomplete="off"> <i class="fa fa-times ml-0"></i>
                                  </label>
                                    @foreach($allLabels as $label)
                                    <label style="border-color: {{ $label['text_color'] }}!important;background: {{ $label['bg_color'] }}!important;color: {{ $label['text_color'] }}!important;" class="btn btn-outline-warning btn-sm my-1 lw-contact-list-label-tag">
                                      <input class="lw-search-labels" type="radio" name="selected_label" value="{{ $label['_id'] }}" autocomplete="off"> {{ $label['title'] }}
                                    </label>
                                     @endforeach
                                </div>
                                </fieldset>
                                @endif
                                <div class="form-group m-0 my-2"><label for="lwShowUnreadOnlyContacts"><input data-lw-plugin="lwSwitchery" data-color="orange" data-size="small" x-model="showUnreadContactsOnly" x-init="$watch('showUnreadContactsOnly', function(value) {
                            window.showUnreadContactsOnly = value;
                            _.defer(function() {
                                window.searchContacts();
                            });
                        })" class="custom-checkbox" id="lwShowUnreadOnlyContacts" type="checkbox" name="unread_only_contacts" id=""> <span x-show="!showUnreadContactsOnly">{{  __tr('Show all') }}</span><span x-show="showUnreadContactsOnly">{{  __tr('Show unread only') }}</span></label>
                     <abbr class="float-right" title="{{  __tr('Once you get the response by the contact, they will be come in the chat list of this chat window, alternatively you can click on chat button of the contact list to chat with the contact.') }}">?</abbr>
                    </div>
                            <div class="form-group">
                                <input x-model="search" x-on:keyup.debounce.500ms="function(value) {
                                    window.searchValue = this.search;
                                    window.searchContacts();
                                }" x-ref="searchField" placeholder="{{ __tr('type to search') }}" type="search" class="form-control">
                            </div>

                            <div class="list-group lw-contact-list shadow-lg list-group-flush" >
                            <template x-for="contactItem in filteredContacts">
                                @if (($assigned ?? null))
                                {{-- <template x-if="contactItem.assigned_users__id == '{{ getUserId() }}'"> --}}
                                @endif
                                <a x-show="(contact && contact._uid == contactItem._uid) || (showUnreadContactsOnly && contactItem.unread_messages_count) || !showUnreadContactsOnly" @click.prevent="isContactListOpened = false;contact = {};whatsappMessageLogs=[]" :data-messaged-at="contactItem.last_message?.messaged_at" @click="whatsappMessageLogs = [];messagePaginatePage = 0;contact.__data = {}; appFuncs.resetForm();"
                                    :class="[(contact && (contact._uid == contactItem._uid)) ? 'lw-contact-list-item-selected' : '']"
                                    :href="__Utils.apiURL('{{ route('vendor.chat_message.contact.view', ['contactUid', 'assigned' => ($assigned ?? '')]) }}',{'contactUid': contactItem._uid})"
                                    class="list-group-item list-group-item-action lw-contact lw-ajax-link-action lw-action-change-url" data-callback="updateContactInfo">
                                    {{-- d-flex align-items-start --}}
                                    <div class="ms-2 me-auto w-100 mt-1">
                                        <div class="float-left">
                                                <div class="lw-contact-avatar bg-primary text-white text-center align-content-center">
                                                    <span x-text="contactItem.name_initials"></span>
                                                </div>
                                        </div>
                                        <div class="mt-2">
                                            <h3>
                                                <span x-show="contactItem.full_name" x-text="contactItem.full_name"></span>
                                                <span x-show="contactItem.full_name"> - </span>
                                                @if(hasVendorAccess('hide_contact_phone_numbers'))
                                                    <span x-text="contactItem.wa_id"></span>
                                                @else
                                                    <span x-text="__Utils.formatAsLocaleNumber(Number(contactItem.wa_id))"></span>
                                                @endif
                                            </h3>
                                        </div>
                                    </div>
                                    <div class="mt-0 p-2" x-init="contactItem.label_string = ''; contactLabel = {}">
                                    <template x-for="contactLabel in contactItem.labels">
                                        <span class="mr-2"><small  x-init="contactItem.label_string = contactItem.label_string + ' ' + contactLabel.title" class="mr-0" x-bind:title="contactLabel.title"><i x-bind:style="'color:'+contactLabel.bg_color+';'" class="fa fa-tag"></i></small> <small x-text="contactLabel.title"></small></span>
                                    </template>
                                </div>
                                <hr class="my-0">
                                <div class="text-right w-100 mb-2 px-2 small">
                                        <small class="text-muted lw-last-message-at small"
                                            x-text="contactItem.last_message?.formatted_message_ago_time"></small>
                                        <span x-show="contactItem.unread_messages_count"
                                            class="badge bg-success rounded-pill"
                                            x-text="contactItem.unread_messages_count"></span>
                                    </div>
                                    </a>
                                @if (($assigned ?? null))
                                {{-- </template> --}}
                                @endif
                            </template>
                            <div class="p-4" x-show="contactsPaginatePage">
                                <button x-cloak class="btn btn-sm btn-block btn-secondary" @click="loadMoreContacts" ><i class="fa fa-download"></i> {{  __tr('Load More') }}</button>
                            </div>
                        </div>
                    </div>
                    </div>
                    </div>
                    <div class="page col-sm-12 col-md-6 col-lg-6 col-xl-6 mb-4" :class="(!contact) ? 'lw-disabled-block-content' : ''" class="chat-container" x-cloak>
                        {{-- <h2>{{ __tr('Chat') }}</h2> --}}
                        <div class="marvel-device nexus5">
                            <div class="screen">
                                <div class="screen-container">
                                    <div class="chat" id="lwChatWindowBox">
                                        {{-- <template x-if="contact"> --}}
                                            <div>
                                                <template x-if="contact">
                                                <div class="user-bar">
                                                    <div class="back d-md-none" @click.prevent="isContactListOpened = true">
                                                        <i class="fa fa-users"></i>
                                                    </div>
                                                    <div class="avatar d-none d-md-inline bg-primary text-white text-center align-content-center">
                                                        <span x-text="contact.name_initials"></span>
                                                    </div>
                                                    <div class="name">
                                                        <span><span x-text="contact.full_name"></span>
                                                            <small> - 
                                                                @if(hasVendorAccess('hide_contact_phone_numbers'))
                                                                    <small x-text="contact.wa_id"></small>
                                                                @else
                                                                    <a target="_blank" x-bind:href="'https://api.whatsapp.com/send?phone=' + contact.wa_id" x-text="contact.wa_id ? __Utils.formatAsLocaleNumber(Number(contact.wa_id)) : ''"></a>
                                                                @endif
                                                            </small>
                                                        </span>
                                                        <template x-if="isDirectMessageDeliveryWindowOpened">
                                                            <span class="status text-success " x-text="directMessageDeliveryWindowOpenedTillMessage"></span>
                                                        </template>
                                                            <template x-if="!isDirectMessageDeliveryWindowOpened">
                                                            <span class="status text-yellow " title="{{ __tr("As you may not received any response in last 24 hours, your direct message may not get delivered. However you can send template messages.") }}">{{  __tr('You can\'t reply, they needs to reply back to start conversion.') }}</span>
                                                             </template>
                                                    </div>
                                                    
                                                    <template x-if="contact">
                                                    <div class="actions more lw-user-new-actions" x-data="{isAiChatBotEnabled:!contact.disable_ai_bot}" x-cloak>
                                                        {{-- Whatsapp call button --}}
                                                        @stack('whatsappCallButton')
                                                        {{-- Whatsapp call button --}}
                                                        <a href="#" class="lw-whatsapp-bar-icon-btn" data-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v text-white"></i>
                                                        </a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                        <a x-bind:href="__Utils.apiURL('{{ route('vendor.template_message.contact.view', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})" class="dropdown-item"><i class="fas fa-paper-plane"></i> {{ __tr('Send Template Message') }}</a>
                                                        <a x-cloak
                                                            :class="whatsappMessageLogs.length <= 0 ? 'disabled' : ''"
                                                            data-method="post" data-confirm="#lwClearChatHistoryWarning" x-bind:href="__Utils.apiURL('{{ route('vendor.chat_message.delete.process', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"
                                                            class="dropdown-item text-danger lw-ajax-link-action"><i class="fas fa-eraser"></i> {{ __tr('Clear Chat History') }}</a>
                                                        <script type="text/template" id="lwClearChatHistoryWarning">
                                                            <h3>{{  __tr('Are you sure you want to clear chat history for this contact?') }}</h3>
                                                                <p class="text-warning">{{  __tr('Only chat history will be deleted permanently, it won\'t delete campaign messages.') }}</p>
                                                            </script>

                                                            <template x-if='contact && (_.isEmpty(contact?.wa_blocked_at))'>
                                                                <span :title="isDirectMessageDeliveryWindowOpened == false ? '{{ __tr('Blocking is not allowed as no response has been received within the past 24 hours') }}' : ''">
                                                                <a x-cloak
                                                                    :class="{ 'disabled': isDirectMessageDeliveryWindowOpened == false }"
                                                                    :style="isDirectMessageDeliveryWindowOpened == false ? {
                                                                        'pointer-events': 'none',
                                                                        'color': 'gray',
                                                                        'cursor': 'not-allowed',
                                                                        'text-decoration': 'none'
                                                                        } : {}"
                                                                    :href="isDirectMessageDeliveryWindowOpened == false ? 'javascript:void(0)' : __Utils.apiURL('{{ route('vendor.contact.write.block', ['contactIdOrUid']) }}', { contactIdOrUid: contact._uid })"
                                                                    @click="if (isDirectMessageDeliveryWindowOpened == false) { $event.preventDefault(); console.log('blocked'); return; }"
                                                                    data-method="post"
                                                                    data-confirm="#lwBlockContact-template"
                                                                    title="{{ __tr('Block') }}"
                                                                    data-callback="appFuncs.modelSuccessCallback"
                                                                    class="dropdown-item lw-ajax-link-action-via-confirm"
                                                                    aria-disabled="true">
                                                                    <i class="fa fa-ban"></i> {{ __tr('Block') }}
                                                                </a>
                                                                </span>
                                                            </template>

                                                            <template x-if='contact && (!_.isEmpty(contact?.wa_blocked_at))'>
                                                                <a x-cloak 
                                                                :href="__Utils.apiURL('{{ route('vendor.contact.write.unblock', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"
                                                                @click="if (isDirectMessageDeliveryWindowOpened == false) { $event.preventDefault(); console.log('blocked'); return; }"
                                                                data-method="post" class="dropdown-item lw-ajax-link-action-via-confirm" data-confirm="#lwUnblockContact-template" title="{{ __tr('Unblock') }}" data-callback="appFuncs.modelSuccessCallback" aria-disabled="true"><i class="fa fa-ban"></i> {{ __tr('Unblock') }}</a>
                                                            </template>
                                                        </div>
                                                        <span class="lw-whatsapp-bar-icon-btn ml-3 d-md-none" @click.prevent="isContactCrmBlockOpened = true"><i class="fa fa-user-tie"></i></span>
                                                    </div>
                                                    </template>
                                                </div>
                                                </template>
                                                <div class="conversation">
                                                    <div class="conversation-container" id="lwConversionChatContainer">
                                                            <div class="w-100" id="lwEndOfChats">&shy;</div>
                                                            <template x-for="whatsappMessageLogItem in whatsappMessageLogs">
                                                                <div class="lw-chat-message-item"
                                                                    :id="whatsappMessageLogItem._uid">
                                                                    <template
                                                                        x-if="whatsappMessageLogItem.is_incoming_message && !whatsappMessageLogItem.is_system_message">
                                                                        <div class="message received">
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.replied_to_whatsapp_message_logs__uid">
                                                                                <a href="#"
                                                                                    @click.prevent="lwScrollTo('#'+whatsappMessageLogItem.replied_to_whatsapp_message_logs__uid)"
                                                                                    class="badge d-flex text-muted justify-content-end"><i
                                                                                        class="fa fa-link"></i> {{
                                                                                    __tr('Replied to') }}</a>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.template_message">
                                                                                <div class="lw-template-message"
                                                                                    x-show="whatsappMessageLogItem.template_message"
                                                                                    x-html="whatsappMessageLogItem.template_message">
                                                                                </div>
                                                                            </template>
                                                                            <div x-show="whatsappMessageLogItem.message && !whatsappMessageLogItem.__data?.interaction_message_data"><span class="lw-plain-message-text" x-html="whatsappMessageLogItem.message"></span></div>
                                                                            <template
                                                                                x-if="(whatsappMessageLogItem.whatsapp_message_error)">
                                                                                <div class="p-1 mt-2">
                                                                                    <small class="text-danger"> <i
                                                                                            class="fas fa-exclamation-circle text-danger text-shadow"></i>
                                                                                        <em
                                                                                            x-text="whatsappMessageLogItem.whatsapp_message_error"></em></small>
                                                                                </div>
                                                                            </template>
                                                                            <span class="metadata"><span class="time"
                                                                                    x-text="whatsappMessageLogItem.formatted_message_time"></span></span>
                                                                        </div>
                                                                    </template>
                                                                    <template
                                                                        x-if="!whatsappMessageLogItem.is_incoming_message && !whatsappMessageLogItem.is_system_message">
                                                                        <div class="message sent">
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.__data?.options?.bot_reply">
                                                                                <span class="badge d-flex text-muted justify-content-end"
                                                                                    :title="whatsappMessageLogItem.__data?.options?.ai_bot_reply ? '{{ __tr('AI Bot Reply') }}' : '{{ __tr('Bot Reply') }}'">
                                                                                    <template x-if="whatsappMessageLogItem.__data?.options?.ai_bot_reply">
                                                                                        <span class="mr-1 text-warning">AI</span>
                                                                                    </template>
                                                                                    <i class="fas fa-robot text-muted"></i>
                                                                                </span>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.campaigns__id">
                                                                                <span class="badge d-flex justify-content-end" title="{{ __tr('Campaign Message') }}">
                                                                                    <i class="fas fa-bullhorn text-info"></i>
                                                                                </span>
                                                                            </template>
                                                                            <template
                                                                                x-if="whatsappMessageLogItem.template_message">
                                                                                <div class="lw-template-message"
                                                                                    x-show="whatsappMessageLogItem.template_message"
                                                                                    x-html="whatsappMessageLogItem.template_message">
                                                                                </div>
                                                                            </template>
                                                                            <template x-if="whatsappMessageLogItem.message && !whatsappMessageLogItem.__data?.interaction_message_data">
                                                                                <div class="lw-template-message" x-show="whatsappMessageLogItem.message"><span class="lw-plain-message-text" x-html="whatsappMessageLogItem.message"></span>
                                                                                </div>
                                                                            </template>
                                                                            <template
                                                                                x-if="(whatsappMessageLogItem.whatsapp_message_error)">
                                                                                <div class="p-1 mt-2">
                                                                                    <small class="text-danger"> <i
                                                                                            class="fas fa-exclamation-circle text-danger text-shadow"></i>
                                                                                        <em
                                                                                            x-text="whatsappMessageLogItem.whatsapp_message_error"></em></small>
                                                                                </div>
                                                                            </template>
                                                                            <span class="metadata">
                                                                                <span class="time"
                                                                                    x-text="whatsappMessageLogItem.formatted_message_time"></span>
                                                                                <span class="tick">
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'read'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-read.svg') }}" title="{{ __tr('Read') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'played'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-read.svg') }}" title="{{ __tr('Played') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'delivered'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-delivered.svg') }}" title="{{ __tr('Delivered') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'sent'">
                                                                                        <img src="{{ __yesset('imgs/icons/icon-sent.svg') }}" title="{{ __tr('Sent') }}" width="16" height="16">
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="whatsappMessageLogItem.status == 'failed'">
                                                                                        <i title="{{ __tr('Failed') }}"
                                                                                            class="fas fa-exclamation-circle text-danger"></i>
                                                                                    </template>
                                                                                    <template
                                                                                        x-if="(whatsappMessageLogItem.status == 'accepted')">
                                                                                        <i title="{{ __tr('Accepted') }}"
                                                                                            class="far fa-clock text-muted"></i>
                                                                                    </template>
                                                                                </span>
                                                                            </span>
                                                                        </div>
                                                                    </template>
                                                                    <template x-if="whatsappMessageLogItem.is_system_message">
                                                                        <div>
                                                                            <div class="text-center align-content-center lw-system-message-container p-2">
                                                                                <div class="text-center align-content-center lw-chat-history-container">
                                                                                    <div class="lw-chat-history-message mb-1" x-html="whatsappMessageLogItem.message"></div>
                                                                                </div>
                                                                                <small><small class="small text-muted mt-2" x-text="whatsappMessageLogItem.formatted_updated_time"></small></small>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <div class="w-100 px-4" id="lwEndOfChats">&shy; <button x-cloak x-show="messagePaginatePage" class="btn btn-sm btn-block btn-secondary" @click="loadEarlierMessages" ><i class="fa fa-download"></i> {{  __tr('Load earlier messages') }}</button></div>
                                                    </div>
                                                    <template x-if="contact && (!_.isEmpty(contact?.wa_blocked_at))">
                                                        <div class="alert alert-light text-center align-content-center">
                                                            <strong class="text-danger" title="{{ __tr('Unblock') }}">{{  __tr('This contact is blocked, ') }}
                                                                <a data-method="post" 
                                                                class="lw-ajax-link-action-via-confirm"
                                                                :href="__Utils.apiURL('{{ route('vendor.contact.write.unblock', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"
                                                                @click="if (isDirectMessageDeliveryWindowOpened == false) { $event.preventDefault(); console.log('blocked'); return; }"
                                                                data-confirm="#lwUnblockContact-template" title="{{ __tr('Click here to unblock.') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-ban"></i> {{  __tr('Click here to unblock.') }}</a></strong>
                                                        </div>
                                                    </template>
                                                    <span x-show="contact && (_.isEmpty(contact?.wa_blocked_at))">
                                                    <x-lw.form data-event-stream-update="true" data-callback="appFuncs.resetForm" id="whatsAppMessengerForm"
                                                        class="conversation-compose" data-show-processing="false"
                                                        :action="route('vendor.chat_message.send.process')">
                                                        <input type="hidden" name="contact_uid" x-bind:value="contact?._uid">
                                                        {{-- emoji following blank tag as removing it may break input layout
                                                        --}}
                                                        <div class="emoji">
                                                        </div>
                                                        <textarea name="message_body" required class="input-msg lw-input-emoji" placeholder="{{ __tr('Type a message') }}" autocomplete="off" autofocus></textarea>
                                                        <template x-if="contact">
                                                          <div class="photo">
                                                        <a title="{!! __tr('Record & Send') !!}" class="lw-ajax-link-action lw-whatsapp-bar-icon-btn" href="#" data-toggle="modal" data-target="#lwSendRecording"><i class="fa fa-microphone text-muted"></i> </a>
                                                          </div>
                                                        </template>
                                                        <div class="photo dropup">
                                                            <!-- Default dropup button -->
                                                            <a href="#" class="lw-whatsapp-bar-icon-btn" data-toggle="dropdown" aria-expanded="false">
                                                                <i class=" fa fa-paperclip text-muted"></i>
                                                            </a>
                                                            <div class="dropdown-menu dropdown-menu-right">

                                                                <!-- Quick Bot Reply -->
                                                                <template x-if="contact">
                                                                    <a title="{{ __tr('Quick Bot Reply') }}"
                                                                        class="lw-ajax-link-action dropdown-item" data-response-template="#lwQuickReplyContentBody" x-bind:href="__Utils.apiURL('{{ route('vendor.bot_reply.read.all.active.bots', ['contactIdOrUid']) }}', { 'contactIdOrUid': contact._uid})"  data-toggle="modal" data-target="#lwQuickReply"><i class="fa fa-bolt text-muted"></i> {{ __tr('Quick Bot Reply') }}
                                                                    </a>
                                                                </template>
                                                                <!-- /Quick Bot Reply -->
                                                                <a title="{{ __tr('Send Document') }}"
                                                                    class="lw-ajax-link-action dropdown-item" data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', [
                                                                    'mediaType' => 'document' ]) }}"><i class="fa fa-file text-muted"></i> {{ __tr('Send Document') }}
                                                                </a>
                                                                <a title="{{ __tr('Send Image') }}" class="lw-ajax-link-action dropdown-item"
                                                                    data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', ['mediaType' => 'image']) }}"><i class="fa fa-image text-muted"></i> {{ __tr('Send Image') }}
                                                                </a>
                                                                <a title="{{ __tr('Send Video') }}" class="lw-ajax-link-action dropdown-item"
                                                                    data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', [
                                                                    'mediaType' => 'video']) }}"><i class="fa fa-video text-muted"></i> {{ __tr('Send Video') }}
                                                                </a>
                                                                <a title="{{ __tr('Send Audio') }}" class="lw-ajax-link-action dropdown-item"
                                                                    data-toggle="modal"
                                                                    data-response-template="#lwWhatsappAttachment"
                                                                    data-target="#lwMediaUploadAndSend"
                                                                    data-callback="appFuncs.prepareUpload" href="{{ route('vendor.chat_message_media.upload.prepare', [
                                                                    'mediaType' => 'audio']) }}"><i class="fa fa-headphones text-muted"></i> {{ __tr('Send Audio') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <button class="send" type="submit">
                                                            <div class="circle pl-2">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="1.5em"
                                                                    height="1.5em" viewBox="0 0 24 24">
                                                                    <path fill="currentColor"
                                                                        d="M2.01 21L23 12L2.01 3L2 10l15 2l-15 2z" />
                                                                </svg>
                                                            </div>
                                                        </button>
                                                    </x-lw.form>
                                                    {{-- error container --}}
                                                    <div data-form-id="#whatsAppMessengerForm"
                                                        class="lw-error-container-message_body p-2">
                                                    </div>
                                                    </span>
                                                </div>
                                            </div>
                                        {{-- </template> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3 col-lg-3 col-xl-3 mb-4 lw-contact-crm-block" :class="(!contact) ? 'lw-disabled-block-content' : ''" x-show="isContactCrmBlockOpened">
                            <div class="row">
                                <div class="col-12 text-right">
                                    <span class="btn btn-light btn-sm float-right d-md-none" @click.prevent="isContactCrmBlockOpened = false"><i class="fa fa-arrow-left"></i></span>
                                </div>
                                <template x-if="contact">
                                    <fieldset class="col-12 p-2 mt-0">
                                        <legend>{{  __tr('Contact Info') }}</legend>
                                        @if (hasVendorAccess('manage_contacts', 'add_edit_contacts'))
                                        <div class="text-right mt--3">
                                            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Edit') }}" class="lw-btn btn btn-sm btn-light lw-ajax-link-action" data-response-template="#lwEditContactBody" x-bind:href="__Utils.apiURL('{{ route('vendor.contact.read.update.data', [ 'contactIdOrUid']) }}', {'contactIdOrUid': contact._uid})"  data-toggle="modal" data-target="#lwEditContact"><i class="fa fa-user-edit"></i> {{  __tr('Edit') }}</a>
                                        </div>
                                        @endif
                                        <dl class="px-2">
                                            <dt>{{  __tr('Name') }}</dt>
                                            <dd x-text="contact.full_name"></dd>
                                            <dt>{{  __tr('Phone') }}</dt>
                                            @if(hasVendorAccess('hide_contact_phone_numbers'))
                                                <dd x-text="contact.wa_id"></dd>
                                            @else
                                                <dd x-text="__Utils.formatAsLocaleNumber(Number(contact.wa_id))"></dd>
                                            @endif
                                            <dt>{{  __tr('Email') }}</dt>
                                            <dd x-text="contact.email ? contact.email : '-'"></dd>
                                            <dt>{{  __tr('Language') }}</dt>
                                            <dd x-text="contact.language_code ? contact.language_code : '-'"></dd>
                                        </dl>
                                    </fieldset>
                                </template>
                                <div class="col-12 p-0">
                                    <x-lw.form id="lwAssignSystemUserForm" :action="route('vendor.chat.assign_user.process')" data-callback="window.assignTeamMember">
                                        <input type="hidden" name="contactIdOrUid" :value="contact?._uid">
                                        {{-- Select messaging permitted team member to assign this contact chat --}}
                                        <fieldset class="col-12 p-2">
                                            <legend>{{  __tr('Assign Team Member') }}</legend>
                                            
                                                <div class="my-3">
                                                    @if(isAiBotAvailable())                                                        
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="checkbox" x-model="isAiChatBotEnabled" id="lwEnableAiBot" value="1">
                                                            <label class="form-check-label" for="lwEnableAiBot">{{ __tr('Enable AI Bot') }}</label>
                                                            <input type="hidden" name="enable_ai_bot" :value="isAiChatBotEnabled ? '1' : ''">
                                                        </div>
                                                    @endif

                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" x-model="isReplyBotEnable" id="lwEnableReplyBot" value="1">
                                                        <label class="form-check-label" for="lwEnableReplyBot">{{ __tr('Enable Reply Bot') }}</label>
                                                        <input type="hidden" name="enable_reply_bot" :value="isReplyBotEnable ? '1' : ''">
                                                    </div>
                                                </div>
                                            
                                            <x-lw.input-field id="lwCurrentlyAssignedUserUid" type="selectize" data-form-group-class="mt--4" name="assigned_users_uid" class="custom-select"
                                    data-selected="{{ $currentlyAssignedUserUid }}" x-model="currentlyAssignedUserUid">
                                            <x-slot name="selectOptions">
                                                <option value="">{{  __tr('Unassigned') }}</option>
                                                <option value="no_one">{{  __tr('Unassigned') }}</option>
                                                @foreach ($vendorMessagingUsers as $vendorMessagingUser)
                                                <option value="{{ $vendorMessagingUser->_uid }}">{{ $vendorMessagingUser->first_name . ' ' . $vendorMessagingUser->last_name }} @if($vendorMessagingUser->_uid == getUserUID()) ({{  __tr('You') }}) @endif</option>
                                                @endforeach
                                            </x-slot>
                                            </x-lw.input-field>
                                            <div class="">
                                                <button type="submit" class="btn btn-dark btn-sm mt--1 float-right">{{  __tr('Save') }}</button>
                                            </div>
                                        </fieldset>
                                    </x-lw.form>
                                </div>
                                <template x-if="contact">
                                    {{-- tags and labels --}}
                                    <fieldset class="col-12 p-2">
                                        {{-- <hr class="my-4"> --}}
                                        <legend class="pb-0 pt-1">{{  __tr('Labels/Tags') }} <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Manage Labels') }}" class="lw-btn btn btn-sm btn-link lw-ajax-link-action float-right pt-1" data-response-template="#lwManageContactLabelsBody" x-bind:href="__Utils.apiURL('{{ route('vendor.chat.contact_labels.read', [ 'contactUid']) }}', {'contactUid': contact._uid})"  data-toggle="modal" data-target="#lwManageContactLabels"><i class="fa fa-cog"></i></a></legend>
                                        <x-lw.form data-callback="onUpdateLabels" id="lwAssignContactLabelsForm" :action="route('vendor.chat.assign_labels.process')">
                                                <input type="hidden" name="contactUid" x-bind:value="contact._uid" />
                                                <div x-show="labelsElement"></div>
                                                <select class="border-0 lw-borderers-selectize" id="lwAssignLabelsField" data-form-group-class="" x-bind:data-selected="assignedLabelIds" name="contact_labels[]" multiple >
                                                    <option value="">{{ __tr('Select Labels') }}</option>
                                                        @foreach($allLabels as $label)
                                                            <option value="{{ $label['_id'] }}">{{ $label['title'] }}</option>
                                                        @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-dark btn-sm float-right">{{  __tr('Update') }}</button>
                                        </x-lw.form>
                                    </fieldset>
                                </template>
                                <template x-if="contact">
                                    {{-- notes --}}
                                    <fieldset class="col-12 p-2" x-data="{openNotesEdit:false,contactNotes:contact.__data?.contact_notes}">
                                        {{-- <hr class="my-4"> --}}
                                        <legend class="pb-0 pt-1" for="lwContactNotes">{{  __tr('Notes') }} <button class="btn btn-link btn-sm float-right pt-1" @click="openNotesEdit = true"><i class="fas fa-edit"></i></button></legend>
                                        <div x-show="!openNotesEdit" class="lw-ws-pre-line px-2 pb-4" x-text="contact.__data?.contact_notes"></div>
                                        <x-lw.form x-show="openNotesEdit" id="lwNotesForm" :action="route('vendor.chat.update_notes.process')" >
                                            <input type="hidden" name="contactIdOrUid" :value="contact?._uid">
                                            <div class="form-group mt-0">
                                                <textarea name="contact_notes" id="lwContactNotes" class="form-control" x-bind:value="contact.__data?.contact_notes" x-model="contactNotes" rows="5"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-dark btn-sm mt--2" @click="openNotesEdit = false; if(!contact['__data']) { contact['__data'] = {}} contact['__data']['contact_notes'] = contactNotes;">{!! __tr('Save & Close') !!}</button>
                                            </div>
                                        </x-lw.form>
                                    </fieldset>
                                </template>
                                {{-- check if stack has content --}}
                                @if ($__env->yieldPushContent('chatRightSidebarAdditionalLinksAndButtons'))
                                <fieldset class="col-12 p-2">
                                    <legend>{{  __tr('Links and buttons') }}</legend>
                                    @stack('chatRightSidebarAdditionalLinksAndButtons')
                                </fieldset>
                                @endif
                                {{-- stack the items in right sidebar at bottom --}}
                                @stack('chatRightSidebarFooter')
                            </div>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>
<x-lw.modal id="lwMediaUploadAndSend" :header="__tr('Send Media')" :hasForm="true"
    data-pre-callback="clearModelContainer">
    <!--  document form -->
    <x-lw.form id="lwMediaUploadAndSendForm" :action="route('vendor.chat_message_media.send.process')"
        data-callback="appFuncs.modelSuccessCallback" :data-callback-params="['modalId' => '#lwMediaUploadAndSend']">
        <!-- form body -->
        <input type="hidden" name="contact_uid" x-bind:value="contact?._uid">
        <div id="lwWhatsappAttachment" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwWhatsappAttachment-template">
            <% if(__tData.mediaType == 'document') { %>
            <div class="form-group col-sm-12">
                <input id="lwDocumentMediaFilepond" type="file" data-allow-revert="true"
                    data-label-idle="{{ __tr('Select Document') }}" class="lw-file-uploader" data-instant-upload="true"
                    data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>" id="lwDocumentField" data-file-input-element="#lwDocumentMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_document') ?>' />
                <input id="lwDocumentMedia" type="hidden" value="" name="uploaded_media_file_name" />
                <input type="hidden" value="document" name="media_type" />
            </div>
            <% } else if(__tData.mediaType == 'image') { %>
                <div class="form-group col-sm-12">
                    <input id="lwImageMediaFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                        data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" id="lwImageField" data-file-input-element="#lwImageMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' />
                    <input id="lwImageMedia" type="hidden" value="" name="uploaded_media_file_name" />
                    <input type="hidden" value="image" name="media_type" />
                </div>
                <% } else if(__tData.mediaType == 'video') { %>
                    <div class="form-group col-sm-12">
                        <input id="lwVideoMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" id="lwVideoField" data-file-input-element="#lwVideoMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' />
                        <input id="lwVideoMedia" type="hidden" value="" name="uploaded_media_file_name" />
                        <input type="hidden" value="video" name="media_type" />
                    </div>
                <% } else if(__tData.mediaType == 'audio') { %>
                    <div class="form-group col-sm-12">
                        <input id="lwAudioMediaFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select Audio') }}" class="lw-file-uploader" data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'whatsapp_audio') ?>" id="lwAudioField" data-file-input-element="#lwAudioMedia" data-raw-upload-data-element="#lwRawDocumentMedia" data-allowed-media='<?= getMediaRestriction('whatsapp_audio') ?>' />
                        <input id="lwAudioMedia" type="hidden" value="" name="uploaded_media_file_name" />
                        <input type="hidden" value="audio" name="media_type" />
                    </div>
                <% } %>
                <input id="lwRawDocumentMedia" type="hidden" value="" name="raw_upload_data"/>
                <% if(__tData.mediaType != 'audio') { %>
                <div>
                    <label for="lwMediaCaptionText">{{  __tr('Caption/Text') }}</label>
                    <textarea name="caption" id="lwCaptionField" class="form-control" rows="2"></textarea>
                </div>
                <% } %>
        </script>
        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Send') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Cancel') }}</button>
        </div>
    </x-lw.form>
    <!--/  document form -->
</x-lw.modal>
 <!-- Edit Contact Modal -->
 @include('contact.contact-edit-modal-partial')
 @include('whatsapp.quick-reply-modal')
 @include('whatsapp.recording-modal')
 <!--/ Edit Contact Modal -->
 {{-- Manage labels Modal --}}
 <x-lw.modal id="lwManageContactLabels" :header="__tr('Manage Labels')" :hasForm="true">
        <!-- form body -->
        <div id="lwManageContactLabelsBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwManageContactLabelsBody-template">
            <fieldset class="pb-4 my-4">
                {{-- <legend>{{  __tr('New Label') }}</legend> --}}
                <x-lw.form data-callback="onNewLabelCreated" id="lwManageContactLabelsForm" :action="route('vendor.chat.label.create.write')">
                    <div class="row">
                        <x-lw.input-field type="text" id="lwLabelFieldTitle" data-form-group-class="col-12" :label="__tr('New Label')"  name="title"  required="true">
                            <x-slot name="append">
                            <input type="color" name="text_color" value="#ffffff" style="height: auto;" title="{{ __tr('Label Text Color') }}" class="lw-color-field">
                            <input type="color" name="bg_color" value="#000000" style="height: auto;" title="{{ __tr('Label BG Color') }}" class="lw-color-field">
                            <button type="submit" class="btn btn-primary">{{ __tr('Create') }}</button>
                            </x-slot>
                        </x-lw.input-field>
                    </div>
                </x-lw.form>
            </fieldset>
            <fieldset>
                <legend>{{  __tr('Labels') }}</legend>
                    <ul class="list-group">
                        <template x-for="labelItem in allLabels">
                            <li x-bind:class="'lw-contact-label-'+labelItem._uid" class="list-group-item lw-list-group-border" >
                                <x-lw.form data-callback="onUpdateContactDetails" class="w-100" :action="route('vendor.chat.label.update.write')" class="lw-contact-label-edit-form">
                                    <div class="row">
                                        <input type="hidden" name="labelUid" x-bind:value="labelItem._uid" />
                                        <x-lw.input-field type="text" data-form-group-class="col-12" :label="__tr('Edit Label')"  name="title" x-bind:value="labelItem.title" required="true">
                                            <x-slot name="append">
                                            <input type="color" name="text_color" x-bind:value="labelItem.text_color" style="height: auto;" title="{{ __tr('Label Text Color') }}" class="lw-color-field">
                                            <input type="color" name="bg_color" x-bind:value="labelItem.bg_color" style="height: auto;" title="{{ __tr('Label BG Color') }}" class="lw-color-field">
                                            <button type="submit" class="btn btn-primary">{{ __tr('Save') }}</button>
                                            <a class="btn btn-outline-danger lw-ajax-link-action" data-confirm="{{ __tr('Are you sure you want to delete this label?') }}"  data-callback="updateManageLabelsList" data-method="post" x-bind:href="__Utils.apiURL('{{ route('vendor.chat.label.delete.write', ['labelUid']) }}',{'labelUid': labelItem._uid})"><i class="fa fa-trash"></i></a>
                                            </x-slot>
                                        </x-lw.input-field>
                                    </div>
                                </x-lw.form>
                            </li>
                            </template>
                    </ul>
            </fieldset>
    </script>
        <!-- form footer -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    <!--/  Edit Contact Form -->
</x-lw.modal>
 {{-- /Manage labels Modal --}}
</div>
<script>
     (function() {
        'use strict';
     document.addEventListener('alpine:init', () => {
        Alpine.data('initialMessageData', () => ({
            // whatsappMessageLogs: @json($whatsappMessageLogs),
            whatsappMessageLogs: [],
            messagePaginatePage: 0,
            contactsPaginatePage: 0,
            isDirectMessageDeliveryWindowOpened: {{ $isDirectMessageDeliveryWindowOpened ?: 0 }},
            directMessageDeliveryWindowOpenedTillMessage: '{{ $directMessageDeliveryWindowOpenedTillMessage }}',
            contact:@json($contact),
            isContactDetailsUpdated: false,
            currentlyAssignedUserUid:'{{ $currentlyAssignedUserUid }}',
            isAiChatBotEnabled: "{{ $isAiChatBotEnabled }}",
            isReplyBotEnable: "{{ $isReplyBotEnable }}",
            search: "",
            search_labels: "",
            contacts: {},
            assignedLabelIds: [],
            allLabels: @json($allLabels),
            filteredContacts: function () {
                return _.reverse(_.sortBy(this.contacts, [function(o) { return o.last_message?.messaged_at; }]));
            },
            labelsElement : function() {
                // reset the selectize
               var $labelsElement =  $('#lwAssignLabelsField').selectize({
                    maxItems: null,
                    items: _.values(this.assignedLabelIds),
                    valueField: '_id',
                    labelField: 'title',
                    searchField: 'title',
                    options: this.allLabels,
                    create: false,
                    closeAfterSelect: true,
                    render: {
                        item: function (item, escape) {
                            return (
                            '<div class="" style="color:'+item.text_color+';background-color:'+item.bg_color+';" >' +
                            (item.title
                                ? '<span>' + escape(item.title) + "</span>"
                                : "") +
                            "</div>"
                            );
                        },
                        option: function (item, escape) {
                            return (
                            '<div class="p-1 rounded m-2" style="color:'+item.text_color+';background-color:'+item.bg_color+';">' +
                            '<span>' +
                            escape(item.title) +
                            "</span>" +
                            "</div>"
                            );
                        },
                    }
                });
                $labelsElement[0].selectize.clear(true);
                $labelsElement[0].selectize.setValue(['']);
                $labelsElement[0].selectize.setValue(_.values(this.assignedLabelIds));
            }
        }));
    });
})();
</script>
@push('head')
    {!! __yesset('dist/emojionearea/emojionearea.min.css', true) !!}
@endpush
@push('appScripts')
{!! __yesset('dist/emojionearea/emojionearea.min.js', true) !!}

<!-- Contact block template -->
<script type="text/template" id="lwBlockContact-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to block this Contact?') }}</p>
</script>
<!-- /Contact block template -->

 <!-- Contact unblock template -->
<script type="text/template" id="lwUnblockContact-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to unblock this Contact?') }}</p>
</script>
<!-- /Contact unblock template -->
    
<script>
(function($) {
    'use strict';
    window.isPageRefresh = false;
    window.messagePaginatePage = 1;
    window.searchValue = '';
    window.showUnreadContactsOnly = 0;
    window.loadEarlierMessages = function(responseData, callbackParams) {
        __DataRequest.get(__Utils.apiURL('{!! route('vendor.chat_message.contact.view', ['contactUid', 'way' => 'prepend', 'page', 'assigned' => ($assigned ?? '')]) !!}',{'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.messagePaginatePage}),{}, function() {});
        if(callbackParams) {
            appFuncs.modelSuccessCallback(responseData, callbackParams);
        }
    };
    window.onUpdateContactDetails = function(responseData, callbackParams) {
        __DataRequest.get(__Utils.apiURL('{!! route('vendor.chat_message.contact.view', ['contactUid', 'current_page', 'assigned' => ($assigned ?? '')]) !!}',{'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'current_page':'current_page='+ window.messagePaginatePage}),{}, function() {});
        if(callbackParams) {
            appFuncs.modelSuccessCallback(responseData, callbackParams);
        }
    };
    window.contactsPaginatePage = 1;
    window.loadMoreContacts = function(responseData, callbackParams) {
        __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid', 'page' => '', 'way' => 'append', 'search' => '', 'unread_only' => '', 'assigned' => ($assigned ?? '')]) !!}", {'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.contactsPaginatePage + '&', 'search':'search='+ window.searchValue + '&', 'unread_only':'unread_only='+ window.showUnreadContactsOnly + '&'}),{}, function() {});
    };
    window.searchContacts = function(responseData, callbackParams) {
        // / Find all checked inputs and retrieve their values
        var selectedLabels = $('.lw-search-labels:checked').val();
        selectedLabels = selectedLabels ? selectedLabels : '';
        window.contactsPaginatePage = 1;
        __DataRequest.updateModels({
            contactsPaginatePage: 1,
        });
        __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid', 'page' => '', 'way' => '', 'search' => '','selected_labels' => '', 'unread_only' => '', 'assigned' => ($assigned ?? '')]) !!}", {'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.contactsPaginatePage + '&', 'search':'search='+ window.searchValue + '&','selected_labels':'selected_labels='+ selectedLabels + '&', 'unread_only':'unread_only='+ window.showUnreadContactsOnly + '&'}),{}, function() {});
    };
    window.updateContactList = function(responseData, callbackParams) {
        __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid', 'page' => '', 'assigned' => ($assigned ?? '')]) !!}", {'contactUid': $('#lwWhatsAppChatWindow').attr('data-contact-uid'),'page':'page='+ window.contactsPaginatePage + '&'}),{}, function() {});
    };
    window.updateContactInfo = function(responseData) {
        $('#lwCurrentlyAssignedUserUid')[0].selectize.setValue(responseData.data.currentlyAssignedUserUid);
    };
    window.onNewLabelCreated = function(responseData) {
        $('#lwLabelFieldTitle').val('');
    };
    window.updateManageLabelsList = function(responseData) {
        if(responseData.reaction == 1) {
            window.onUpdateContactDetails();
        }
    };
    window.onUpdateLabels = function(responseData) {
        if(responseData.reaction == 1) {
            window.updateContactList();
        }
    };
    window.updateContactList();
    window.onUpdateContactDetails();
    window.lwMessengerEmojiArea = $(".lw-input-emoji").emojioneArea({
    useInternalCDN: true,
    pickerPosition: "top",
    searchPlaceholder: "{{ __tr('Search') }}",
    buttonTitle: "{{ __tr('Use the TAB key to insert emoji faster') }}",
    events: {
        'emojibtn.click': function (editor, event) {
            this.hidePicker();
        },
        keyUp: function (editor, event) {
            if (event && event.which == 13 && !event.shiftKey && $.trim(this.getText())) { // On Enter
                $('.lw-input-emoji').val(this.getText());
                $('#whatsAppMessengerForm').submit();
                this.hidePicker();
                appFuncs.resetForm();
            }
        }
    }
}); 
})(jQuery);
</script>
@endpush
@endsection()