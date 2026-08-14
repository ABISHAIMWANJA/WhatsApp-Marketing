<!-- Quick view modal -->
<x-lw.modal id="lwVendorQuickViewModal" :header="__tr('Vendor Details')" :hasForm="false">
    <!-- FORM BODY -->
    <div id="lwVendorQuickViewBody" class="lw-form-modal-body"></div>
    <script type="text/template" id="lwVendorQuickViewBody-template">
        <ol class="list-group list-group-numbered">
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Vendor Title') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData?.vendorInfo?.title %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Total Contacts') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.totalContacts %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Total Contact Groups') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.totalGroups %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Total Campaign') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.totalCampaigns %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Total Templates') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.totalTemplates %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Total Active Bots') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.totalBotReplies %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Active Team Members') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.activeTeamMembers %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Messages In Queue') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.messagesInQueue %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('Messages Processed') }}</div>
                </div>
                <span class="text-bg-primary">
                    <%- __tData.vendorDashboardData.totalMessagesProcessed %>
                </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-start">
                <div class="ms-2 me-auto">
                <div class="fw-bold">{{ __tr('WhatsApp Cloud API Setup') }}</div>
                </div>
                <span class="<%- __tData.vendorDashboardData.whatsappSetupStatus ? 'text-success' : 'text-danger' %>">
                    <%- __tData.vendorDashboardData.whatsappSetupStatusMessage %>
                </span>
            </li>
        </ol>
    </script>
</x-lw.modal>
<!--/ Quick view modal -->