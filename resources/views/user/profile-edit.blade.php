@extends('layouts.app', ['title' => __tr('User Profile')])

@section('content')
@include('users.partials.header', [
'title' => __tr('Your Profile') . ' '. auth()->user()->name,
'description' => '',
'class' => 'col-lg-7'
])

<div class="container-fluid mt-lg--1"> 
    <div class="row">
        <div class="col-xl-6 order-xl-1">
            <div class="card shadow col-xl-12">
                <div class="card-body">
                    <x-lw.form :action="route('user.profile.update')">
                        <h3 class="text-dark">{{ __tr('User Information') }}</h3>
                        <hr class="my-3">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label" for="lwFirstName">{{ __tr('First Name') }}</label>
                                    <input type="text" name="first_name" id="lwFirstName"
                                        class="form-control form-control-alternative{{ $errors->has('first_name') ? ' is-invalid' : '' }}"
                                        placeholder="{{ __tr('First Name') }}"
                                        value="{{ old('first_name', auth()->user()->first_name) }}" required autofocus>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label" for="lwLastName">{{ __tr('Last Name') }}</label>
                                    <input type="text" name="last_name" id="lwLastName"
                                        class="form-control form-control-alternative{{ $errors->has('last_name') ? ' is-invalid' : '' }}"
                                        placeholder="{{ __tr('Last Name') }}"
                                        value="{{ old('last_name', auth()->user()->last_name) }}" required>
                                </div>
                            </div>
                        </div>
                         {{-- MOBILE NUMBER --}}
                         <div class="">
                            <div class="form-group">
                                <label class="form-control-label" for="input-mobile-number">{{ __tr('Mobile Number') }}</label>
                                <input class="form-control form-control-alternative{{ $errors->has('mobile_number') ? ' is-invalid' : '' }}" placeholder="{{ __tr('Mobile Number') }}" type="number" name="mobile_number" value="{{ old('mobile_number', auth()->user()->mobile_number) }}" required >
                            </div>
                        </div>
                        <h5> <span class="text-muted">{{__tr("Mobile number should be with country code without 0 or +")}}</span></h5>
               
                {{-- /MOBILE NUMBER --}}
                        <div class="">
                            <div class="form-group">
                                <label class="form-control-label" for="input-email">{{ __tr('Email') }}</label>
                                <input type="email" name="email" id="input-email"
                                    class="form-control form-control-alternative{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                    placeholder="{{ __tr('Email') }}" value="{{ old('email', auth()->user()->email) }}"
                                    required>
                            </div>
                            <div class="lw-form-footer">
                                <button type="submit" class="btn btn-primary mt-4">{{ __tr('Save') }}</button>
                            </div>
                        </div>
                    </x-lw.form>
                </div>

            </div>
            <div class="card col-xl-12 shadow mt-4">
                <div class="card-body">
                    <x-lw.form class="" data-secured="true" method="post"
                        action="{{ route('auth.password.update.process') }}" autocomplete="off">

                        <h3 class="text-dark">{{ __tr('Password') }}</h3>
                        <hr class="my-3">
                        @if (session('password_status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('password_status') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif

                        <div class="">
                            <div class="form-group{{ $errors->has('old_password') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="input-current-password">{{ __tr('Current Password')
                                    }}</label>
                                <input type="password" name="old_password" id="input-current-password"
                                    class="form-control form-control-alternative{{ $errors->has('old_password') ? ' is-invalid' : '' }}"
                                    placeholder="{{ __tr('Current Password') }}" value="" required>

                                @if ($errors->has('old_password'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('old_password') }}</strong>
                                </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="input-password">{{ __tr('New Password') }}</label>
                                <input type="password" name="password" id="input-password"
                                    class="form-control form-control-alternative{{ $errors->has('password') ? ' is-invalid' : '' }}"
                                    placeholder="{{ __tr('New Password') }}" value="" required>

                                @if ($errors->has('password'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="form-control-label" for="input-password-confirmation">{{ __tr('Confirm New
                                    Password') }}</label>
                                <input type="password" name="password_confirmation" id="input-password-confirmation"
                                    class="form-control form-control-alternative"
                                    placeholder="{{ __tr('Confirm New Password') }}" value="" required>
                            </div>
                            <div class="lw-form-footer">
                                <button type="submit" class="btn btn-primary mt-4">{{ __tr('Change password') }}</button>
                            </div>
                        </div>
                    </x-lw.form>
                </div>
            </div>
        </div>
        <div class="col-xl-6 order-xl-1">
             <div class="card shadow col-xl-12">
                <div class="card-body">
                     <h3 class="text-dark">{{ __tr('Two Factor Authentication') }}</h3>
                    <hr class="my-3">
                    @if (!auth()->user()->two_factor_secret)
                    <p class="card-text">{{ __tr('Click the button below to display the QR code and activate Two-Factor Authentication.') }}</p>
                    @endif
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        @if (!auth()->user()->two_factor_secret)
                            @if (isDemo() and isDemoVendorAccount())
                                <div class="alert alert-warning">
                                    {{ __tr('Two Factor Auth feature is disabled in demo account.') }}
                                </div>
                            @else
                            <button type="submit" class="btn btn-success mt-4">{{ __tr('Enable') }}</button>
                            @endif
                        @else
                        <div class="text-right">
                            {{-- Button for disable 2FA --}}
                          <button type="submit" class="btn btn-danger">{{ __tr('Disable') }}</button>
                            {{-- /Button for disable 2FA --}}
                        </div>
                            <div class="mb-4 font-medium text-sm">
                                @method('DELETE')
                                <div class="row">
                                    <div class="col-lg-12">
                                                              @if(auth()->user()->two_factor_confirmed_at)
                                            <div class="alert alert-success my-3 text-center">
                                                <i class="fa fa-user-shield fa-6x"></i>
                                              <p class="my-3">
                                                {{ __tr('Two Factor Auth has been setup and activated on __activatedAt__', [
                                                    '__activatedAt__' => formatDateTime(auth()->user()->two_factor_confirmed_at)
                                                ]) }}
                                              </p>
                                            </div>
                                            @endif
                                        @if(!auth()->user()->two_factor_confirmed_at)
                                        <fieldset>
                                            <legend>{{ __tr('Step 1 - Scan QR Code') }}</legend>
                                            <p>{!! 
                            __tr('Scan this QR code to set up your account using your preferred authenticator app. Popular choices include __googleAuthenticator__, __microsoftAuthenticator__ and __authy__.', [
                                '__googleAuthenticator__' => '<a href="https://support.google.com/accounts/answer/1066447?hl=en" target="_blank">'. __tr('Google Authenticator') .'</a>',
                                '__microsoftAuthenticator__' => '<a href="https://www.microsoft.com/en-in/security/mobile-authenticator-app" target="_blank">'. __tr('Microsoft Authenticator') .'</a>',
                                '__authy__' => '<a href="https://www.authy.com/download/" target="_blank">'. __tr('Authy') .'</a>'
                            ]) 
                        !!}</p>
                                            {{-- 2FA QR Code --}}
                                            <div class="text-center">
                                                {!! $qrCodeSvg !!}
                                            </div>
                                            {{-- /2FA QR Code --}}

                                            {{-- QR Code Secret Code --}}
                                            <div class="mt-2 text-center">
                                                <h1><strong>{{ decrypt(auth()->user()->two_factor_secret) }}</strong></h1>
                                            </div>
                                            {{-- /QR Code Secret Code --}}
                                            <hr>
                                            @if(!auth()->user()->two_factor_confirmed_at)
                                             <fieldset>
                                            <legend>{{ __tr('Step 2 - Activate') }}</legend>
                                            <p>{{  __tr('Once you scan with 2FA app, you need activate it') }}</p>
                                                <a class="lw-btn btn btn-lg btn-primary mt-1" data-toggle="modal" data-target="#lwActivate2FACodeModal">{{ __tr('Activate') }}</a>
                                                 </fieldset>
                                            @endif
                                        </fieldset>
                                        @endif
                                    </div>
                                </div>
                                <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
                                    <p class="alert alert-warning">{{  __tr('Write down following recovery codes, in case if you loose access to device app etc.') }}</p>
                                    <legend class="lw-fieldset-legend">
                                        {{ __tr('Recovery Codes') }}
                                    </legend>
                                    <button type="button" @click="panelOpened = !panelOpened"  class="btn btn-dark">{{  __tr('Show Recovery Codes') }}</button>
                                    @php
                                        $recoveryCodes = (array) auth()->user()->recoveryCodes();
                                    @endphp
                                    <div x-show="panelOpened">
                                        <ul class="list-group list-group-flush">
                                            @foreach($recoveryCodes as $code)
                                                <li class="list-group-item">{{ $code }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </fieldset>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<x-lw.modal id="lwActivate2FACodeModal" :header="__tr('Activate 2FA')" :hasForm="true">
    <x-lw.form id="lwActivate2FACodeForm" :action="route('user.profile.2fa.confirm')" :data-callback-params="['modalId' => '#lwActivate2FACodeModal']" data-callback="window.activate2Fa">
        {{-- Modal Body --}}
        <div class="lw-form-modal-body">
            <x-lw.input-field type="text" id="lwConfirmCode" data-form-group-class="" :label="__tr('Code')" name="confirm_code" required="true"/>
        </div>
        {{-- /Modal Body --}}

        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Activate') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
        <!-- /form footer -->
    </x-lw.form>
</x-lw.modal>

@push('appScripts')
<script>
    window.activate2Fa = function(responseData) {
        if (responseData.reaction == 1) {
            __Utils.viewReload();
        }
    }
</script>
@endpush