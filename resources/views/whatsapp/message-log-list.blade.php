@php
/**
* Component : WhatsApp
* Controller : WhatsAppServiceController
* File : whatsapp.message-log-list.blade.php
* ----------------------------------------------------------------------------- */
@endphp

@extends('layouts.app', ['title' => __tr('Message Log')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Message Log'),
'description' => '',
'class' => 'col-lg-7'
])
@push('head')
{!! __yesset('dist/css/whatsapp-chat.css', true) !!}
@endpush
@php
$type=request()->is_incoming_message;
// If $type is null, set it to a default value (e.g., 'all')
if (is_null($type)) {
    $type = 'all'; 
}
$startDate=request()->msg_start_date;
$endDate=request()->msg_end_date;
@endphp

<div class="container-fluid ">
    <div class="card ">
        <div class="card-body p-4">
            <form  id="lwMessageFilterForm" action="{{ route('vendor.whatsapp.message.log.view') }}" method="get" data-show-processing="true">
            <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-5">
                    <x-lw.input-field type="selectize" data-form-group-class=""
                        name="is_incoming_message" data-selected=""
                        :label="__tr('Select Message Type')" placeholder="{{ __tr('Select Message Type') }}" >
                        <x-slot name="selectOptions">
                            <option value="all">{{ __tr('All') }}</option>
                            <option value="1" @if(request('is_incoming_message') == '1') selected @endif>{{ __tr('Incoming') }}</option>
                            <option value="0" @if(request('is_incoming_message') == '0') selected @endif>{{ __tr('Outgoing') }}</option>
                        </x-slot>
                    </x-lw.input-field>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6 ">
                    <div class="row">
                        <div class="col-6 ">
                            <x-lw.input-field type="date" id="lwMsgDate" data-form-group-class=""
                                :label="__tr('Start Date')" name="msg_start_date" value="{{ $startDate }}" max="{{ now()->format('Y-m-d') }}"/>

                            @error('msg_start_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                        <div class="col-6">
                            <x-lw.input-field type="date" id="lwMsgDate" data-form-group-class="" value="{{ $endDate }}"
                                :label="__tr('End Date')" name="msg_end_date" max="{{ now()->format('Y-m-d') }}"/>

                            @error('msg_end_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-1">
                    <div class="lw-top-spacing-block">
                        <button type="submit" class="btn btn-primary btn-block">{{ __tr('Show') }}</button>
                    </div>
                </div>
            </div>
        </form>
        </div>

    </div>

    <div class="row">

        <!--datatable-->
        <div class="col-xl-12 mt-4">
            <x-lw.datatable id="lwMessageList" :url="route('vendor.whatsapp.message.log.list', [
                'isIncomingMsg' => $type ,'msgStartDate' => $startDate ?: 'any','msgEndDate' => $endDate ?: 'any'
            ])">
             
                <th data-orderable="true" data-name="recepient">{{ __tr('Recipient') }}</th>
                <th data-orderable="true" data-name="from">{{ __tr('From') }}</th>
                <th data-orderable="true" data-order-type="desc" data-order-by="true" data-name="messaged_at">{{ __tr('Messaged At') }}</th>
                <th data-orderable="true" data-name="is_incoming_message">{{ __tr('Type') }}</th>
                <th data-template="#messageCampaignColumnTemplate" name="null">{{ __tr('Via') }}</th>
                <th data-orderable="true" data-template="#messageStatusColumnTemplate" name="null">{{ __tr('Status') }}
                </th>
                <th data-template="#messageLogActionColumnTemplate" name="null">{{ __tr('Action') }}</th>

            </x-lw.datatable>
        </div>
         <!--datatable-->
        <!-- action template -->
        <script type="text/template" id="messageLogActionColumnTemplate">
            @if (isThisDemoVendorAccountAccess())
                <button class="btn btn-sm btn-default lw-disabled" disabled title="{{  __tr('Disabled for Demo') }}"><i class="fa fa-info-circle"></i> {{  __tr('Message') }}</button>
            @else
                <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Details') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwDetailsMessageBody" href="<%= __Utils.apiURL("{{ route('vendor.read.message.data', ['messageIdOrUid']) }}", {'messageIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwDetailsMessage"><i class="fa fa-info-circle"></i> {{  __tr('Message') }}</a>
            @endif
        </script>
        <script type="text/template" id="messageCampaignColumnTemplate">
                <% if( __tData.messageVia == 'bot') { %>
                     {{  __tr('Bot') }}
                <% } else if( __tData.messageVia == 'aibot') { %>
                   {{  __tr('AI Bot') }}
                <% } else if(__tData.messageVia && __tData.messageVia == 'whatsapp_call') { %>
                    {{  __tr('Whatsapp calling') }}
                <% } else if(__tData.messageVia && __tData.messageVia !== 'bot' && __tData.messageVia !== 'aibot') { %>
                    <a href="<%= __Utils.apiURL("{{ route('vendor.campaign.status.view', ['campaignUid' => 'campaignUid',]) }}", {'campaignUid': __tData.messageVia}) %>" class="campaign-text-color" title="{{ __tr('Campaign') }}">{{  __tr('Campaign') }}</a>
                <% } 
                else { %>
                    {{  ('-') }}
                <% } %>
            </script>
        <script type="text/template" id="messageStatusColumnTemplate">
          
            <% if(__tData.status == 'read') { %>
                <span ></i> {{  __tr('Read') }}</span>
            <% } else if(__tData.status == 'played') { %>
                <span ></i> {{  __tr('Played') }}</span>
            <% } else if(__tData.status == 'failed') { %>
                <span > {{  __tr('Failed') }}</span>
            <% } else if(__tData.status == 'accepted') { %>
                <span > {{  __tr('Accepted') }}</span>
            <% } else if(__tData.status == 'received') { %>
                <span > {{  __tr('Received') }}</span>
            <% } else if(__tData.status == 'delivered') { %>
                <span > {{  __tr('Delivered') }}</span>
            <% } else if(__tData.status == 'initialize') { %>
                <span > {{  __tr('Initialize') }}</span>
            <% } else if(__tData.status == 'sent') { %>
                <span > {{  __tr('Sent') }}</span>
            <% }
            else { %>
                <%- __tData.status  %>
            <% } %>
        </script>

        <!-- Details Message Modal -->
        <x-lw.modal id="lwDetailsMessage " :header="__tr('Message Details')">
            <!--  Details Message Form -->
            <!-- Details body -->
            <div id="lwDetailsMessageBody" class="lw-form-modal-body "></div>
            <script type="text/template" id="lwDetailsMessageBody-template">
                <!-- form fields -->
                <label class="small">{{ __tr('Message') }}:</label>
                <div class="lw-details-item w-75 m-auto">
                    <%= __tData.messageData.message %>
                        <% if (!_.isEmpty(__tData.messageData.template_message)) { %>
                            <%= __tData.messageData.template_message %>
                                <% } %>
                </div>
            </script>
            <!--/  Details Message Form -->
        </x-lw.modal>
        <!--/ Edit Message Modal -->
    </div>
</div>
@endsection()
@push('appScripts')
<?= __yesset([
            'dist/js/whatsapp-template.js',
        ],true,
) ?>
@endpush