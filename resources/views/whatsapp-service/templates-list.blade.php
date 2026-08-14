@php
/**
* Component : WhatsAppService
* Controller : WhatsAppServiceController
* File : templates.list.blade.php
----------------------------------------------------------------------------- */
@endphp
@extends('layouts.app', ['title' => __tr('Templates')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Templates'),
'description' => '',
'class' => 'col-lg-7'
])
<div class="container-fluid mt-lg--6">
    <div class="row">
        <!-- button -->
        <div class="col-xl-12 mb-3">
           <div class="float-right">
            @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
                <a class="lw-btn btn btn-primary" href="{{ route('vendor.whatsapp_service.templates.read.new_view') }}" > {{ __tr('Create New Template') }}</a>
            @endif
            <a class="lw-btn btn btn-dark lw-ajax-link-action" data-callback="reloadDtOnSuccess" data-method="post" href="{{ route('vendor.whatsapp_service.templates.write.sync') }}" > {{ __tr('Sync WhatsApp Templates') }}</a>
            @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
            <a class="lw-btn btn btn-default" target="_blank" href="https://business.facebook.com/wa/manage/message-templates/?waba_id={{ getVendorSettings('whatsapp_business_account_id') }}" > {{ __tr('Manage Templates on Meta') }} <i class="fas fa-external-link-alt"></i></a>
             @endif
             @if(hasVendorAccess('manage_campaigns'))
                <a class="lw-btn btn btn-secondary" href="{{ route('vendor.campaign.new.view') }}">{{ __tr('Create New Campaign') }}</a>
            @endif
           </div>
        </div>
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
        <x-lw.modal id="lwTemplatePreview" :header="__tr('Template Preview')" :modalSize="'modal-sm'" :hasForm="true" x-data="{selectedTemplate:''}">
            <!--  Edit Contact Form -->
            <div id="lwTemplatePreviewForm">
                <!-- form body -->
                <div id="lwTemplateStructureContainer" class="lw-form-modal-body"></div>
                <!-- form footer -->
                <div class="modal-footer" id="lwTemplateStructureContainerActions"></div>
                <script type="text/template" id="lwTemplateStructureContainerActions-template">
                    <% if(__tData.template_status == 'APPROVED') { %>
                    <a title="{{  __tr('Create Campaign using this template') }}" class="lw-btn btn btn-primary"  href="{{ route('vendor.campaign.new.view') }}?use_template=<%- __tData._uid %>"><i class="fa fa-bullhorn"></i> {{  __tr('Create Campaign') }}</a>
                    <% } %>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </script>
            </div>
            <!--/  Edit Contact Form -->
        </x-lw.modal>
        
        <div class="col-xl-12">
            <x-lw.datatable id="lwTemplatesList"  data-page-length="100" :url="route('vendor.whatsapp_service.templates.read.list')">
                <th data-orderable="true" data-name="template_name">{{ __tr('Name') }}</th>
                <th data-orderable="true" data-name="language">{{ __tr('Language') }}</th>
                <th data-orderable="true" data-name="category">{{ __tr('Category') }}</th>
                <th data-template="#templatesStatusColumnTemplate" name="null">{{ __tr('Status') }}</th>
                <th data-orderable="true" data-order-by="true" data-order-type="desc" data-name="updated_at">{{ __tr('Updated On') }}</th>
                <th data-template="#templatesActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
        </div>
        <script type="text/template" id="templatesStatusColumnTemplate">
            <% if(__tData.status == 'APPROVED') { %>
                <span class="text-success"><i class="fa fa-check-circle text-success"></i> {{  __tr('APPROVED') }}</span>
            <% } else if(__tData.status == 'REJECTED') { %>
                <span class="text-danger"><i class="fa fa-times-circle text-danger"></i> {{  __tr('REJECTED') }}</span>
            <% } else if(__tData.status == 'PENDING') { %>
                <span class="text-warning"><i class="fa fa-clock text-warning"></i> {{  __tr('PENDING') }}</span>
            <% } else { %>
                <%- __tData.status  %>
            <% } %>
        </script>
        <!-- action template -->
        <script type="text/template" id="templatesActionColumnTemplate">
            @if(hasVendorAccess('manage_campaigns'))
                <% if(__tData.status == 'APPROVED') { %>
                    <a title="{{  __tr('Create Campaign using this template') }}" class="lw-btn btn btn-sm btn-primary"  href="{{ route('vendor.campaign.new.view') }}?use_template=<%- __tData._uid %>"><i class="fa fa-bullhorn"></i> {{  __tr('Create Campaign') }}</a>
                <% } %>
            @endif
            {{-- preview template --}}
            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Template Preview') }}" class="lw-btn btn btn-sm btn-light lw-ajax-link-action" data-method="post" data-post-data="<%- toJsonString({'template_selection': __tData._uid,'template_status': __tData.status}) %>" data-response-template="#lwTemplateStructureContainerActions" href="{{ route('vendor.request.template.view') }}?only-preview=1"  data-toggle="modal" data-target="#lwTemplatePreview"><i class="fa fa-eye"></i> {{  __tr('Preview') }}</a>
            {{-- edit template --}}
            <div class="btn-group">
                @if(hasVendorAccess('manage_templates', 'add_edit_templates'))
                    <a title="{{  __tr('Edit Template') }}" class="lw-btn btn btn-sm btn-default" href="<%= __Utils.apiURL("{{ route('vendor.whatsapp_service.templates.read.update_view',['whatsappTemplateUid']) }}", {'whatsappTemplateUid': __tData._uid}) %>">{{  __tr('Edit') }}</a>
                @endif
                <button type="button" class="btn btn-sm btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-expanded="false">
                  <span class="sr-only">{{  __tr('Toggle Dropdown') }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a target="_blank" title="{{  __tr('Edit Template on Meta') }}" class="dropdown-item" href="https://business.facebook.com/wa/manage/message-templates/?&waba_id={{ getVendorSettings('whatsapp_business_account_id') }}&id=<%- __tData.template_id %>">{{  __tr('Edit Template on Meta') }} <i class="fas fa-external-link-alt"></i></a>
                </div>
              </div>
            @if(hasVendorAccess('manage_templates', 'delete_templates'))
                <a title="{{  __tr('Delete Template') }}" data-callback="reloadDtOnSuccess" data-method="post" data-confirm="#lwConfirmTemplateDelete" data-confirm-params="<%- toJsonString({'templateName': __tData.template_name}) %>" class="lw-btn btn btn-sm btn-danger lw-ajax-link-action" href="<%= __Utils.apiURL(" {{ route('vendor.whatsapp_service.templates.write.delete',['whatsappTemplateUid']) }}", {'whatsappTemplateUid': __tData._uid}) %>">{{  __tr('Delete') }}</a>
            @endif
            
            @if(getVendorSettings('template_analytics_status') == 'ENABLED' and hasVendorAccess('manage_templates'))
                <% if(__tData.status == 'APPROVED') { %>
                    <a title="{{  __tr('Show Analytics') }}" class="lw-btn btn btn-sm btn-primary"  href="<%= __Utils.apiURL(" {{ route('vendor.whatsapp_service.templates.read.analytics',['templateUid']) }}", {'templateUid': __tData._uid}) %>"><i class="fas fa-chart-line"></i> {{  __tr('Show Analytics') }}</a>
                <% } %>
            @endif
        </script>
        <!-- /action template -->
        <script type="text/template" id="lwConfirmTemplateDelete">
            <h3>{!! __tr('Are you sure you want to delete __templateName__ template', [
                '__templateName__' => '<strong><%- __tData.templateName %></strong>'
                ]) !!}</h3>
        </script>
    </div>
</div>
@endsection()
@push('appScripts')
<?= __yesset([
            'dist/js/whatsapp-template.js',
        ],true,
) ?>
@endpush