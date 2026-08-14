@php
/**
* Component : Contact
* Controller : ContactController
* File : contact.list.blade.php
* ----------------------------------------------------------------------------- */
$currentGroup = $groupUid ? $vendorContactGroups->where('_uid', $groupUid)->first() : null;
@endphp
@extends('layouts.app', ['title' => __tr('Contacts')])
@section('content')
@include('users.partials.header', [
'title' => $groupUid ? __tr('__groupName__ group contacts', [
'__groupName__' => $currentGroup->title
]) : __tr('Contacts'),
// 'description' => $groupUid ? $currentGroup->description : '',
'class' => 'col-lg-7'
])
@php
$groupDescription = $groupUid ? $currentGroup->description : '';
@endphp
<div class="container-fluid mt-lg--6" x-data="initialContactsInfoData">
    <div class="row">
        <!-- button -->
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                @if ($groupUid)
                    <a class="lw-btn btn btn-secondary" href="{{ route('vendor.contact.group.read.list_view') }}">{{
                        __tr('Back to Contact Groups') }}</a>
                    <a class="lw-btn btn btn-secondary" href="{{ route('vendor.contact.read.list_view') }}">{{
                        __tr('Back to Contacts') }}</a>
                @endif
                @if(hasVendorAccess('manage_contacts', 'add_edit_contacts'))
                    <button type="button" class="lw-btn btn btn-primary" data-toggle="modal" data-target="#lwAddNewContact">
                        {{ __tr('Create New Contact') }}
                    </button>
                @endif
                @if (!$groupUid)
                    @if(hasVendorAccess('manage_contacts', 'export_contacts'))
                        <button type="button" class="lw-btn btn btn-dark" data-toggle="modal" data-target="#lwExportDialog"> <i class="fa fa-download"></i> {{ __tr('Download Contacts') }}</button>
                    @endif
                    @if(hasVendorAccess('manage_contacts', 'import_contacts'))
                        <button type="button" class="lw-btn btn btn-dark" data-toggle="modal"
                            data-target="#lwImportContactDialog"><i class="fa fa-upload"></i> {{ __tr('Upload Contacts') }}
                        </button>
                    @endif
                @endif
            </div>
        </div>

        @if(!hasVendorAccess('manage_contacts', 'add_edit_contacts') 
            and !hasVendorAccess('manage_contacts', 'export_contacts') 
            and !hasVendorAccess('manage_contacts', 'import_contacts'))
            <div class="col-xl-12 mt-4"></div>
        @endif

        <!--/ button -->
        {{-- import contacts --}}
        <x-lw.modal id="lwImportContactDialog" :header="__tr('Upload Contacts')" :hasForm="true"
            data-pre-callback="appFuncs.clearContainer">
            <x-lw.form id="lwImportContactDialogForm" :action="route('vendor.contact.write.import')"
                :data-callback-params="['modalId' => '#lwImportContactDialog', 'datatableId' => '#lwContactList']"
                data-callback="window.onImportProcessUpdate">
                <div class="lw-form-modal-body p-3">
                <div x-cloak class="text-center" x-show="existingImportRequestData.progress">
                    <h1 class="text-success" x-text="existingImportRequestData.progressCountFormatted"></h1>
                    <h4 x-show="existingImportRequestData.estimatedRemainingTime" class="text-muted">{{  __tr('Estimated time remaining') }}</h4>
                    <h4 class="text-info" x-show="existingImportRequestData.estimatedRemainingTime" x-text="existingImportRequestData.estimatedRemainingTime"></h4>
                    <h3>{{  __tr('Please wait ... contacts import is in progress') }}</h3>
                    <div class="progress" role="progressbar" aria-label="{{ __tr('contacts import in progress') }}" :aria-valuenow="existingImportRequestData.progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" :style="{'width':existingImportRequestData.progress + '%'}" style="width: 0%"></div>
                    </div>
                    <div>
                        <a class="lw-ajax-link-action btn btn-danger btn-sm" data-confirm="<?= __tr('Are you sure you want to abort importing contacts?') ?>" href="{{ route('vendor.contact.write.abort_import') }}" data-callback="__Utils.viewReload" role="button" data-method="post">{{  __tr('Abort') }}</a>
                        <div>
                            <small class="text-muted">{{  __tr('In case process stuck at some point, please reload page.') }}</small>
                        </div>
                    </div>
                </div>
                {{-- if existing request is not in progress --}}
                <div x-cloak x-show="!existingImportRequestData.progress" class="">
                    <div class="alert alert-danger">
                        {{ __tr('Please use Template from Download contacts dialog') }}
                    </div>
                    <p>{{ __tr('You can import csv file with new contacts or existing updated.') }}</p>
                    <div class="alert alert-light">
                        <h3>{{ __tr('Conventions') }}</h3>
                        <h4>{{ __tr('Mobile Number') }}</h4>
                        {{ __tr('Mobile number treated as unique entity, it should be with country code without prefixing
                        0 or +, if the Mobile number is found in the records other information for the same will get
                        updated with data from the csv file.') }}
                        <div class="mt-3">
                            <h4>{{ __tr('Group') }}</h4>
                            {{ __tr('Use comma separated group title, make sure groups are already exists into the
                            system. Groups won\'t be deleted, only new groups will be assigned.') }}
                        </div>
                    </div>
                    <div class="form-group ">
                        <input id="lwImportDocumentFilepond" type="file" data-allow-revert="true"
                            data-label-idle="{{ __tr('Select CSV File') }}" class="lw-file-uploader"
                            data-instant-upload="true"
                            data-action="<?= route('media.upload_temp_media', 'vendor_contact_import') ?>"
                            data-file-input-element="#lwImportDocument" data-allowed-media='{{ getMediaRestriction('
                            vendor_contact_import') }}'>
                        <input id="lwImportDocument" type="hidden" value="" name="document_name" />
                    </div>
                </div>
                </div>
                <!-- form footer -->
                <div x-cloak x-show="!existingImportRequestData.progress" class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">{{ __tr('Process Import') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                    </div>
                </div>
            </x-lw.form>
        </x-lw.modal>
        {{-- /import contacts --}}
        {{-- export contacts --}}
        <x-lw.modal id="lwExportDialog" :header="__tr('Download Contacts')" :hasForm="true"
            data-pre-callback="appFuncs.clearContainer">
            <div class="lw-form-modal-body p-3">
                <h5>{{ __tr('Export with Data') }}</h5>
                <p>{{ __tr('You can export all contacts csv file and import it back with updated data.') }}</p>
                <a href="{{ route('vendor.contact.write.export', [
                    'exportType' => 'data',
                    'fileType' => 'csv',
                ]) }}" data-method="post" class="btn btn-primary"><i class="fa fa-download"></i> {{ __tr('Download CSV File with Data') }}</a>
                <hr>
                <h5>{{ __tr('Blank CSV Template') }}</h5>
                <p>{{ __tr('You can download blank csv file and fill with data according to column header and import it
                    for updates.') }}</p>
                <a href="{{ route('vendor.contact.write.export', [
                    'exportType' => 'blank',
                     'fileType' => 'csv',
                ]) }}" data-method="post" class="btn btn-primary"> <i class="fa fa-download"></i> {{ __tr('Download CSV Blank Template') }}</a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
            </div>
        </x-lw.modal>
        {{-- /export contacts --}}
        <!-- Add New Contact Modal -->
        <x-lw.modal id="lwAddNewContact" :header="__tr('Add New Contact')" :hasForm="true"
            data-pre-callback="appFuncs.clearContainer">
            <!--  Add New Contact Form -->
            <x-lw.form id="lwAddNewContactForm" :action="route('vendor.contact.write.create')"
                :data-callback-params="['modalId' => '#lwAddNewContact', 'datatableId' => '#lwContactList']"
                data-callback="appFuncs.modelSuccessCallback">
                <!-- form body -->
                <div class="lw-form-modal-body">
                    <!-- form fields form fields -->
                    <!-- First_Name -->
                    <x-lw.input-field type="text" id="lwFirstNameField" data-form-group-class=""
                        :label="__tr('First Name')" name="first_name"  />
                    <!-- /First_Name -->
                    <!-- Last_Name -->
                    <x-lw.input-field type="text" id="lwLastNameField" data-form-group-class=""
                        :label="__tr('Last Name')" name="last_name"  />
                    <!-- /Last_Name -->
                    <!-- Country -->
                    <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwCountryField"
                        data-form-group-class="" data-selected=" " :label="__tr('Country')" name="country"
                        >
                        <x-slot name="selectOptions">
                            <option value="">{{ __tr('Country') }}</option>
                            @foreach(getCountryPhoneCodes() as $getCountryCode)
                            <option value="{{ $getCountryCode['_id'] }}">{{ $getCountryCode['name'] }}</option>
                            @endforeach
                        </x-slot>
                    </x-lw.input-field>
                    <!-- /Country -->
                    <!-- Phone_Number -->
                    <x-lw.input-field type="number" id="lwPhoneNumberField" data-form-group-class=""
                        :label="__tr('Mobile Number')" name="phone_number" minlength="9"
                        :helpText="__tr('Number should be with country code without 0 or +')" required="true" maxlength="20" />
                    <!-- /Phone_Number -->
                    
                    <!-- Language Code -->
                    <?php $languages = include app_path('Yantrana/Support/languages.php'); ?>
                    <label for="lwSelectLanguage"><?= __tr('Language') ?></label>
                    <select id="lwSelectLanguage" name="language_code" required>
                        @if(!__isEmpty($languages))
                            <option value="">{{ __tr('Select Language') }}</option>
                            @foreach($languages as $key => $language)
                            <option value="<?= $language['code'] ?>" required><?= $language['language'] ?> (<?= $language['code'] ?>)</option>
                            @endforeach
                        @endif
                    </select>
                    <!-- /Language Code -->

                    <!-- Email -->
                    <x-lw.input-field type="email" id="lwEmailField" data-form-group-class="" :label="__tr('Email')"
                        name="email" />
                    <!-- /Email -->
                    <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSelectGroupsField"
                        data-form-group-class="" data-selected=" " :label="__tr('Groups')" name="contact_groups[]"
                        multiple>
                        <x-slot name="selectOptions">
                            <option value="">{{ __tr('Select Groups') }}</option>
                            @foreach($vendorContactGroups as $vendorContactGroup)
                            <option value="{{ $vendorContactGroup['_id'] }}">{{ $vendorContactGroup['title'] }} {{ $vendorContactGroup['status'] == 5  ? __tr('(Archived)') : '' }}</option>
                            @endforeach
                        </x-slot>
                    </x-lw.input-field>
                    <div class="my-3">
                        <x-lw.checkbox id="lwPromotionalOpt" name="whatsapp_opt_out" data-color="#ff0000" data-size="small" value="1" data-lw-plugin="lwSwitchery" :label="__tr('Opt out Marketing Messages')" />
                    </div>
                    <div class="my-3">
                        @if(isAiBotAvailable())
                        <x-lw.checkbox id="lwAiBotEnable" :checked="getVendorSettings('default_enable_flowise_ai_bot_for_users')" name="enable_ai_bot" value="1" data-size="small" 
                            data-lw-plugin="lwSwitchery" :label="__tr('Enable AI Bot')" />
                        @endif
                    </div>
                    <div class="my-3">
                        <x-lw.checkbox id="lwReplyBotEnable" :checked="true" name="enable_reply_bot" value="1" data-size="small" 
                            data-lw-plugin="lwSwitchery" :label="__tr('Enable Reply Bot')" />
                    </div>
                    <fieldset>
                        <legend>{{ __tr('Other Information') }}</legend>
                        @foreach ($vendorContactCustomFields as $vendorContactCustomField)
                        <x-lw.input-field type="{{ $vendorContactCustomField->input_type }}"
                            id="lwCustomField{{ $vendorContactCustomField->_id }}" data-form-group-class=""
                            :label="$vendorContactCustomField->input_name"
                            name="custom_input_fields[{{ $vendorContactCustomField->_uid }}]" />
                        @endforeach
                    </fieldset>
                </div>
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
            </x-lw.form>
            <!--/  Add New Contact Form -->
        </x-lw.modal>
        <!--/ Add New Contact Modal -->

        <!-- Details Contact Modal -->
        <x-lw.modal id="lwDetailsContact" :header="__tr('Contact Details')">
            <!--  Details Contact Form -->
            <!-- Details body -->
            <div id="lwDetailsContactBody" class="lw-form-modal-body"></div>
            <script type="text/template" id="lwDetailsContactBody-template">
                <!-- form fields -->
                <div>
                    <label class="small">{{ __tr('First Name') }}:</label>
                    <div class="lw-details-item">
                        <%- __tData.first_name %>
                    </div>
                </div>

                <div>
                    <label class="small">{{ __tr('Last Name') }}:</label>
                    <div class="lw-details-item">
                        <%- __tData.last_name %>
                    </div>
                </div>

                <div>
                    <label class="small">{{ __tr('Country') }}:</label>
                    <div class="lw-details-item">
                        <%- __tData.country?.name %>
                    </div>
                </div>

                <div>
                    <label class="small">{{ __tr('Mobile Number') }}:</label>
                    <div class="lw-details-item">
                        <%- __tData.wa_id %>
                    </div>
                </div>
                <div>
                    <label class="small">{{ __tr('Language Code') }}:</label>
                    <div class="lw-details-item">
                        <%- __tData.language_code %>
                    </div>
                </div>

                <div>
                    <label class="small">{{ __tr('Email') }}:</label>
                    <div class="lw-details-item">
                        <%- __tData.email %>
                    </div>
                </div>

                <fieldset>
                    <legend>{{ __tr('Groups') }}</legend>
                    <% _.forEach(__tData.groups, function(value, key) { %>
                        <span class="badge badge-light">
                            <%- value.title %>
                        </span>
                        <% } ); %>
                </fieldset>
                <fieldset>
                    <legend>{{ __tr('Other Information') }}</legend>
                    @foreach ($vendorContactCustomFields as $vendorContactCustomField)
                    <div class="mb-2">
                        <label class="small">{{ $vendorContactCustomField->input_name }}:</label>
                        <div class="lw-details-item">
                            <%- _.get(_.find(__tData.custom_field_values, {'contact_custom_fields__id' : {{
                                $vendorContactCustomField->_id }} }), 'field_value') %>
                        </div>
                    </div>
                    @endforeach
                </fieldset>
            </script>
            <!--/  Details Contact Form -->
        </x-lw.modal>
        <!--/ Edit Contact Modal -->

        <!--Group description -->
        <div class="ml-3">
            <p class="card-text">{{$groupDescription
            }}</p>
        </div>
         <!--/ Group description -->
        <!-- Edit Contact Modal -->
        @include('contact.contact-edit-modal-partial')
        <!--/ Edit Contact Modal -->
        <div  x-cloak :class="isAllContactDeleteInProgress ? 'lw-delete-all-contact-loader-container' : ''" x-data="{isSelectedAll:false,selectedContacts: [], selectedGroupsForSelectedContacts:[], isAllContactDeleteInProgress: false,

            toggle(id) {
                if (this.selectedContacts.includes(id)) {
                    const index = this.selectedContacts.indexOf(id);
                    this.selectedContacts.splice(index, 1);
                    this.isSelectedAll = false;
                } else {
                    this.selectedContacts.push(id);
                    if($('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes').length == this.selectedContacts.length) {
                        this.isSelectedAll = true;
                    }
                };
            },toggleAll() {
                if(!this.isSelectedAll) {
                    $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes').not(':checked').trigger('click');
                    this.isSelectedAll = true;
                } else {
                    $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                    this.isSelectedAll = false;
                }
            },deleteSelectedContacts(groupUid) {
                var that = this,
                    confirmationMessage = groupUid 
                        ? '{{ __tr('Are you sure you want to remove selected contacts from this group?') }}' 
                        : '{{ __tr('Are you sure you want to delete all selected contacts?') }}';
                showConfirmation(confirmationMessage, function() {
                    __DataRequest.post('{{ route('vendor.contacts.selected.write.delete') }}', {
                        'selected_contacts' : that.selectedContacts,
                        'group_uid': groupUid
                    });
                }, {
                    confirmButtonText: '{{ __tr('Yes') }}',
                    cancelButtonText: '{{ __tr('No') }}',
                    type: 'error'
                });
            }, deleteAllContacts(groupId) {
                var that = this,
                    confirmationMessage = groupId ? '#lwRemoveAllContactsFromGroup-template' : '#lwDeleteAllContacts-template';
                showConfirmation(confirmationMessage, function() {
                    that.isAllContactDeleteInProgress = true;
                    __DataRequest.post('{{ route('vendor.contacts.all.write.delete') }}', {
                        'group_id': groupId   
                    }, function(responseData) {
                        that.isAllContactDeleteInProgress = false;
                    });
                }, {
                    confirmButtonText: '{{ __tr('Yes') }}',
                    cancelButtonText: '{{ __tr('No') }}',
                    type: 'error'
                });
            }, assignGroupsToSelectedContacts(){
                var that = this;
                __DataRequest.post('{{ route('vendor.contacts.selected.write.assign_groups') }}', {
                    'selected_contacts' : that.selectedContacts,
                    'selected_groups' : that.selectedGroupsForSelectedContacts
                });
                $('#lwAssignGroups').modal('hide');
                $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                this.isSelectedAll = false;
            }}" x-init="$('#lwContactList').on( 'draw.dt', function () {
                $('.dataTables_wrapper table>tbody input[type=checkbox].lw-checkboxes:checked').trigger('click');
                isSelectedAll = false;
            } );">

            <div class="lw-all-contact-delete-message" x-show="isAllContactDeleteInProgress">
                <div class="lw-delete-contact-spinner"></div>
                {{ __tr('Please wait, all contacts deleting in progress...') }}
            </div>

            @if(hasVendorAccess('manage_contacts', 'delete_contacts') or hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group') or hasVendorAccess('messaging'))
                <button x-show="!isSelectedAll" class="btn btn-dark btn-sm my-2" @click="toggleAll">{{ __tr('Select All')
                    }}</button>
                <button x-show="isSelectedAll" class="btn btn-dark btn-sm my-2" @click="toggleAll">{{ __tr('Unselect All')
                    }}</button>
            @endif
            <div class="btn-group">
                @if(hasVendorAccess('manage_contacts', 'delete_contacts') or hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group') or hasVendorAccess('messaging'))
                    <button :class="!selectedContacts.length ? 'disabled' : ''"
                        class="btn btn-danger mt-1 btn-sm dropdown-toggle" type="button" data-toggle="dropdown"
                        aria-expanded="false">
                        {{ __tr('Bulk Actions') }}
                    </button>
                @endif
                <div class="dropdown-menu">
                    @if(hasVendorAccess('manage_contacts', 'delete_contacts'))
                        <a class="dropdown-item" @click.prevent="deleteSelectedContacts('<?= $groupUid ?>')" href="#">
                        {{ $groupUid ? __tr('Remove Selected Contacts') : __tr('Delete Selected Contacts') }}
                        </a>
                    @endif
                    @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                        <a class="dropdown-item" data-toggle="modal" data-target="#lwAssignGroups" href="#">{{ __tr('Assign Group to Selected Contacts') }}</a>
                    @endif
                    @if(hasVendorAccess('messaging'))
                        <a class="dropdown-item lw-ajax-link-action" data-toggle="modal" data-target="#lwAssignTeamMember" data-response-template="#lwAssignTeamMemberBody" href="{{ route('vendor.team_member.read.list', ['contactIdOrUid' => 'bulk_action']) }}">{{ __tr('Assign Team Member') }}</a>
                    @endif
                </div>
            </div>
            <!-- Delete All Contact Button -->
            @if(hasVendorAccess('manage_contacts', 'delete_contacts'))
                <button class="btn btn-danger btn-sm my-2" @click.prevent="deleteAllContacts('<?= $groupUid ?>')">
                    @if($groupUid)
                        <i class="fa fa-user-times"></i> {!! __tr("Remove Group's All Contacts") !!}
                    @else
                        <i class="fa fa-trash"></i> {{ __tr('Delete All Contact') }}
                    @endif
                </button>
            @endif
            <!-- /Delete All Contact Button -->

            @if(!$groupUid)
            <span class="mb-4" x-data="{ isFilterApplied: false, contactCount: 0, countString: '' }">
                <div class="text-right mt-1" role="group" :class="isFilterApplied ? 'btn-group' : '' ">
                    <!-- Contact Filter Dialog Button -->
                    <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Advanced Contacts Filters') }}" class="lw-btn btn btn-secondary btn-sm lw-ajax-link-action lw-filter-and-clear-btn-z-index" data-response-template="#lwContactFilterBody" href="{{ route('vendor.contact.read.filter_support_data') }}"  data-toggle="modal" data-target="#lwContactFilter"><i class="fas fa-filter"></i> {{  __tr('Advanced Contacts Filters') }}</a>
                    <!-- /Contact Filter Dialog Button -->

                    <!-- Clear Filter Button -->
                    <a x-show="isFilterApplied" type="button" class="lw-btn btn btn-danger btn-sm lw-ajax-link-action lw-filter-and-clear-btn-z-index" data-method="post" x-bind:data-post-data="toJsonString({'clear_filter': true})" href="<?= route('vendor.contact.write.store_contact_filter') ?>" data-callback="__Utils.viewReload">
                        {{ __tr('Clear Filter') }}
                    </a>
                    <!-- /Clear Filter Button -->
                </div>

                <fieldset class="mb-3" x-show="isFilterApplied">
                    <legend class="bg-white"><h2>{{ __tr('Advanced Contacts Filter') }}</h2></legend>
                    <span x-show="contactCount">
                        <div x-text="countString"></div>
                        {{  __tr('Data has been filtered based on advanced filter, you can create a new group using this result.') }}
                        <hr class="lw-filter-message-hr">
                        <div class="btn-group" role="group">
                            <!-- Contact Filter Dialog Button -->
                            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Update Filter') }}" class="lw-btn btn btn-dark btn-sm lw-ajax-link-action" data-response-template="#lwContactFilterBody" href="{{ route('vendor.contact.read.filter_support_data') }}"  data-toggle="modal" data-target="#lwContactFilter"><i class="fas fa-filter"></i> {{  __tr('Update Filter') }}</a>
                            <!-- /Contact Filter Dialog Button -->

                            @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                <!-- Create New Contact Group Button -->
                                <button type="button" class="lw-btn btn btn-primary btn-sm" data-pre-callback="appFuncs.clearContainer" data-toggle="modal" data-target="#lwAddNewGroup">
                                    {{ __tr('Create New Contact Group') }}
                                </button>
                                <!-- /Create New Contact Group Button -->
                            @endif
                        </div>
                    </span>
                    <span x-show="!contactCount">
                        {{  __tr('No contacts were found with the current filter, but you can update the filter to retrieve a list of contacts.') }}
                        <hr class="lw-filter-message-hr">
                        
                        <!-- Contact Filter Dialog Button -->
                        <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Update Filter') }}" class="lw-btn btn btn-dark btn-sm lw-ajax-link-action" data-response-template="#lwContactFilterBody" href="{{ route('vendor.contact.read.filter_support_data') }}"  data-toggle="modal" data-target="#lwContactFilter"><i class="fas fa-filter"></i> {{  __tr('Update Filter') }}</a>
                        <!-- /Contact Filter Dialog Button -->
                    </span>
                </fieldset>
            </span>
            @endif

            <!-- Assign Groups to the selected contacts -->
            <x-lw.modal id="lwAssignGroups" :header="__tr('Assign Groups to Selected Contacts')" :hasForm="true"
                data-pre-callback="appFuncs.clearContainer">
                <!-- form body -->
                <div class="lw-form-modal-body p-4">
                    <!-- form fields form fields -->
                    <x-lw.input-field x-model="selectedGroupsForSelectedContacts" type="selectize"
                        data-lw-plugin="lwSelectize" id="lwSelectGroupsField" data-form-group-class="" data-selected=" "
                        :label="__tr('Groups')" name="contact_groups[]" multiple>
                        <x-slot name="selectOptions">
                            <option value="">{{ __tr('Select Groups') }}</option>
                            @foreach($vendorContactGroups as $vendorContactGroup)
                            <option value="{{ $vendorContactGroup['_id'] }}">{{ $vendorContactGroup['title'] }} {{ $vendorContactGroup['status'] == 5  ? __tr('(Archived)') : '' }}</option>
                            @endforeach
                        </x-slot>
                    </x-lw.input-field>
                </div>
                <!-- form footer -->
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="button" @click="assignGroupsToSelectedContacts" class="btn btn-primary">{{
                        __tr('Submit') }}</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                </div>
                <!--/  Add New Contact Form -->
            </x-lw.modal>
            <!--/ Assign Groups to the selected contacts -->

            <!-- Assign Team Member Modal -->
            <x-lw.modal id="lwAssignTeamMember" :header="__tr('Assign Team Member')" :hasForm="true">
                <div id="lwAssignTeamMemberBody" class="lw-form-modal-body"></div>
                <script type="text/template" id="lwAssignTeamMemberBody-template">                    
                    <x-lw.form id="lwAssignTeamMemberForm" :action="route('vendor.chat.assign_user.process')"
                        :data-callback-params="['modalId' => '#lwAssignTeamMember', 'datatableId' => '#lwContactList']"
                        data-callback="appFuncs.modelSuccessCallback">
                        <!-- form body -->
                        <div class="lw-form-modal-body">
                            <% if(__tData.is_bulk_action) { %>
                                <input type="hidden" name="contactIdOrUid" :value="selectedContacts">
                                <input type="hidden" name="bulk_action" :value="true">
                            <% } else { %>
                                <input type="hidden" name="contactIdOrUid" value="<%= __tData.contact._uid %>">
                            <% } %>
                            <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSelectTeamMemberField"
                            :label="__tr('Select Team Member')" name="assigned_users_uid" data-selected="<%= __tData?.contact?.assigned_user?._uid %>">
                                <x-slot name="selectOptions">
                                    <option value="no_one">{{ __tr('Unassigned') }}</option>
                                    <% _.forEach(__tData.teamMembers, function(item) {%>
                                        <option value="<%= item._uid %>">
                                            <%= item.first_name %> <%= item.last_name %> <% if(item._uid == __tData.userUID) { %> ({{ __tr('You') }}) <% } %>
                                        </option>
                                    <% }); %>
                                </x-slot>
                            </x-lw.input-field>
                        </div>
                        <!-- form footer -->
                        <div class="modal-footer">
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                        </div>
                    </x-lw.form>
                </script>
            </x-lw.modal>
            <!-- Assign Team Member Modal -->

            <!-- Contact filter modal -->
            @include('contact.contact-filter')
            <!-- /Contact filter modal -->

            <!-- Add New Group Modal -->
            <x-lw.modal id="lwAddNewGroup" :header="__tr('Add New Group')" :hasForm="true">
                <!--  Add New Group Form -->
                <x-lw.form id="lwAddNewGroupForm" :action="route('vendor.contact.group.write.create')"
                    :data-callback-params="['modalId' => '#lwAddNewGroup', 'datatableId' => '#lwContactList']"
                    data-callback="appFuncs.modelSuccessCallback">
                    <!-- form body -->
                    <div class="lw-form-modal-body">
                        <input type="hidden" name="request_from" value="CONTACT_ADVANCE_FILTER">

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
            
            <x-lw.datatable data-page-length="100" id="lwContactList" :url="route('vendor.contact.read.list', [
                'groupUid' => $groupUid
            ])">
                <th style="width: 1px;padding:0;" data-name="none"></th>
                <th data-name="none" data-template="#lwSelectMultipleContactsCheckbox">{{ __tr('Select') }}</th>
                <th data-orderable="true" data-name="first_name">{{ __tr('First Name') }}</th>
                <th data-orderable="true" data-name="last_name">{{ __tr('Last Name') }}</th>
                <th data-orderable="true" data-name="phone_number">{{ __tr('Mobile Number') }}</th>
                <th data-orderable="true" data-name="language_code">{{ __tr('Language Code') }}</th>
                <th data-orderable="true" data-name="created_at">{{ __tr('Created on') }}</th>
                <th data-name="country_name">{{ __tr('Country') }}</th>
                <th data-orderable="true" data-name="email">{{ __tr('Email') }}</th>
                <th data-orderable="true" data-name="whatsapp_opt_out">{{ __tr('Marketing') }}</th>
                <th  data-name="groups">{{ __tr('Groups') }}</th>
                @if (isAiBotAvailable())
                <th data-orderable="true" data-name="disable_ai_bot">{{ __tr('AI Bot') }}</th>
                @endif
                <th data-template="#contactActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
        </div>
        <!-- action template -->
        <script type="text/template" id="lwSelectMultipleContactsCheckbox">
            <input @click="toggle('<%- __tData._uid %>')" type="checkbox" name="selected_contacts[]" class="lw-checkboxes custom-checkbox" value="<%- __tData._uid %>">
        </script>
        <script type="text/template" id="contactActionColumnTemplate">
            <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Details') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwDetailsContactBody" href="<%= __Utils.apiURL("{{ route('vendor.contact.read.update.data', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwDetailsContact"><i class="fa fa-info-circle"></i> {{  __tr('Details') }}</a>
            @if(hasVendorAccess('manage_contacts', 'add_edit_contacts'))
                <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Edit') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditContactBody" href="<%= __Utils.apiURL("{{ route('vendor.contact.read.update.data', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditContact"><i class="fa fa-edit"></i> {{  __tr('Edit') }}</a>
            @endif
