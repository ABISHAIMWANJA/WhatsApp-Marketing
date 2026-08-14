<x-lw.modal id="lwRegisterPhoneNumber" :header="__tr('Call Register Phone Number API')" :hasForm="true">
    <!--  Edit Contact Form -->
    <x-lw.form id="lwReisterPhoneNumberForm" :action="route('vendor.whatsapp.register_phone_number.write')"
        :data-callback-params="['modalId' => '#lwRegisterPhoneNumber']" data-callback="appFuncs.modelSuccessCallback">
        <!-- form body -->
        <div id="lwRegisterPhoneNumberBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwRegisterPhoneNumberBody-template">
            <div class="alert alert-warning">
            {{ __tr('Registering phone number multiple times in certain time span may block for couple of days. So only register when you get a message that number is not registered and needs to call register API.') }}
        </div>
            <input type="hidden" name="phone_number_id" value="<%- __tData.phoneNumberId %>" />
            <x-lw.input-field type="text" id="lwPinCode" data-form-group-class="" :label="__tr('Enter PIN')" value="" name="pin" />
        </script>
        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Update') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
    </x-lw.form>
    <!--/  Edit Contact Form -->
</x-lw.modal>