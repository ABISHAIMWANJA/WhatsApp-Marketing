@extends('layouts.app', ['title' => __tr('Mobile App Configurations')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Mobile App Configurations'),
'description' =>'',
'class' => 'col-lg-7'
])

@php
$isDemoMode = isDemo();
$demoContent = 'XXXXXXXXXXXXX MASKED FOR DEMO XXXXXXXXXXXXX';
@endphp

<div class="container-fluid ">
    <div class="row p-4">
     {{--    <div class="col-12 mb-3 alert alert-success ">
            <?= __tr('If you have purchase Flutter Mobile App or Bundle of for this application. You need following configurations contents for app_config.dart file for Flutter Mobile apps.') ?>
        </div> --}}
        <!-- button -->
        <div class="col-12 p-0">
            @if($isDemoMode)
            <div class="alert alert-warning">
                <strong>{{  __tr('Information masked for demo') }}</strong>
            </div>
@else
<code class="form-control bg-white  lw-mobile-app " readonly name="mobile_app_config" id="mobileAppConfig" rows="100">
// This is the mobile app configuration file content you can make
// changes to the file as per your requirements

// Warning do not change >>> -------------------------------------------

const String baseUrl = '{{ url('/') }}/';
const String baseApiUrl = '${baseUrl}api/';
// key for form encryption/decryptions
{{-- -----BEGIN PUBLIC KEY----- --}}
const String publicKey = '''{!! $isDemoMode ? $demoContent : YesSecurity::getPublicRsaKey() !!}''';
{{-- -----END PUBLIC KEY----- --}}
// ------------------------------------------- <<<<< do not change

// if you want to enable debug mode set it to true
// for the production make it false
const bool debug = {{ config('app.debug') ? 'true' : 'false' }};
const String version = '1.0.0';
const Map configItems = {
    'debug': debug,
    'appTitle': '{{ getAppSettings('default_language') }}',
    'default_language_code': '{{ getAppSettings('default_language') }}',
    'services': {
        'pusher': {
            'apiKey': '{{ $isDemoMode ? $demoContent : getAppSettings('pusher_app_key') }}',
            'cluster': '{{ $isDemoMode ? $demoContent : getAppSettings('pusher_app_cluster') }}'
        }
    }
};
</code>
@endif
        </div>
    </div>
</div>
@endsection()
