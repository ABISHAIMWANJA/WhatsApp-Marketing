{{-- datatable queue log--}}
<x-lw.datatable lw-card-classes="border-0" data-page-length="100" id="lwCampaignQueueLog" :url="route('vendor.campaign.expired.log.list.view', ['campaignUid' => $campaignUid])">
    <th data-orderable="true" data-name="full_name">{{ __tr('Name') }}</th>
    {{-- <th data-orderable="true" data-name="last_name">{{ __tr('Last Name') }}</th> --}}
    <th data-orderable="true" data-name="phone_with_country_code">{{ __tr('Phone Number') }}</th>
    <th data-orderable="true" data-order-by="true" data-order-type="desc" data-name="updated_at">{{ __tr('Last Status Updated at') }}</th>
    <th data-orderable="true" data-name="scheduled_at">{{ __tr('Scheduled at') }}</th>
    <th data-orderable="true" data-name="retries">{{ __tr('Auto Retried') }}</th>
    <th data-template="#campaignActionColumnTemplate" data-name="null">{{ __tr('Messages') }}</th>
</x-lw.datatable>
 <!-- action template -->
 <script type="text/template" id="campaignActionColumnTemplate">
    <!--  status -->
    <% if (__tData.whatsapp_message_error) { %>
        <small class="text-danger"><%- __tData.whatsapp_message_error %></small>
    <% } %>
</script>
            <!-- / status -->
{{-- /datatable queue log--}}