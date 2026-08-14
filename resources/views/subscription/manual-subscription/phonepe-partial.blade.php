@if ($paymentMethod == 'phonepe' && $subscriptionRequestRecord->status == 'initiated')
    @if (getAppSettings('enable_phonepe'))
        <script src="https://mercury.phonepe.com/web/bundle/checkout.js"></script>
    @endif
    @push('appScripts')
        <script type="text/javascript">
            $("#lwPhonePayBtn").on('click', function() {
                try {
                    var tokenUrl = "{{ $phonePeInitiatePaymentData['redirectUrl'] }}";
                    window.PhonePeCheckout.transact({ 
                        tokenUrl, 
                        callback: function(response) {
                            if (response === 'USER_CANCEL') {
                                console.log('cancelled by user...');
                                return;
                            } else if (response === 'CONCLUDED') {
                                var phonePeRequestUrl = __Utils.apiURL(
                                    "<?= route('phonepe.capture.payment') ?>"
                                );
                                __DataRequest.post(phonePeRequestUrl, {
                                    'merchantOrderId': "{{ $phonePeInitiatePaymentData['merchantOrderId'] }}"
                                }, function(responseData) {
                                    if (responseData && responseData.reaction == 1) {
                                        window.location = responseData.data.redirectRoute;
                                    } else {
                                        showAlert(responseData.data.errorMessage);
                                    }
                                });
                            } 
                        }, 
                        type: "IFRAME" 
                    });
                } catch (error) {
                    //bind error message on div
                    showAlert(error.message);
                }
            });
        </script>
    @endpush
@endif