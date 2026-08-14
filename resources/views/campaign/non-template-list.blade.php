@php
/**
* Component : Campaign
* Controller : CampaignController
* File : campaign.list.blade.php
* -----------------------------------------------------------------------------
*/
@endphp
@extends('layouts.app', ['title' => __tr('Templates')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Templates'),
'description' => '',
'class' => 'col-lg-7'
])

<?php $status = request()->status ?? 'active'; ?>
<div class="container-fluid mt-lg--6">
    <div class="row" x-data="{isAdvanceBot:'interactive',botFlowUid:null,ntCampaignPresetMessage:'yes'}">
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown"
                            aria-expanded="false">
                            {{ __tr('Add New Preset Message') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a type="button" @click="isAdvanceBot = 'simple'" data-response-template="#lwAddBotReplyBody" class="dropdown-item btn lw-ajax-link-action"
                                href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply"> {{ __tr('Simple Preset Message')
                                }}</a>
                            <a type="button" @click="isAdvanceBot = 'media'" data-response-template="#lwAddBotReplyBody" class="dropdown-item btn lw-ajax-link-action"
                                href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply"> {{ __tr('Media Preset Message')
                                }}</a>
                            <a type="button" @click="isAdvanceBot = 'interactive'" data-response-template="#lwAddBotReplyBody" class="dropdown-item btn lw-ajax-link-action"
                                href="{{ route('vendor.ping_pong.read')}}" data-toggle="modal" data-target="#lwAddNewAdvanceBotReply"> {{ __tr('Advance Interactive Preset Message') }}</a>
                        </div>
                    </div>
                @endif
                @if(hasVendorAccess('manage_campaigns'))
                    <a class="lw-btn btn btn-secondary " href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}">{{ __tr('Create New Non-Template Campaign') }}</a>
                @endif
            </div>
        </div>
        @if(!hasVendorAccess('manage_templates', 'add_edit_templates') and !hasVendorAccess('manage_campaigns'))
            <div class="col-xl-12 mb-3"></div>
        @endif
        <!--/ button -->
       <ul class="nav nav-tabs">
        <!-- Active tab -->
            <li class="nav-item">
                <a class="nav-link {{ markAsActiveLink('vendor.whatsapp_service.templates.read.list_view') }}" data-title="{{ __tr('Active') }}" href="<?= route('vendor.whatsapp_service.templates.read.list_view') ?>">
                    <?= __tr('WhatsApp Templates') ?>
                </a>
            </li>
            <!-- /Active tab -->

            <li class="nav-item">
                <a class="nav-link {{ markAsActiveLink('vendor.campaign.read.non_template_list_view') }}" data-title="{{ __tr('Preset Messages') }}" href="<?= route('vendor.campaign.read.non_template_list_view') ?>">
                    <?= __tr('Preset Messages') ?>
                </a>
            </li>
        </ul>
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <strong>{{  __tr('Please note:') }}</strong> {{ __tr('Preset Messages are reusable messages that can be used in campaigns as templates. You can create and manage your preset messages here. Preset messages can only be delivered to 24 hours service window opened contacts.') }}
                </div>
            </div>
            <x-lw.datatable data-page-length="100" id="lwBotReplyList" :url="route('vendor.campaign.read.non_template_message_preset_list', ['status' => $status])">
                <th data-orderable="true" data-name="name">{{ __tr('Name') }}</th>
                <th data-name="bot_type">{{ __tr('Message Type') }}</th>
                <th data-orderable="true" data-name="created_at">{{ __tr('Created At') }}</th>
                <th data-template="#botReplyActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
        </div>
        <!-- action template -->
        <script type="text/template" id="campaignActionColumnTemplate">
        <a href="<%= __Utils.apiURL("{{ route('vendor.campaign.status.view', ['campaignUid' => 'campaignUid',]) }}", {'campaignUid': __tData._uid}) %>" class="btn btn-dark btn-sm" title="{{ __tr('Campaign Details') }}"><i class="fa fa-tachometer"></i> {{  __tr('Campaign Dashboard') }}</a>
<!--  Delete Action -->
<% if(__tData.delete_allowed) { %>
<a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.delete', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteCampaign-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
<% } else { %>
    <!--  Archived button -->
    <% if(__tData.status != 5) { %>
        <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.archive', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action" title="{{ __tr('Archive') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" data-callback="appFuncs.modelSuccessCallback">{{  __tr('Archive') }}</a>
        <% } else { %>
             <!--  /Archived button -->
            <!--  UnArchived button -->
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.campaign.write.unarchive', [ 'campaignIdOrUid']) }}", {'campaignIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action" title="{{ __tr('Unarchive') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwCampaignList']) }}" data-callback="appFuncs.modelSuccessCallback">{{  __tr('Unarchive') }}</a>

            <% } %>
               <!--  /UnArchived button -->

    <% } %>
    </script>
        <!-- /action template -->
        <!-- action template -->
        <script type="text/template" id="campaignStatusColumnTemplate">
<!--  status -->
<% if(__tData.delete_allowed) { %>
    <span class="badge badge-success"><%- __tData.scheduled_status %></span>
<% } else { %>
    <span class="badge badge-warning p-2"><%- __tData.scheduled_status %></span>
<% } %>
    </script>
    <!-- /status template -->

    <!-- Campaign delete template -->
    <script type="text/template" id="lwDeleteCampaign-template">
        <h2>{{ __tr('Are You Sure!') }}</h2>
        <p>{{ __tr('You want to delete this Campaign?') }}</p>
    </script>
    <!-- /Campaign delete template -->
    @include('bot-reply.bot-forms-partial')
    <!-- action template -->
    <script type="text/template" id="botReplyActionColumnTemplate">
        @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Edit') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditBotReplyBody" href="<%= __Utils.apiURL("{{ route('vendor.bot_reply.read.update.data', [ 'botReplyIdOrUid', 'page_type' => 'preset_message']) }}", {'botReplyIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditBotReply"><i class="fa fa-edit"></i> {{  __tr('Edit') }}</a>
        @endif
        @if(hasVendorAccess('manage_templates', 'delete_templates'))
        <!--  Delete Action -->
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.bot_reply.write.delete', [ 'botReplyIdOrUid', 'page_type' => 'preset_message']) }}", {'botReplyIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteBotReply-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwBotReplyList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
        @endif
        @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
        <!--  Duplicate Action -->
            <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.bot_reply.write.duplicate', [ 'botReplyIdOrUid', 'page_type' => 'preset_message']) }}", {'botReplyIdOrUid': __tData._uid}) %>" class="btn btn-light btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDuplicateBotReply-template" title="{{ __tr('Duplicate') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwBotReplyList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-copy"></i> {{  __tr('Duplicate') }}</a>
        @endif
    </script>
    <!-- /action template -->

    <!-- Preset Message delete template -->
    <script type="text/template" id="lwDeleteBotReply-template">
        <h2>{{ __tr('Are You Sure!') }}</h2>
        <p>{{ __tr('You want to delete this Preset Message?') }}</p>
    </script>
        <!-- /Preset Message delete template -->

         <!-- Preset Message duplicate template -->
        <script type="text/template" id="lwDuplicateBotReply-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to duplicate this Preset Message?') }}</p>
    </script>
        <!-- /Preset Message duplicate template -->
    </div>
</div>
@endsection()