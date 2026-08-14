@extends('layouts.app', ['title' => __tr('Campaign Status')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Campaign Dashboard'),
'description' => '',
'class' => 'col-lg-7'
])
@php
$campaignData = $campaign->__data;
$selectedGroups = Arr::get($campaignData, 'selected_groups', []);
$isRestrictByTemplateContactLanguage = Arr::get($campaignData, 'is_for_template_language_only');
$isAllContacts = Arr::get($campaignData, 'is_all_contacts');
// $messageLog = $campaign->messageLog;
// $queueMessages = $campaign->queueMessages;
$campaignUid=$campaign->_uid;
@endphp

<div class="container-fluid mt-lg--6 lw-campaign-window-{{ $campaign->_uid }}" x-cloak x-data="initialRequiredData">
    <div class="row" x-data="{ failedCampaignType: '', modalHeader: '', campaignId: '{{ $campaign->_id }}', recampaignType: '' }">
        <!-- button -->
        <div class="col-12 mb-3">
            <div class="float-right">
                <a class="lw-btn btn btn-light" href="{{ route('vendor.campaign.read.list_view') }}">{{ __tr('Back to Campaigns') }}</a>
                <a class="lw-btn btn btn-primary" href="{{ route('vendor.campaign.new.view') }}">{{ __tr('Create New Campaign') }}</a>
                <a class="lw-btn btn btn-secondary " href="{{ route('vendor.campaign.new.view', ['campaignType' => 'non-template']) }}">{{ __tr('Create New Non-Template Campaign') }}</a>
            </div>
        </div>
        <!--/ button -->
        <div class="col-12 mb-4">
            <div class="card card-stats mb-4 mb-xl-0 ">
                <div class="card-body ">
                    <div class="row">
                        <div class="col">
                            <span class="float-right">
                                <span class="rounded py-1 px-3 bg-warning text-white mb-2" x-text="statusText"></span>
                                @if($campaign->status == 5) <span class="rounded py-1 px-3 badge-dark text-white mb-2">{{  __tr('Archived') }}</span> @endif
                            </span>
                            <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Campaign Name') }}</h5>
                            <span class="h2 font-weight-bold mb-0">{{ $campaign->title }}</span>
                            <p class="mt-3 mb-0 text-muted text-sm">
                             <template x-if="campaignStatus == 'executed'">
                                <em class="d-block text-muted mb-2">
                                    {!! __tr('Overall sending messages process took __time__  from scheduled time including requeued messages etc.', [
                                        '__time__' => '<strong x-text="timeTookFromScheduledAtFormatted"></strong>'
                                    ]) !!}
                                </em>
                            </template>
                             <template x-if="campaignStatus == 'processing'">
                                <em class="d-block text-muted mb-2">
                                    {!! __tr('Sending messages are in process it took __time__ for the processed messages until now, __contactsInQueue__ contact(s) still in queue', [
                                        '__time__' => '<strong x-text="timeTookFromScheduledAtFormatted"></strong>',
                                        '__contactsInQueue__' => '<strong x-text="__Utils.formatAsLocaleNumber(inQueuedCount)"></strong>'
                                    ]) !!}
                                </em>
                            </template>
                            <h3 class="mr-2"><span class="text-success">{{ __tr('Execution Scheduled at') }}</span>
                            <h3>
                                @if(data_get($campaign->__data, 'send_message_via_marketing_message_api'))
                                    <span class="text-default">{{ __tr('Messages send via: Marketing Massages API (Marketing Lite)') }}</span>
                                @else
                                    <span class="text-default">{{ __tr('Messages send via: WhatsApp Cloud API') }}</span>
                                @endif
                            </h3>
                            @if ($campaign->timezone and getVendorSettings('timezone') != $campaign->timezone)
                            <div class="">{!! __tr('__scheduledAt__ as per your account timezone which is __selectedTimezone__', [
                                '__scheduledAt__' => formatDateTime($campaign->scheduled_at),
                                '__selectedTimezone__' => '<strong>'. getVendorSettings('timezone') .'</strong>'
                                ]) !!} </div>
                            <div class=" text-muted">{!! __tr('__scheduledAt__ as per the __selectedTimezone__ timezone', [
                                '__scheduledAt__' => formatDateTime($campaign->scheduled_at, null, null,
                                $campaign->timezone),
                                '__selectedTimezone__' => '<strong>'. $campaign->timezone .'</strong>'
                                ]) !!}</div>
                            @else
                            <span class="text-nowrap">{{ formatDateTime($campaign->scheduled_at) }}</span>
                            @endif

                            {{-- Abort Campaign --}}
                            <template x-if="campaignStatus == 'processing'">
                                <a class="lw-ajax-link-action-via-confirm lw-btn btn btn-danger" data-confirm="#lwCampaignAbort-template" data-method="post" href="{{ route('vendor.campaign.write.abort', ['campaignIdOrUid' => $campaign->_uid]) }}" data-callback="__Utils.viewReload"><i class="fas fa-ban"></i> {{ __tr('Abort') }}</a>
                            </template>
                            {{-- /Abort Campaign --}}

                            @if ($campaign->scheduled_at > now())
                            <div class="text-warning">{{ formatDiffForHumans($campaign->scheduled_at, 3) }}</div>
                            @else
                            <template x-if="(executedCount == 0) && inQueuedCount && !totalFailed">
                                <div class="text-warning my-3">
                                    {{  __tr('Awaiting execution') }} <i class="fa fa-spin fa-spinner"></i>
                                </div>
                            </template>
                            @endif
                            </h3>
                            @if(!__isEmpty(data_get($campaign, '__data.expiry_at')))
                                <div class="text-danger mt-3">{{ __tr('Expire On:') }} {{ formatDateTime(data_get($campaign, '__data.expiry_at')) }}</div>
                            @endif

                            </p>
                            <div class="my-3">
                                @if ($campaign->template_name)
                                <h5 class="card-title text-uppercase text-muted mb-2">{{ __tr('template Name') }} ({{ __tr('language') }})</h5>
                                <span class="h3 font-weight-bold mb-2">{{ $campaign->template_name }} (<span class="h3 font-weight-bold mb-2">{{ $campaign->template_language }}</span>)</span>
                                </h5>
                                @endif
                                @if ($campaign->__data['preset_message_name'] ?? null)
                                <h5 class="card-title text-uppercase text-muted mb-2">{{ __tr('Preset Message Name') }}</h5>
                                <span class="h3 font-weight-bold mb-2">{{ $campaign->__data['preset_message_name'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="row mb-4">
                {{-- total contacts --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Contacts') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="totalContacts"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                @if ($isAllContacts)
                                {{ __tr('All contacts') }}
                                @else
                                {{ __tr('All contacts from: ') }}
                                @foreach ($selectedGroups as $selectedGroup)
                                <strong class="text-nowrap text-warning">{{ $selectedGroup['title'] }}</strong>
                                @endforeach
                                {{ __tr('groups.') }}
                                @endif
                                @if ($isRestrictByTemplateContactLanguage)
                                <span class="">{!! __tr('Excluding those contacts which don\'t have __languageCode__
                                    language', [
                                    '__languageCode__' => "<span class='text-warning'>". e($campaign->template_language)
                                        ."</span>"
                                    ]) !!}</span>
                                @endif
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'total'; $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for All Contacts', ['__campaignTitle__' => $campaign->title]) }}');" :disabled="!['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /total contacts --}}
                {{-- delivered to --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Delivered') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="totalDeliveredInPercent"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(totalDelivered)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'delivered', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Delivered Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="totalDelivered == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /delivered to --}}
                {{-- read by --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Read') }}</h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="totalReadInPercent"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(totalRead)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'read', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Read Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="totalRead == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /read by --}}
                {{-- failed --}}
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Failed') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="__Utils.formatAsLocaleNumber(totalFailedInPercent)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(totalFailed)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'failed', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Failed Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="totalFailed == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /failed --}}
                {{-- expired --}}
                <div class="col-xl-3 col-lg-4 col-md-6 mt-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('Total Expired') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="__Utils.formatAsLocaleNumber(totalExpiredInPercent)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(expiredCount)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'expired', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Expired Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="expiredCount == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /expired --}}
                {{-- Total Sent --}}
                <div class="col-xl-3 col-lg-4 col-md-6 mt-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('In Sent') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="__Utils.formatAsLocaleNumber(totalSentInPercent)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-paper-plane"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(totalSent)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'sent', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Sent Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="totalSent == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /Total Sent --}}
                {{-- In Queue --}}
                <div class="col-xl-3 col-lg-4 col-md-6 mt-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('In Queue') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="__Utils.formatAsLocaleNumber(totalInQueueInPercent)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-hourglass-end"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(inQueueCount)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'in_queue', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for In Queue Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="inQueueCount == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /In Queue --}}
                {{-- Accepted --}}
                <div class="col-xl-3 col-lg-4 col-md-6 mt-4">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">{{ __tr('In Accepted') }}
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" x-text="__Utils.formatAsLocaleNumber(totalAcceptedInPercent)"></span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                        <i class="fas fa-pause"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap" x-text="__Utils.formatAsLocaleNumber(acceptedCount)"></span>
                                <span class="text-nowrap">{{ __tr('Contacts') }}</span>
                                @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                    <div class="float-right">
                                        <button type="button" x-on:click="recampaignType = 'accepted', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Accepted Contacts', ['__campaignTitle__' => $campaign->title]) }}')" :disabled="acceptedCount == 0 || !['executed', 'aborted'].includes(campaignStatus)" data-toggle="modal" data-target="#lwAddNewGroup" class="lw-btn btn btn-sm btn-primary" href="#">{{ __tr('Recampaign') }}</button>
                                    </div>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                {{-- /Accepted --}}
            </div>
            {{-- message log --}}
              <div class="row">
                <!--start of tabs-->
        <ul class="nav nav-tabs col-12 pr-5 mb-1" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $pageType == "queue" ? 'active' : '' ?>"
                   href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'queue', 'logStatus' => 'all']) ?>#logData">
                    <?= __tr('Queue') ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $pageType == "executed" ? 'active' : '' ?>"
                   href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'executed', 'logStatus' => 'all']) ?>#logData">
                    <?= __tr('Executed') ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $pageType == "expired" ? 'active' : '' ?>"
                   href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'expired']) ?>#logData">
                    <?= __tr('Expired') ?>
                </a>
            </li>
            <li class="nav-item text-right col my-2">
                @if (($pageType == "queue") and ($campaign->status == 1))
                <template x-if="campaignStatus == 'executed' && (queueFailedCount > 0)">
                <a class="btn btn-warning btn-sm lw-ajax-link-action" data-confirm="#requeueFailedMessageConfirm-template" data-method="post" href="{{ route('vendor.campaign.requeue.log.write.failed', [
                    'campaignUid' => $campaignUid
                ]) }}"><i class="fa fa-redo-alt"></i> {{  __tr('Requeue Failed Message') }}</a>
                 </template>
                @endif
                <button @click="window.reloadDT('#lwCampaignQueueLog');" class="btn btn-dark btn-sm"><i class="fa fa-sync"></i> {{  __tr('Refresh') }}</button>
                @if($campaignStatus=="executed")
                    @if($pageType== "queue")
                        <a href="{{ route('vendor.campaign.queue.log.report.write',['campaignUid' => $campaignUid ] 
                        ) }}" data-method="post" class="btn btn-dark btn-sm"><i class="fa fa-download"></i> {{  __tr('Report') }}</a>

                        @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                            <button type="button" x-on:click="failedCampaignType = 'queue', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Queue Contacts', ['__campaignTitle__' => $campaign->title]) }}')" data-toggle="modal" data-target="#lwAddNewGroup" :disabled="totalFailed == 0" :title="totalFailed == 0 ? '{{ __tr('No failed contacts are available in the Queue tab') }}' : ''" class="btn btn-primary btn-sm">{{  __tr('Recampaign') }}</button>
                        @endif
                    @elseif($pageType== "executed")
                        <a href="{{ route('vendor.campaign.executed.report.write',['campaignUid' => $campaignUid ] 
                            ) }}" data-method="post" class="btn btn-dark btn-sm"><i class="fa fa-download"></i> {{  __tr('Report') }}</a>

                        @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                            <button type="button" x-on:click="failedCampaignType = 'executed', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Executed Contacts', ['__campaignTitle__' => $campaign->title]) }}')" data-toggle="modal" data-target="#lwAddNewGroup" :disabled="totalDelivered == 0" :title="totalDelivered == 0 ? '{{ __tr('No contacts are available in the Executed tab') }}' : ''" class="btn btn-primary btn-sm">{{  __tr('Recampaign') }}</button>
                        @endif
                    @elseif($pageType== "expired")
                        <a href="{{ route('vendor.campaign.expired.log.report.write',['campaignUid' => $campaignUid ] 
                            ) }}" data-method="post" class="btn btn-dark btn-sm"><i class="fa fa-download"></i> {{  __tr('Report') }}</a>

                        @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                            <button type="button" x-on:click="failedCampaignType = 'expired', $dispatch('set-modal-title', '{{ __tr('__campaignTitle__ : Create New Group for Expired Contacts', ['__campaignTitle__' => $campaign->title]) }}')" data-toggle="modal" data-target="#lwAddNewGroup" :disabled="expiredCount == 0" :title="expiredCount == 0 ? '{{ __tr('No contacts are available in the Expired tab') }}' : ''" class="btn btn-primary btn-sm">{{  __tr('Recampaign') }}</button>
                        @endif
                    @endif
                @endif
                {{-- </template> --}}
            </li>
        </ul>
        <!--/end of tabs -->
              </div>
        <!-- tab Container -->
        <div class="row">
            <div class="col-12 mb-4 lw-nested-nav-tabs-container" id="logData">
                @if($pageType== "queue")
                    @include('whatsapp.campaign-queue-log-partial')
                    @elseif($pageType== "executed")
                    @include('whatsapp.campaign-executed-log-partial')
                    @elseif($pageType== "expired")
                    @include('whatsapp.campaign-expired-log-partial')
                    @endif
            </div>
        </div>
        <script type="text/template" id="requeueFailedMessageConfirm-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want requeue all the failed messages to process it again?') }}</p>
        </script>
        </div>

        <!-- Add New Group Modal -->
        <x-lw.modal id="lwAddNewGroup" :header="__tr('Add New Group')" :hasForm="true">
            <!--  Add New Group Form -->
            <x-lw.form id="lwAddNewGroupForm" :action="route('vendor.contact.group.write.create')"
                :data-callback-params="['modalId' => '#lwAddNewGroup', 'datatableId' => '#lwGroupList']"
                data-callback="appFuncs.modelSuccessCallback">
                <!-- form body -->
                <div class="lw-form-modal-body">

                    <input type="hidden" x-model="failedCampaignType" name="failed_campaign_type">
                    <input type="hidden" x-model="recampaignType" name="recampaign_type">
                    <input type="hidden" x-model="campaignId" name="campaign_id">

                    <div class="alert alert-warning">
                        {{  __tr('The new group only will be created for contacts available.') }}
                    </div>

                    <!-- form fields form fields -->
                    <!-- Title -->
                    <x-lw.input-field type="text" id="lwTitleField" data-form-group-class="" :label="__tr('Title')" name="title" required="true" />
                    <!-- /Title -->
                    <!-- Description -->
                    <div class="form-group">
                        <label for="lwDescriptionField">{{ __tr('Description') }}</label>
                        <textarea cols="10" rows="3" id="lwDescriptionField" class="lw-form-field form-control"
                            placeholder="{{ __tr('Description') }}" name="description"></textarea>
                    </div>
                    <!-- /Description -->
                </div>
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
            </x-lw.form>
            <!--/  Add New Group Form -->
        </x-lw.modal>
        <!--/ Add New Group Modal -->
    </div>