<!--  Delete Action -->
@if(hasVendorAccess('messaging'))
<a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Send Template Message') }}" class="lw-btn btn btn-sm btn-primary" href="<%= __Utils.apiURL("{{ route('vendor.template_message.contact.view', ['contactUid']) }}",{'contactUid': __tData._uid}) %>"><i class="fab fa-whatsapp"></i> {{  __tr('Send Template Message') }}</a> <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Chat') }}" class="lw-btn btn btn-sm btn-primary" href="<%= __Utils.apiURL("{{ route('vendor.chat_message.contact.view', ['contactUid']) }}",{'contactUid': __tData._uid}) %>"><i class="fab fa-whatsapp"></i> {{  __tr('Chat') }}</a>
@endif
@if(hasVendorAccess('manage_contacts', 'delete_contacts'))
 <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.delete', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteContact-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
 @endif
 <!--  Remove Contact Action -->
 @if($currentGroup!=null)
  <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.remove',['contactIdOrUid', 'groupUid' => $groupUid]) }}",{ 'contactIdOrUid': __tData._uid }) %>" class="btn btn-warning btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwRemoveContact-template" title="{{ __tr('Remove contact from group') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-user-times"></i> {{  __tr('Remove') }}</a> 
 @endif

@if(hasVendorAccess('messaging'))
    <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Assign') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwAssignTeamMemberBody" href="<%= __Utils.apiURL("{{ route('vendor.team_member.read.list', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" data-toggle="modal" data-target="#lwAssignTeamMember"><i class="fa fa-user-check"></i> {{  __tr('Assign') }}</a>
