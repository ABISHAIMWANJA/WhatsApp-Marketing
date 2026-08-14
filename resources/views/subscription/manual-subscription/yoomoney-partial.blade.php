@if ($paymentMethod == 'yoomoney' && $subscriptionRequestRecord->status == 'initiated')

    @if (getAppSettings('enable_yoomoney'))
        <script src="https://yoomoney.ru/checkout.js"></script>
    @endif

    @push('appScripts')
        <script>
            (function() {
                'use strict';
                $("#yoomoneyPaymentButton").on('click', function(e) {
                    e.preventDefault(); // Prevent default button behavior
                    try {

                        __DataRequest.get(
                            "{{ route('yoomoney.checkout.payment', ['manualSubscriptionUid' => $subscriptionRequestRecord->_uid]) }}", {},
                            function(responseData) {
                             
                                if (responseData.data.success && responseData.data && responseData.data
                                    .payment_url) {
                                    var paymentUrl = responseData.data.payment_url;
                                    // Redirect user to YooMoney payment page
                                    window.location.href = paymentUrl;
                                } else {
                                    showAlert("Payment not created. Please check credentials.");
                                }
                            });


                    } catch (error) {
                        alert(error.message);
                    }

                });
            })();
        </script>
    @endpush
@endif
