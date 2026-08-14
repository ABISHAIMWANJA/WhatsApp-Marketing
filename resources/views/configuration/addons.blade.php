@extends('layouts.app', ['title' => __tr('Addons')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Addons'),
'description' =>'',
'class' => 'col-lg-7'
])
<div class="container-fluid ">
    <div class="row">
        @if (file_exists(base_path('addons')))
            @if (is_writable(base_path('addons')))
                <div class="col-6 offset-3 mb-3">
                    <div class="alert alert-danger text-center">
                    {{  __tr('Your addons directory is not writable, please give write permission to your addons directory to upload new addons.') }}
                </div>
                </div>
            @endif
        @else
        <div class="col-6 offset-3 mb-3">
            <div class="alert alert-danger">
                {{  __tr('Your addons directory does not exist, please make sure addons directory exists in your application\'s root directory and has write permissions.') }}
            </div>
        </div>
        @endif
        <div class="col-xl-12 mb-3">
            <div class="float-right">
                <button type="button" class="lw-btn btn btn-dark" data-toggle="modal"
                    data-target="#lwInstallNewAddonDialog"> <i class="fa fa-upload"></i> {{ __tr('Update Existing Or Install New Addon') }}</button>
            </div>
        </div>
        <x-lw.modal id="lwInstallNewAddonDialog" :header="__tr('Update Existing Or Install New Addon')" :hasForm="true"
        data-pre-callback="appFuncs.clearContainer">
        <x-lw.form id="lwInstallNewAddonDialogForm" :action="route('central.addons.write.install')"
            :data-callback-params="['modalId' => '#lwInstallNewAddonDialog', 'datatableId' => '#lwContactList']"
            data-callback="appFuncs.modelSuccessCallback">
            <div class="lw-form-modal-body">
                <div class="alert alert-danger">
                    {{ __tr('You need to select zip file under your downloaded main zip file package file.') }}
                </div>
                <div class="form-group ">
                    <input id="lwInstallAddonDocumentFilepond" type="file" data-allow-revert="true"
                        data-label-idle="{!! __tr('Select & Upload Addon ZIP file') !!}" class="lw-file-uploader"
                        data-instant-upload="true"
                        data-action="<?= route('central.addons.write.upload', 'addon_upload_file') ?>"
                        data-file-input-element="#lwInstallAddonDocument" data-allowed-media='{{ getMediaRestriction('
                        addon_upload_file') }}'>
                    <input id="lwInstallAddonDocument" type="hidden" value="" name="document_name" />
                </div>
            </div>
            <!-- form footer -->
            <div class="modal-footer">
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">{{ __tr('Install') }}</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
            </div>
        </x-lw.form>
    </x-lw.modal>
        <!-- button -->
        <div class="col">
            <div class="card p-3">
                <div class="card-body row">
                    @foreach (($allAddons ?? []) as $addonInfoKey => $addonInfo)
                            <div class="card col-lg-4 col-xl-3 col-md-6 col-sm-12 shadow-none p-2 text-center mr-4 mb-4">
                                <img src="{{ $addonInfo['thumbnail'] ?: $addonInfo['local_thumbnail_url'] }}" class="card-img-top" alt="{{ $addonInfo['title'] ?? '' }}">
                                <h1 class="card-header text-primary">
                                    {{ $addonInfo['title'] ?? '' }}
                                </h1>
                                <div class="card-body">
                                    @if(($addonInfo['highlighted_text'] ?? ''))
                                    <h2 class="text-warning">
                                        {{ $addonInfo['highlighted_text'] }}
                                    </h2>
                                    @endif
                                    @if (($addonInfo['version'] ?? ''))
                                        <h3 class="text-muted">{{ __tr('Version - __version__', [
                                            '__version__' => $addonInfo['version']
                                        ]) }}</h3>
                                        @if (version_compare($addonInfo['version'], ($addonInfo['installed_version'] ?? $addonInfo['version']), '>'))
                                            <h4 class="text-warning">
                                                {{  __tr('New version is available') }}
                                            </h4>
                                        @endif
                                    @endif
                                    <p class="card-text">{{ $addonInfo['description'] ?? '' }}</p>
                                </div>
                                <div class="card-footer">
                                    @if (($addonInfo['price'] ?? '') and (isDemo() || !($addonInfo['installed_version'] ?? null)))
                                        <h2 class="text-danger">{{ $addonInfo['price'] }}</h2>
                                    @endif
                                </div>
                               <div>
                                @if($addonInfo['setup_url'] ?? null)
                                   <div class="mb-3">
                                    <a class="btn btn-success" href="{{ route('addon.'. $addonInfo['identifier'].'.setup_view') }}">{!! __tr('Setup & Details') !!}</a>
                                   </div>
                                @endif
                                @foreach ( ($addonInfo['buttons'] ?? []) as $addonInfoButtonKey => $addonInfoButton)
                                <a target="_blank" class="btn btn-info btn-sm" href="{{ $addonInfoButton }}">{{ $addonInfoButtonKey }}</a>
                                @endforeach
                               </div>

                               <?php $addonIdentifier = $addonInfo['identifier']; ?>
                                @if(getAppSettings('lwAddon' . $addonIdentifier, 'registration_id') and $addonInfo['enable'] ?? null)
                                    <div class="mb-2 mt-2" x-data="{
                                        submitForm: function (enableWhatsJetAddon) {
                                            $('#lw{{ $addonIdentifier }}EnableForm').submit();
                                        },
                                        enableWhatsJetAddon: '{{ getAppSettings('lwEnable'.$addonIdentifier.'') }}'
                                    }">
                                        <form id="lw{{ $addonIdentifier }}EnableForm"
                                            class="lw-ajax-form lw-form" name="lw_whats_jet_enable_addon" method="post"
                                            action="<?= route('manage.configuration.write', ['pageType' => 'lwAddon'.$addonIdentifier.'']) ?>">

                                            <input type="hidden" name="lwEnable{{ $addonIdentifier }}" x-bind:value="!enableWhatsJetAddon ? 1 : 0">

                                            <x-lw.checkbox id="lwEnable{{ $addonIdentifier }}" :offValue="0" :checked="getAppSettings('lwEnable'.$addonIdentifier.'')" data-lw-plugin="lwSwitchery" :label="__tr('Enable')" x-model="enableWhatsJetAddon" @click="submitForm(!enableWhatsJetAddon)"/>

                                        </form>
                                    </div>
                                @endif

                               <div class="card-footer text-center">
                                @if($addonInfo['installed_version'] ?? null)
                                <span class="text-light">
                                    <i class="fa fa-check-circle"></i> {{  __tr('Installed Version - __installedVersion__', [
                                        '__installedVersion__' => $addonInfo['installed_version']
                                    ]) }}
                                </span>
                                @endif
                                @if($addonInfo['available_since'] ?? null)
                                <span class="text-light">
                                    <i class="fa fa-check-circle"></i> {{  __tr('Available from __availableSince__', [
                                        '__availableSince__' => $addonInfo['available_since']
                                    ]) }}
                                </span>
                                @endif
                               </div>
                            </div>
                        @endforeach
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()