@endif

@if(hasVendorAccess('messaging'))
    <% if (__tData.is_blocked) { %>
        <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.unblock', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwUnblockContact-template" title="{{ __tr('WA Unblock') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-ban"></i> {{  __tr('WA Unblock') }}</a>
    <% } %>
    <% if(!__tData.is_blocked && __tData.is_direct_message_delivery_window_opened) { %>
        <a data-method="post" href="<%= __Utils.apiURL("{{ route('vendor.contact.write.block', [ 'contactIdOrUid']) }}", {'contactIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwBlockContact-template" title="{{ __tr('WA Block') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwContactList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-ban"></i> {{  __tr('WA Block') }}</a>
    <% } %>
@endif
 
 <!--  Remove Contact Action  -->
    </script>
        <!-- /action template -->
        <!-- Contact delete template -->
        <script type="text/template" id="lwDeleteContact-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to delete this Contact permanently?') }}</p>
        </script>
        <!-- /Contact delete template -->

         <!-- Contact remove template -->
         <script type="text/template" id="lwRemoveContact-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('You want to remove this Contact from this group?') }}</p>
        </script>
        <!-- /Contact remove template -->

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

        <!-- Contacts remove from group -->
        <script type="text/template" id="lwRemoveAllContactsFromGroup-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('All contacts in this group will be removed. Are you sure you want to proceed?') }}</p>
        </script>
        <!-- /Contacts remove from group -->

         <!-- Delete All Contact Template -->
        <script type="text/template" id="lwDeleteAllContacts-template">
            <h2>{{ __tr('Are You Sure!') }}</h2>
            <p>{{ __tr('All your contacts will be deleted permanently, Are you sure you want to delete all your contacts??') }}</p>
        </script>
        <!-- /Delete All Contact Template -->
    </div>
