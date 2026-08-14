@php
$hasActiveLicense = true;
if(isLoggedIn() and (request()->route()->getName() != 'manage.configuration.product_registration') and
(!getAppSettings('product_registration', 'registration_id') or sha1(array_get($_SERVER, 'HTTP_HOST', '') .
getAppSettings('product_registration', 'registration_id') . '4.5+') !== getAppSettings('product_registration',
'signature'))) {
$hasActiveLicense = false;
if(hasCentralAccess()) {
header("Location: " . route('manage.configuration.product_registration'));
exit;
}
}
$currentAppTheme ='';
 // Default theme from settings
 $currentAppTheme = getUserAppTheme()

@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $CURRENT_LOCALE_DIRECTION }}" lw-theme-mode="{{ $currentAppTheme }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {!! (isset($title) and $title) ? $title : __tr('Welcome') !!} - {{ getAppSettings('name') }}</title>
    <!-- Light Theme Favicon -->
    <link href="{{getAppSettings('favicon_image_url') }}" rel="icon">
    <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=TikTok+Sans:opsz,wght@12..36,300..900&display=swap" rel="stylesheet">
    @stack('head')
    {!! __yesset(
    [
    // Icons
    'static-assets/packages/fontawesome/css/all.css',
    'dist/css/common-vendorlibs.css',
    'dist/css/vendorlibs.css',
    'argon/css/argon.min.css',
    'dist/css/app.css',
    // 'dist/css/dark-theme.css'
    ]) !!}
        <!-- App Theme Change -->
        @if($currentAppTheme=='dark' ) 
        <link rel="stylesheet" href="{{ __yesset('dist/css/dark-theme.css')}}">
        <!-- Dark Theme Favicon -->
        <link href="{{getAppSettings('dark_theme_favicon_image_url') }}" rel="icon" media="(prefers-color-scheme: dark)">
        @elseif( $currentAppTheme=='system_default')
        <link rel="stylesheet" href="{{ __yesset('dist/css/dark-theme.css')}}"  media="(prefers-color-scheme: dark)">
        @endif
        <!-- /App Theme Change -->

    {{-- custom app css --}}
    <link href="{{ route('app.load_custom_style') }}" rel="stylesheet"  />
    @if(getAppSettings('page_head_code'))
    {!! getAppSettings('page_head_code') !!}
    @endif
</head>

<body
    class="p-0 @if(hasVendorAccess() or hasVendorUserAccess()) lw-minimized-menu @endif @if(isLoggedIn()) lw-authenticated-page @else lw-guest-page @endif {{ $class ?? '' }} lw-app-theme-{{ $currentAppTheme }}"
    x-cloak
    x-data="{disableSoundForMessageNotification:{{ getVendorSettings('is_disabled_message_sound_notification') ? 1 : 0 }},unreadMessagesCount:null}">
    @auth()
    @include('layouts.navbars.sidebar')
    @endauth

    <div class="main-content">
        @include('layouts.navbars.navbar')
        @if(isDemo())
         <div class="lw-alert-dismissible-container top-3">
             <h3 class="alert alert-danger text-center text-white">
                <button type="button" class="mt--2 btn btn-link text-white btn-sm float-right" data-dismiss="alert" aria-label="{{ __tr('Close') }}"> <i class="fa fa-times"></i></button>
                 {{ __tr('Please Note: We sell this script only through CodeCanyon only, Click following links to purchase') }} -> <strong><a class="text-white" target="_blank"
                    href="https://codecanyon.net/item/whatsjet-saas-a-whatsapp-marketing-platform-with-bulk-sending-campaigns-chat-bots/51167362">
                    {{  __tr('Regular License') }}</strong> | <strong><a class="text-white" target="_blank"
                    href="https://codecanyon.net/item/whatsjet-saas-a-whatsapp-marketing-platform-with-bulk-sending-campaigns-chat-bots/51167362?license=extended">
                    {{  __tr('Extended License') }}
                </a></strong>
                </h3>
        </div>
        @endif
        @if ($hasActiveLicense)
        @if(hasVendorAccess())
        <div class="lw-alert-dismissible-container top-6">
            <div class="text-center">
                    @php
                    $vendorPlanDetails = vendorPlanDetails(null, null, getVendorId());
                    @endphp
                    @if(!$vendorPlanDetails->hasActivePlan())
                    <div class="alert alert-danger">
                        <button type="button" class="mt--2 btn btn-link text-white btn-sm float-right" data-dismiss="alert" aria-label="{{ __tr('Close') }}"> <i class="fa fa-times"></i></button>
                        {{ $vendorPlanDetails->message }} - <a class="text-white text-underline"
                        href="{{ route('subscription.read.show') }}">
                        {{ __tr('Go to My Subscription') }}
                    </a>
                    </div>
                    @elseif($vendorPlanDetails->is_expiring)
                    <div class="alert alert-warning">
                        <button type="button" class="mt--2 btn btn-link text-white btn-sm float-right" data-dismiss="alert" aria-label="{{ __tr('Close') }}"> <i class="fa fa-times"></i></button>
                        {{ __tr('Your subscription plan is expiring on __endAt__', [
                        '__endAt__' => formatDate($vendorPlanDetails->ends_at)
                        ]) }} - 
                        '<a class="text-white text-underline"
                        href="{{ route('subscription.read.show') }}">
                        {{ __tr('Go to My Subscription') }}
                    </a>'
                    </div>
                    @endif
                </div>
        </div>
        @endif
        @yield('content')
        @else
        <div class="container">
            <div class="row">
                <div class="col-12 my-5 py-5 text-center">
                    <div class="card my-5 p-5">
                        <i class="fas fa-exclamation-triangle fa-6x mb-4 text-warning"></i>
                        <div class="alert alert-danger my-5">
                            {{ __tr('Product has not been verified yet, please contact via profile or product page.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @guest()
    @include('layouts.footers.guest')
    @endguest
    <?= __yesset(['dist/js/common-vendorlibs.js','dist/js/vendorlibs.js', 'argon/bootstrap/dist/js/bootstrap.bundle.min.js', 'argon/js/argon.js', 'dist/push-js/push.min.js'], true) ?>
    @stack('js')
    @if (hasVendorAccess() or hasVendorUserAccess())
    {{-- QR CODE model --}}
    <x-lw.modal id="lwScanMeDialog" :header="__tr('Scan QR Code to Start Chat')">
        @if (getVendorSettings('current_phone_number_number'))
        <div class="alert alert-dark text-center text-success">
            {{ __tr('You can use following QR Codes to invite people to get connect with you on this platform.') }}
        </div>
        @if (!empty(getVendorSettings('whatsapp_phone_numbers')))
        @foreach (getVendorSettings('whatsapp_phone_numbers') as $whatsappPhoneNumber)
        <fieldset class="text-center">
            <legend class="text-center">{{ $whatsappPhoneNumber['verified_name'] ?? '' }} ({{
                $whatsappPhoneNumber['display_phone_number'] }})</legend>
            <div class="text-center">
                <img class="lw-qr-image" src="{{ route('vendor.whatsapp_qr', [
            'vendorUid' => getVendorUid(),
            'phoneNumber' => cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']),
        ]) }}">
            </div>
            <div class="form-group">
                <h3 class="text-muted">{{ __tr('Phone Number') }}</h3>
                <h3 class="text-success">{{ $whatsappPhoneNumber['display_phone_number'] }}</h3>
                <label for="lwWhatsAppQRImage{{ $loop->index }}">{{ __tr('URL for QR Image') }}</label>
                <div class="input-group">
                    <input type="text" class="form-control" readonly id="lwWhatsAppQRImage{{ $loop->index }}" value="{{ route('vendor.whatsapp_qr', [
                    'vendorUid' => getVendorUid(),
                    'phoneNumber' => cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']),
                ]) }}">
                    <div class="input-group-append">
                        <button class="btn btn-outline-light" type="button"
                            onclick="lwCopyToClipboard('lwWhatsAppQRImage{{ $loop->index }}')">
                            <?= __tr('Copy') ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <h3 class="text-muted">{{ __tr('WhatsApp URL') }}</h3>
                <div class="input-group">
                    <input type="text" class="form-control" readonly id="lwWhatsAppUrl{{ $loop->index }}"
                        value="https://wa.me/{{ cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']) }}">
                    <div class="input-group-append lw-whatsapp-group">
                        <button class="btn btn-outline-light" type="button"
                            onclick="lwCopyToClipboard('lwWhatsAppUrl{{ $loop->index }}')">
                            <?= __tr('Copy') ?>
                        </button>
                        <a type="button" class="btn btn-outline-success" target="_blank"
                            href="https://api.whatsapp.com/send?phone={{ cleanDisplayPhoneNumber($whatsappPhoneNumber['display_phone_number']) }}"><i
                                class="fab fa-whatsapp"></i> &nbsp;{{ __tr('WhatsApp Now') }}</a>
                    </div>
                </div>
            </div>
        </fieldset>
        @endforeach
        @else
        <div class="alert alert-info">{{ __tr('Please resync phone numbers.') }}</div>
        @endif
        @else
        <div class="text-danger">
            {{ __tr('Phone number does not configured yet.') }}
        </div>
        @endif
    </x-lw.modal>
    {{-- /QR CODE model --}}
    <template x-if="!disableSoundForMessageNotification">
        <audio id="lwMessageAlertTone">
            <source src="<?= asset('/static-assets/audio/whatsapp-notification-tone.mp3'); ?>" type="audio/mpeg">
        </audio>
    </template>

    {{-- Global View Stack --}}
    @stack('globalViewsStack')
    {{-- /Global View Stack --}}

    @endif
    <script>
        (function($) {
            'use strict';
            window.appConfig = {
                debug: "{{ config('app.debug') }}",
                csrf_token: "{{ csrf_token() }}",
                locale : '{{ app()->getLocale() }}',
                vendorUid : '{{ getVendorUid() }}',
                broadcast_connection_driver: "{{ getAppSettings('broadcast_connection_driver') }}",
                hide_contact_phone_number: "{{ hasVendorAccess('hide_contact_phone_numbers') }}",
                pusher : {
                    key : "{{ config('broadcasting.connections.pusher.key') }}",
                    cluster : "{{ config('broadcasting.connections.pusher.options.cluster') }}",
                    host : "{{ config('broadcasting.connections.pusher.options.host') }}",
                    port : "{{ config('broadcasting.connections.pusher.options.port') }}",
                    useTLS : "{{ config('broadcasting.connections.pusher.options.useTLS') }}",
                    encrypted : "{{ config('broadcasting.connections.pusher.options.encrypted') }}",
                    authEndpoint : "{{ url('/broadcasting/auth') }}"
                },
            }
        })(jQuery);
    </script>
    <?= __yesset(
        [
            'dist/js/jsware.js',
            'dist/js/app.js',
            // keep it last
            'dist/js/alpinejs.min.js',
        ],
        true,
    ) ?>
    @if(hasVendorAccess() or hasVendorUserAccess())
    {{-- app bootstrap --}}
    {!! __yesset('dist/js/bootstrap.js', true) !!}
    @endif
    @stack('vendorLibs')
    <script src="{{ route('vendor.load_server_compiled_js') }}"></script>
    @stack('footer')
    @stack('appScripts')
    <script>
        (function($) {
        'use strict';
        @if (session('alertMessage'))
            showAlert("{{ session('alertMessage') }}", "{{ session('alertMessageType') ?? 'info' }}");
            @php
                session('alertMessage', null);
                session('alertMessageType', null);
            @endphp
        @endif
        @php
        $isRestrictedVendorUser = (!hasVendorAccess() ? hasVendorAccess('assigned_chats_only') : false);
        @endphp
        var isRestrictedVendorUser = {{ $isRestrictedVendorUser ? 1 : 0 }},
            loggedInUserId = '{{ getUserId() }}';
        __Utils.setTranslation({
            'processing': "{{ __tr('processing') }}",
            'uploader_default_text': "<span class='filepond--label-action'>{!! __tr('Drag & Drop Files or Browse') !!}</span>",
            "confirmation_yes": "{{ __tr('Yes') }}",
            "confirmation_no": "{{ __tr('No') }}",
            "whatsapp_delete_msg_text": "{{ __tr('Messages before __date__ will be deleted') }}"
        });
        /* filepond translations */
        if(window['FilePond']) {
            FilePond.setOptions({
                labelInvalidField: '{{ __tr("Field contains invalid files") }}',
                labelFileWaitingForSize: '{{ __tr("Waiting for size") }}',
                labelFileSizeNotAvailable: '{{ __tr("Size not available") }}',
                labelFileCountSingular: '{{ __tr("file in list") }}',
                labelFileCountPlural: '{{ __tr("files in list") }}',
                labelFileLoading: '{{ __tr("Loading") }}',
                labelFileAdded: '{{ __tr("Added") }}',
                labelFileLoadError: '{{ __tr("Error during load") }}',
                labelFileRemoved: '{{ __tr("Removed") }}',
                labelFileRemoveError: '{{ __tr("Error during remove") }}',
                labelFileProcessing: '{{ __tr("Uploading") }}',
                labelFileProcessingComplete: '{{ __tr("Upload complete") }}',
                labelFileProcessingAborted: '{{ __tr("Upload cancelled") }}',
                labelFileProcessingError: '{{ __tr("Error during upload") }}',
                labelFileProcessingRevertError: '{{ __tr("Error during revert") }}',
                labelTapToCancel: '{{ __tr("tap to cancel") }}',
                labelTapToRetry: '{{ __tr("tap to retry") }}',
                labelTapToUndo: '{{ __tr("tap to undo") }}',
                labelButtonRemoveItem: '{{ __tr("Remove") }}',
                labelButtonAbortItemLoad: '{{ __tr("Cancel") }}',
                labelButtonRetryItemLoad: '{{ __tr("Retry") }}',
                labelButtonAbortItemProcessing: '{{ __tr("Cancel") }}',
                labelButtonUndoItemProcessing: '{{ __tr("Undo") }}',
                labelButtonRetryItemProcessing: '{{ __tr("Retry") }}',
                labelButtonProcessItem: '{{ __tr("Upload") }}'
            });
        };
        // push notification
        if (!Push.Permission.has()) {
            Push.Permission.request();
        }
        // register service worker for push notifications
        navigator.serviceWorker.register("{{ asset('dist/push-js/serviceWorker.min.js') }}");
        // check if the window tab is active
        var isWindowTabActive = true;
        $(window).on("blur focus", function(e) {
            var prevType = $(this).data("prevType");
            //  reduce double fire issues
            if (prevType != e.type) {
                switch (e.type) {
                    case "blur":
                        isWindowTabActive = false;
                        break;
                    case "focus":
                        isWindowTabActive = true;
                        break;
                };
            };
            $(this).data("prevType", e.type);
        });

        @if(hasVendorAccess() or hasVendorUserAccess())
            var broadcastActionDebounce,
                campaignActionDebounce,
                lastEventData,
                lastCampaignStatus,
                demoNumbers,
                isUnreadRequestInProgress = false;
                function arrayContains(arr, item) {
                    // Handle case where arr is null/undefined
                    if (arr == null) return false;
                    // Use the most compatible iteration method
                    var length = arr.length;
                    for (var i = 0; i < length; i++) {
                        if (arr[i] == item) {  // Loose equality comparison
                        return true;
                        }
                    }
                    return false;
                    }
                @if(isDemo())
                demoNumbers = @json(array_unique(array_filter(array_merge([config('__misc.demo_test_recipient_contact_number')], session('__demoAccountTestPhoneNumbers') ?: []))));
                @endif
            window.Echo.private(`vendor-channel.${window.appConfig.vendorUid}`).listen('.VendorChannelBroadcast', function (data) {
                // if the event data matched does not need to process it
                if(_.isEqual(lastEventData, data)) {
                    return true;
                }
                @if(isDemo())
                // prevent for other demo numbers to process
                    if(!arrayContains(demoNumbers, data.contactWaId)) {
                        return true;
                    }
                @endif
                if(!data.campaignUid && (!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                    // chat updates
                    if(isWindowTabActive && data.contactUid && $('[data-contact-uid=' + data.contactUid + ']').length) {
                        __DataRequest.get(__Utils.apiURL("{{ route('vendor.chat_message.data.read', ['contactUid', 'way']) }}{{ ((isset($assigned) and $assigned) ? '?assigned=to-me' : '') }}", {'contactUid': data.contactUid, 'way':'prepend'}),{}, function(responseData) {
                            __DataRequest.updateModels({
                                '@whatsappMessageLogs' : 'append',
                                'whatsappMessageLogs':responseData.client_models.whatsappMessageLogs
                            });
                            window.lwScrollTo('#lwEndOfChats', true);
                        });
                    } else if((!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                        // play the sound for incoming message notifications
                        if(data.isNewIncomingMessage && $('#lwMessageAlertTone').length) {
                            // play the sound
                            $('#lwMessageAlertTone')[0].play();
                            if (!isWindowTabActive) {
                                // show the notification
                            Push.create("{{ __tr('__siteName__ - New Message', [
                            '__siteName__' => getAppSettings('name')
                        ])}}", {
                                body: data.contactDescription,
                                icon: "{{ getAppSettings('small_logo_image_url') }}",
                                // timeout: 4000,
                                onClick: function () {
                                    window.focus();
                                    this.close();
                                }
                            });
                        };
                        };
                    };
                };

                lastEventData = _.cloneDeep(data);
                clearTimeout(broadcastActionDebounce);
                broadcastActionDebounce = setTimeout(function() {
                    // generic model updates
                    if(data.eventModelUpdate) {
                        __DataRequest.updateModels(data.eventModelUpdate);
                    }
                    @if(hasVendorAccess('messaging'))
                    if(!data.campaignUid && (!isRestrictedVendorUser || (isRestrictedVendorUser && (data.assignedUserId == loggedInUserId)))) {
                        // is incoming message
                        if(data.isNewIncomingMessage && !isUnreadRequestInProgress) {
                            isUnreadRequestInProgress = true;
                            __DataRequest.get("{{ route('vendor.chat_message.read.unread_count') }}",{}, function(responseData) {
                                isUnreadRequestInProgress = false;
                            });
                        };
                        // contact list update
                        if($('.lw-whatsapp-chat-window').length) {
                            __DataRequest.get(__Utils.apiURL("{!! route('vendor.contacts.data.read', ['contactUid','way' => 'append','request_contact' => '', 'assigned'=> ($assigned ?? '')]); !!}", {'contactUid': $('#lwWhatsAppChatWindow').data('contact-uid'),'request_contact' : 'request_contact=' + data.contactUid + '&'}),{}, function() {});
                        }
                    }
                    @endif
                }, 1000);
                @if(hasVendorAccess('messaging'))
                // 10 seconds for campaign
                    clearTimeout(campaignActionDebounce);
                    campaignActionDebounce = setTimeout(function() {
                        // campaign data update
                        if(data.campaignUid && $('.lw-campaign-window-' + data.campaignUid).length) {
                            __DataRequest.get(__Utils.apiURL("{{ route('vendor.campaign.status.data', ['campaignUid']) }}", {'campaignUid': data.campaignUid}),{}, function(responseData) {
                                if(responseData.data.campaignStatus != lastCampaignStatus) {
                                    window.reloadDT('#lwCampaignQueueLog');
                                }
                                lastCampaignStatus = responseData.data.campaignStatus;
                            });
                        };
                    }, 10000);
                @endif
                // stack for to process data whenever the vendor channel broadcasts through Pusher or Socket
                @stack('vendorChannelBroadcastStack')
                // Check if data reload event fired, if yes then reload page
                if (data.reload) {
                    location.reload();
                }
            });
        @if(hasVendorAccess('messaging'))
        // initially get the unread count on page loads
        __DataRequest.get("{{ route('vendor.chat_message.read.unread_count') }}",{}, function() {});
        @endif
    @endif
    })(jQuery);
    </script>
    {!! getAppSettings('page_footer_code_all') !!}
    @if(isLoggedIn())
    {!! getAppSettings('page_footer_code_logged_user_only') !!}
    @endif
</body>

</html>