@extends('layouts.app', ['class' => 'main-content-has-bg'])
@section('content')
@include('layouts.headers.guest')
<div class="container lw-guest-page-container-block pb-2">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card lw-form-card-box shadow border-0">
                <h1 class="card-header text-center text-dark">
                    <div class="my-2">
                        <i class="fa fa-lock text-success"></i>  {{  __tr('Two Factor Challenge') }}
                        @if ($errors->any())
                            <div class="alert alert-danger mt-4">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </h1>
                <div class="card-body px-4 py-2">
                    <form id="lwTwoFactorChallengeForm" method="POST" action="{{ route('two-factor.login.store') }}">
                        @csrf
                        <input id="lw2FACode" class="form-control mt-2" placeholder="{{ __tr('Please enter your authentication code to login.') }}" type="text" name="code" value="" autofocus>
                        <label id="lw2FACode-error" class="lw-validation-error d-none" for="lw2FACode">{{ __tr('This field is required.') }}</label>
                        
                        <div class="text-center">
                            <button id="lw2FAChallengeButton" type="submit" class="btn btn-success my-4 btn-lg btn-block">{{ __tr('Submit') }}</button>
                        </div>
                    </form>
                </div>
                <hr class="m-0">
                <div class="card-footer text-center">
                    <div class="mb-3 mt-2 text-dark">
                        {{  __tr('Want to authenticate using recovery code? click below button') }}
                    </div>
                    <a href="{{ route('auth.two_factor_recovery.view') }}" class="btn btn-lg btn-warning">
                        {{ __tr('Go to Recovery Code Page') }}
                    </a>

                    <a href="{{ route('auth.login') }}" class="btn btn-lg btn-primary">
                        {{ __tr('Click here to login') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('appScripts')
<script>
document.addEventListener("DOMContentLoaded", function () {    
    $("#lwTwoFactorChallengeForm").on("submit", function (e) {
        e.preventDefault(); // stop default submit

        let codeInput = $("#lw2FACode");
        let errorLabel = $("#lw2FACode-error");
        let codeValue = codeInput.val().trim();

        // Reset old error styles
        errorLabel.addClass("d-none");
        $('#lw2FAChallengeButton').prop('disabled', false);

        if (codeValue === "") {
            errorLabel.removeClass("d-none");
        } else {
            $('#lw2FAChallengeButton').prop('disabled', true);
            this.submit();
        }
    });
});
</script>
@endpush