</div>
<script>
(function() {
    'use strict';
        document.addEventListener('alpine:init', () => {
                Alpine.data('initialContactsInfoData', () => ({
                    existingImportRequestData: @json(getVendorSettings('contacts_import_process_data') ?: []),
            }));
        });
    })();
</script>
@push('appScripts')
<script>
(function($) {
    'use strict';    
    window.onUpdateContactDetails = function(responseData, callbackParams) {
        appFuncs.modelSuccessCallback(responseData, callbackParams);
    };
    window.onImportProcessUpdate = function(responseData, callbackParams) {
        if((responseData.reaction == 1) || (responseData === true)) {
            if(_.get(responseData, 'data.progressCount') === 0) {
                // close modal
                appFuncs.modelSuccessCallback(responseData, callbackParams);
            } else if(_.get(responseData, 'data.progressCount') || (responseData === true))  {
                __DataRequest.post('{{ route('vendor.contact.write.import') }}', {
                    'document_name': 'existing'
                }, function(responseData) {
                     _.delay(function() {
                         window.onImportProcessUpdate(responseData, callbackParams);
                    },30);
                });
            } else {
                // close modal
                appFuncs.modelSuccessCallback(responseData, callbackParams);
            }
        }
    };
    var existingImportRequestExist = {{ getVendorSettings('contacts_import_process_data') ? 1 : 0 }};
    if(existingImportRequestExist) {
        _.delay(function() {
            window.onImportProcessUpdate(true);
        },300);
    };
    $('#lwSelectLanguage').selectize({
        create: true,
        valueField: 'code',
        labelField: 'language',
        searchField: ['language', 'code']
    });

    window.clearFilter = function () {
        let filterForm = $('#lwContactFilterForm');
        let formData = filterForm.serializeArray();
        filterForm.append('<input type="hidden" name="clear_filter" value="true">');
        filterForm.submit();
    }
    
})(jQuery);
</script>
@endpush
@endsection()