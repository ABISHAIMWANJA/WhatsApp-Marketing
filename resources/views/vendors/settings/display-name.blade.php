<x-lw.modal id="lwDisplayNameUpdate" :header="__tr('Update Display Name')" :hasForm="true">
    <!--  Edit Contact Form -->
    <x-lw.form id="lwDisplayNameUpdateForm" :action="route('vendor.whatsapp.display_name.write')"
        :data-callback-params="['modalId' => '#lwDisplayNameUpdate']" data-callback="appFuncs.modelSuccessCallback">
        <!-- form body -->
        <div id="lwDisplayNameUpdateBody" class="lw-form-modal-body"></div>
        <script type="text/template" id="lwDisplayNameUpdateBody-template">

            <fieldset>
                <legend for="">{{  __tr('Status') }}</legend>
                <h3>
                    {{ __tr('Current Status') }}: <%= __tData.displayNameData.name_status %>

                    <% if (!_.isEmpty(__tData.pendingRequestData) && !_.isEmpty(__tData.pendingRequestData?.new_display_name) && __tData.pendingRequestData?.name_status != 'APPROVED') { %>
                    <div class="card-header">
                        <dl>
                            <dt>{{ __tr('New Display Name') }}</dt>
                            <dd><%= __tData.pendingRequestData?.new_display_name %></dd>
                            <dt>{{ __tr('New Name Status') }}</dt>
                            <dd><%= __tData.pendingRequestData?.name_status %></dd>
                        </dl>
                    </div>
                    <% } %>
                </h3>
            </fieldset>

            <input type="hidden" name="phoneNumberId" value="<%- __tData.phoneNumberId %>" />
            <!-- form fields -->
            <x-lw.input-field type="text" id="lwVerifiedNameField" data-form-group-class="" :label="__tr('Verified Name')" value="<%- __tData.displayNameData?.verified_name %>" name="verified_name" required/>
            
            <a href="https://www.facebook.com/business/help/757569725593362"target="_blank" class="float-right">{!! __tr('Display Name Help') !!}<i class="fas fa-external-link-alt"></i></a>
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