@extends('layouts.app', ['title' => __tr('Local Files & Media')])

@section('content')
@include('users.partials.header', [
'title' => __tr('Local Files & Media') . ' '. auth()->user()->name,
'description' => '',
'class' => 'col-lg-7'
])

<div class="container-fluid" x-data="{
    previewUrl: '',
    mediaType: '',
    docFileName: '',
    selectAllLabel: '{{ __tr('Select All') }}',
    rowCheckboxes() {
        return Array.from(document.querySelectorAll('.lw-row-checkbox'));
    },
    deleteMultipleMedia() {
        const selectedMedia = [];

        this.rowCheckboxes()
            .filter(cb => cb.checked)
            .forEach(cb => {
                selectedMedia.push({
                    filepath: cb.dataset.filepath,
                    filename: cb.dataset.filename
                });
            });

        if (selectedMedia.length === 0) {
            showErrorMessage('<?= __tr("Please select at least one item.") ?>');
            return;
        }

        var that = this;
        showConfirmation('#lwDeleteBulkFilesMedia-template', function() {
            __DataRequest.post('{{ route('media.files.write.bulk_delete') }}', {
                'selected_media': selectedMedia
            }, function() {
                
            });
        }, {
            confirmButtonText: '{{ __tr('Yes') }}',
            cancelButtonText: '{{ __tr('No') }}',
            type: 'error'
        });
    },
    isPdf(url) {
        return typeof url === 'string'
            && url.toLowerCase().split('?')[0].endsWith('.pdf');
    },
    toggleAll(event) {
        const checked = event.target.checked;
        this.rowCheckboxes().forEach(cb => cb.checked = checked);
        this.toggleBulkButton();
        this.updateSelectAllState();
    },

    toggleSingle() {
        this.updateSelectAllState();
        this.toggleBulkButton();
    },

    updateSelectAllState() {
        const all = this.rowCheckboxes();
        const checked = all.filter(cb => cb.checked);

        const selectAll = this.$refs.selectAll;
        if (!selectAll) return;

        const isIndeterminate = checked.length > 0 && checked.length < all.length;
        const allSelected = checked.length === all.length && all.length > 0;

        selectAll.checked = allSelected;
        selectAll.indeterminate = isIndeterminate;

        this.selectAllLabel = allSelected ? '{{ __tr('Unselect All') }}' : '{{ __tr('Select All') }}';
    },

    toggleBulkButton() {
        const btn = this.$refs.bulkBtn;
        if (!btn) return;

        btn.disabled = !this.rowCheckboxes().some(cb => cb.checked);
    }
}">
    <div class="card mb-4">
        <div class="card-body p-4">
            <form  id="lwMediaAndFilesFilterForm" action="{{ route('media.files.read_view') }}" method="get" data-show-processing="true">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-4">
                        <x-lw.input-field type="selectize" data-form-group-class=""
                            name="vendor_uid" data-selected=""
                            :label="__tr('Select Vendor')" placeholder="{{ __tr('Select Vendor') }}" >
                            <x-slot name="selectOptions">
                                <option value="all">{{ __tr('All') }}</option>
                                @if(!__isEmpty($vendorData ?? null))
                                    @foreach ($vendorData as $vendorUid => $vendorTitle)
                                        <option value="{{ $vendorUid }}" @if(request('vendor_uid') == $vendorUid) selected @endif>{{ $vendorTitle }}</option>
                                    @endforeach
                                @endif
                            </x-slot>
                        </x-lw.input-field>
                    </div>
                    <div class="col-sm-12 col-md-12 col-lg-4">
                        <x-lw.input-field type="selectize" data-form-group-class=""
                            name="media_type" data-selected=""
                            :label="__tr('Select Media Type')" placeholder="{{ __tr('Select Media Type') }}" >
                            <x-slot name="selectOptions">
                                <option value="all">{{ __tr('All') }}</option>
                                @if(!__isEmpty($mediaTypes ?? null))
                                    @foreach ($mediaTypes as $type)
                                        <option value="{{ $type }}" @if(request('media_type') === $type) selected @endif>{{ ucfirst($type) }}</option>
                                    @endforeach
                                @endif
                            </x-slot>
                        </x-lw.input-field>
                    </div>
                    <div class="col-sm-12 col-md-12 col-lg-4">
                        <div class="row">
                            <div class="col-lg-6 lw-file-media-filter-btn">
                                <button type="submit" class="btn btn-primary btn-block">{{ __tr('Show') }}</button>
                            </div>
                            <div class="col-lg-6 lw-file-media-filter-btn">
                                <a href="{{ url()->current() }}" class="btn btn-secondary btn-block">
                                    {{ __tr('Reset') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="align-items-center">
            <div class="col-md-6 text-center text-md-left mb-2 mb-md-0">
                <button type="button"
                        x-ref="bulkBtn"
                        disabled
                        @click="deleteMultipleMedia()"
                        class="btn btn-danger btn-sm">
                    {{ __tr('Delete Selected Files') }}
                </button>
            </div>
        </div>
        
        <div class="col-xl-12 mt-4">
            <x-lw.datatable id="lwMediaAndFileList" :url="route('media.files.datatable_list', [
                'vendorUid' => request('vendor_uid', 'all'),
                'mediaType' => request('media_type', 'all')
            ])" data-page-length="100">
                <th data-name="none" data-template="#lwSelectMultipleFilesMediaCheckbox" class="lw-media-file-heading text-center">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        x-ref="selectAll"
                        id="lwSelectAllCheckbox"
                        @change="toggleAll($event)"
                    >
                    <label class="form-check-label" for="lwSelectAllCheckbox" x-text="selectAllLabel"></label>
                </th>
                <th data-orderable="false" data-name="vendor_title">{{ __tr('Vendor') }}</th>
                <th data-template="#filesMediaTypeColumnTemplate" data-name="null">{{ __tr('Media Type') }}</th>
                <th data-template="#filesMediaNameColumnTemplate" data-name="null">{{ __tr('File Name') }}</th>
                <th data-orderable="false" data-name="size_kb">{{ __tr('Size (KB)') }}</th>
                <th data-orderable="false" data-name="formatted_date">{{ __tr('Date') }}</th>
                <th data-template="#filesMediaActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
            </x-lw.datatable>
        </div>
    </div>

    <script type="text/template" id="filesMediaNameColumnTemplate">
        <a href="<%= __tData.url %>" target="_blank" title="{{  __tr('View in new tab') }}">
            <%= __tData.file_name %> <i class="fas fa-external-link-alt"></i>
        </a>
    </script>

    <script type="text/template" id="lwSelectMultipleFilesMediaCheckbox">
        <input
            type="checkbox"
            class="lw-row-checkbox"
            data-filepath="<%= __tData.directory %>"
            data-filename="<%= __tData.file_name %>"
            @change="toggleSingle()">
    </script>

    <script type="text/template" id="filesMediaTypeColumnTemplate">
        <a data-toggle="modal" data-target="#lwMediaFilePreview" href="#" @click="previewUrl = '<%= __tData.url %>'; mediaType = '<%= __tData.media_type %>'; docFileName = '<%= __tData.file_name %>'">
            <div class="lw-whatsapp-header-placeholder">
                <% if(__tData.media_type == 'images'){ %>
                    <i class="fa fa-3x fa-image text-white"></i>
                <% } else if(__tData.media_type == 'videos'){ %>
                    <i class="fa fa-3x fa-play-circle text-white"></i>
                <% } else if(__tData.media_type == 'audios'){ %>
                    <i class="fa fa-3x fa-headphones text-white"></i>
                <% } else if(__tData.media_type == 'documents'){ %>
                    <i class="fa fa-3x fa-file-alt text-white p-2"></i>
                <% } %>
            </div>
        </a>
    </script>

    <script type="text/template" id="filesMediaActionColumnTemplate">
        <a data-toggle="modal" data-target="#lwMediaFilePreview" href="#" @click="previewUrl = '<%= __tData.url %>'; mediaType = '<%= __tData.media_type %>'" title="{{  __tr('View') }}" class="lw-btn btn btn-sm btn-default" target="_blank"><i class="fa fa-eye"></i> {{  __tr('View') }}</a>

        <!--  Delete Action -->
        <a data-method="post" x-bind:data-post-data="toJsonString({'path':'<%= __tData.directory %>','filename':'<%= __tData.file_name %>'})" href="{{ route('media.files.write.delete') }}" class="btn btn-danger btn-sm lw-ajax-link-action" data-confirm="#lwDeleteFilesMedia-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwMediaAndFileList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
        {{-- login as  --}}
        <!-- login as button -->
    </script>

    <!-- Media File Preview -->
    <x-lw.modal id="lwMediaFilePreview" :header="__tr('Media Preview')"
        data-pre-callback="appFuncs.clearContainer">
        <!-- form body -->
        <div class="lw-form-modal-body p-4">
            <template x-if="mediaType == 'images'">
                <img class="lw-whatsapp-header-image" x-bind:src="previewUrl" alt="">
            </template>

            <template x-if="mediaType == 'videos'">
                <video class="lw-whatsapp-header-video" controls x-bind:src="previewUrl"></video>
            </template>

            <template x-if="mediaType == 'audios'">
                <audio class="lw-whatsapp-header-audio my-auto mx-4" controls>
                    <source x-bind:src="previewUrl" type="audio/mpeg">
                    {{  __tr('Your browser does not support the audio element.') }}
                </audio>
            </template>

            <template x-if="mediaType === 'documents'">
                <div>
                    <template x-if="isPdf(previewUrl)">
                        <iframe
                            height="800"
                            width="100%"
                            :src="previewUrl"
                            frameborder="0"
                        ></iframe>
                    </template>

                    <template x-if="previewUrl && !isPdf(previewUrl)">
                        <div class="text-center py-4">
                            <div class="alert alert-warning">
                                <strong>{{  __tr('Please Note:') }}</strong>
                                {{  __tr('This file type cannot be previewed here. Please download the file to view it.') }}
                            </div>
                            <div class="mb-2" x-text="docFileName"></div>
                            <a
                                :href="previewUrl"
                                title="{{ __tr('Download') }}"
                                class="lw-btn btn btn-sm btn-default"
                                target="_blank"
                                download
                            ><i class="fas fa-download"></i> {{ __tr('Download') }}
                            </a>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </x-lw.modal>
    <!--/ Media File Preview -->
</div>

@endsection

<script type="text/template" id="lwDeleteFilesMedia-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to delete this file permanently?') }}</p>
</script>

<script type="text/template" id="lwDeleteBulkFilesMedia-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want to delete selected files permanently?') }}</p>
</script>

@push('appScripts')
<script>
    $('#lwMediaFilePreview').on('hidden.bs.modal', function () {
        // Stop HTML5 video
        let video = $(this).find('video').get(0);
        if (video) {
            video.pause();
            video.currentTime = 0; // optional: reset to start
        }
    });

    if ($.fn.dataTable) {
        $.fn.dataTable.defaults = $.extend({}, $.fn.dataTable.defaults, {
            "language": {
                "info": '{{ __tr("Showing _START_ to _END_ entries of many") }}'
            }
        });
    }
</script>
@endpush