</div>
@php
$totalContacts = (int) Arr::get($campaignData, 'total_contacts');
@endphp

<!-- Campaign abort template -->
<script type="text/template" id="lwCampaignAbort-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to abort this campaign?') }}</p>
</script>
<!-- /Campaign abort template -->

<script>
    (function() {
        'use strict';
        document.addEventListener('alpine:init', () => {
            Alpine.data('initialRequiredData', () => ({
                timeTookFromScheduledAtFormatted:'',
                totalContacts:'{{ __tr($totalContacts) }}',
                totalDeliveredInPercent:'',
                totalDelivered:'',
                totalRead:'',
                totalReadInPercent:'',
                totalFailed:'',
                totalFailedInPercent:'',
                executedCount:'',
                inQueuedCount:"",
                statusText:'',
                campaignStatus:'',
                queueFailedCount:'',
                expiredCount:'',
                totalExpiredInPercent:'',
                totalSent: '',
                totalSentInPercent: '',
                inQueueCount: '',
                totalInQueueInPercent: '',
                acceptedCount: '',
                totalAcceptedInPercent: ''
            }));
        });
    })();
</script>
@push('appScripts')
<script>
(function($) {
    'use strict';
    // initial request
    __DataRequest.get("{{ route('vendor.campaign.status.data', ['campaignUid' => $campaignUid ]) }}");

    $('#lwAddNewGroup').on('hidden.bs.modal', function (hiddenEvent) {
        var $targetForm = $('#lwAddNewGroupForm');
        $targetForm[0].reset();
        $targetForm.find('input[name="failed_campaign_type"]').val('');
        $targetForm.find('input[name="recampaign_type"]').val('');
        var validator = $targetForm.validate();
        validator.resetForm();
    });
})(jQuery);
</script>
@endpush
@endsection()