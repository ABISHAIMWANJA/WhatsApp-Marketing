@if ($paymentMethod == 'razorpay' && $subscriptionRequestRecord->status == 'initiated')

@if (getAppSettings('enable_razorpay'))
    @if (getAppSettings('use_test_razorpay'))
        <script>
            var razorpayKey = '{{ getAppSettings('razorpay_testing_publishable_key') }}';
        </script>
    @else
        <script>
            var razorpayKey = '{{ getAppSettings('razorpay_live_publishable_key') }}';
        </script>
    @endif
@endif



    @if (getAppSettings('enable_razorpay'))
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif

    @push('appScripts')
    <script type="text/javascript">

    $("#lwRazorPayBtn").on('click', function() {
    try {
        var subscriptionCharge = "{{ $subscriptionRequestRecord->charges }}";
        var options = {
            "key": razorpayKey, // razorpay id
            "amount": getRazorPayAmount(subscriptionCharge).toFixed(
                2),
            "currency": "<?= getAppSettings('currency') ?>",
            "name": "{{ $planDetails['title'] }}", //subscription name
            handler: function(response) {
                if (!_.isEmpty(response.razorpay_payment_id)) {
                // payment checkout route
                 var razorPayRequestUrl = __Utils.apiURL(
                  "<?= route('write.razorpay.checkout') ?>"
                );
                 //post ajax request
                 __DataRequest.post(razorPayRequestUrl, {
                 'packageUid': '{{ $subscriptionRequestRecord->_uid }}',
                 'razorpayPaymentId': response.razorpay_payment_id
                  }, function(response) {
                // Successful capture! For dev/demo purposes:
                window.location = response.data.redirectRoute;
                 });
                 } else {
                    // Show an error page here, when an error occurs
                    showAlert('Payment Failed');
                  }
                            },
            "prefill": {
                "name": '<?= getUserAuthInfo('profile.full_name') ?>', //user name
                "email": '<?= getUserAuthInfo('profile.email') ?>',// user email
            },
            "notes": {
                "packageUid": '{{ $subscriptionRequestRecord->_uid }}', // subscription Uid
                "userId": '<?= getUserID() ?>', // user id
            },
            "theme": {
                "color": "#3399cc" // payment modal theme color
            },
            "modal": {
                ondismiss: function(e) {}
            }
        };
        var rzp1 = new Razorpay(
                            options); // will inherit key and image from above.
                        rzp1.open();
                    } catch (error) {
                        //bind error message on div
                        showAlert(error.message);
                    }
                });

    /**
     * get razor pay amount customise
     *
     *-------------------------------------------------------- */
     function getRazorPayAmount(amount) {
        return amount * 100;
    }
</script>
    @endpush
@endif