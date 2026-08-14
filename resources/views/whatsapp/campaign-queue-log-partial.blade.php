<?php $logStatus = request()->route('logStatus'); ?>
<ul class="nav nav-tabs col-12 mb-3" id="myTab" role="tablist">
    @if(!__isEmpty($messageQueueStatusCodes))
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($logStatus == 'all' || __isEmpty($logStatus)) ? 'active' : '' ?>" href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'queue', 'logStatus' => 'all']) ?>#logData">
                <?= __tr('All') ?>
            </a>
        </li>
        @foreach($messageQueueStatusCodes as $queueStatusIndex => $queueStatusCode)
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= ($logStatus == $queueStatusIndex) ? 'active' : '' ?>" href="<?= route('vendor.campaign.status.view', ['campaignUid' => $campaignUid, 'pageType' => 'queue', 'logStatus' => $queueStatusIndex]) ?>#logData">
                    <?= $queueStatusCode ?>
                </a>
            </li>
        @endforeach
    @endif
</ul>

{{-- datatable queue log--}}
<x-lw.datatable lw-card-classes="border-0" data-page-length="100" id="lwCampaignQueueLog" data-url="{{ route('vendor.campaign.queue.log.list.view', ['campaignUid' => $campaignUid, 'logStatus' => $logStatus])}}">
    <th data-orderable="true" data-name="full_name">{{ __tr('Name') }}</th>
    {{-- <th data-orderable="true" data-name="last_name">{{ __tr('Last Name') }}</th> --}}
    <th data-orderable="true" data-name="phone_with_country_code">{{ __tr('Phone Number') }}</th>
    <th data-orderable="true" data-name="formatted_status">{{ __tr('Status') }}</th>
    <th data-orderable="true" data-order-by="true" data-order-type="desc" data-name="updated_at">{{ __tr('Last Status Updated at') }}</th>
    <th data-orderable="true" data-name="scheduled_at">{{ __tr('Scheduled at') }}</th>
    <th data-orderable="true" data-name="retries">{{ __tr('Auto Retried') }}</th>
    <th class="col-3 col-lg-4" data-template="#campaignActionColumnTemplate" data-name="null">{{ __tr('Messages') }}</th>
</x-lw.datatable>
 <!-- action template -->
 <script type="text/template" id="campaignActionColumnTemplate">
    <!--  status -->
    <% if (_.includes([1], __tData.status)) { %>
        <% if (__tData.whatsapp_message_error) { %>
        <span class="text-muted">{{ __tr('requeued and waiting ..') }}</span>
        <br><small class="text-danger"><%- __tData.whatsapp_message_error %></small>
        <% } else { %>
            <span class="text-muted">{{ __tr('waiting ..') }}</span>
        <% } %>
     <% } else if ((__tData.status == 2) || (__tData.status == 'failed')) { %>
         <span class="text-danger">{{ __tr('Failed') }}</span> <br>
        <small class="text-muted"><%- __tData.whatsapp_message_error %></small>
    <% } else if (__tData.status == 3) { %>
        <span class="text-muted">{{ __tr('processing ..') }}</span>
    <% } else if (__tData.status == 4) { %>
        <span class="text-danger">{{  __tr('Processed and waiting for response') }}</span>
    <% } else if (__tData.status == 5) { %>
        <span class="text-danger">{{  __tr('Expired before processing') }}</span>
    <% } else if (__tData.status == 6) { %>
        <span class="text-danger">{{  __tr("Processed but unknown status") }}</span>
    <% } else if (__tData.status == 7) { %>
        <span class="text-danger">{{  __tr("Stopped and Aborted") }}</span>
    <% } %>
</script>
            <!-- / status -->
{{-- /datatable queue log--}}