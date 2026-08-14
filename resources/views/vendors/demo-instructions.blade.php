<x-lw.modal id="lwRegisterDemoNumber" :header="__tr('Register Number for Demo')" :hasForm="true" class="show" >
    <div class="col mb-4">
        <div class="col p-4">
            <div class="alert alert-danger text-center">
                <h1 class="text-yellow">{{  __tr('Important for test demo') }}</h1>
                <p>{{  __tr('To prevent misuse of contacts number and for your privacy, you need to first register your number so you can test it with our demo. You can add multiple comma separated numbers') }}</p>
                <p>{{  __tr('If you have any difficulty for the demo or or having any purchase related query please feel free to') }}</p>
                <a type="button" class="btn btn-sm btn-default" target="_blank" href="https://api.whatsapp.com/send?phone={{ config('__misc.demo_test_recipient_contact_number') }}"><i class="fab fa-whatsapp"></i>  {{ __tr('WhatsApp Us at __whatsAppNumber__', [
                    '__whatsAppNumber__' => config('__misc.demo_test_recipient_contact_number')
                ]) }}</a>
               {{--  <div class="text-center">
                    <img class="lw-qr-image" src="{{ route('vendor.whatsapp_qr', [
                    'vendorUid' => 'xyz',
                    'phoneNumber' => cleanDisplayPhoneNumber(config('__misc.demo_test_recipient_contact_number')),
                ]) }}">
                </div> --}}
            </div>
       </div>
    </div>
    <h2 class="text-center col text-danger">{{  __tr('Add your Mobile number with country code and without 0 or + so you can see your contact here in panel/chatbox etc') }}</h2>
    <x-lw.form id="lwRegisterDemoNumberForm" :action="route('vendor.demo_number_register.write')"
        :data-callback-params="['modalId' => '#lwRegisterDemoNumber']" data-callback="appFuncs.modelSuccessCallback" class="">
        <!-- form body -->
        <div id="lwRegisterDemoNumberBody" class="lw-form-modal-body">
            <x-lw.input-field placeholder="{{ __tr('Mobile Number with country code eg. 91XXXXXXXXXX') }}" type="text" id="lwDemoPhoneNumbers" data-form-group-class="" :helpText="__tr('Add your mobile number with country code to test')" :label="__tr('You can add comma separated multiple numbers')" value="{{ getDemoNumbersForTest(null, true, true) }}" name="demo_phone_numbers" />
        </div>
        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Update') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Not Now') }}</button>
        </div>
    </x-lw.form>
</x-lw.modal>
@push('appScripts')
@if (!getDemoNumbersForTest(null, true, true))
<script>
    (function(window) {
    'use strict';
    $('#lwRegisterDemoNumber').modal('show');
    })(window);
</script>
@endif
@endpush