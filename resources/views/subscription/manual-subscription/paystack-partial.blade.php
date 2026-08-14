@if ($paymentMethod == 'paystack' && $subscriptionRequestRecord->status == 'initiated')

@if (getAppSettings('enable_paystack'))

<script src="https://js.paystack.co/v1/inline.js"></script>
@endif

@push('appScripts')
<script>
    (function() {
        'use strict'; 
   $("#paystackPaymentButton").on('click', function (e) {
        e.preventDefault(); // Prevent default button behavior
        try {
            // Define payment amount in kobo (multiply by 100)
            var useTestPaystack="<?= getAppSettings('use_test_paystack_checkout') ?>";
            var subscriptionCharge = "{{ $subscriptionRequestRecord->charges }}";
            var paystackAmount = getPaystackAmount(subscriptionCharge);
            var manual_subscription_uid = "{{ $subscriptionRequestRecord->_uid }}";
            var currency = "<?= getAppSettings('currency_value') ?>";
            var paystackKey = useTestPaystack ? "<?= getAppSettings('paystack_checkout_testing_publishable_key') ?> " : "<?= getAppSettings('paystack_checkout_live_publishable_key') ?>";
            // Set up Paystack payment handler
            const handler = PaystackPop.setup({
                key: paystackKey, //paystackKey Paystack public key
                email: "{{ auth()->user()->email }}", // Dynamically fetch user's email
                amount: paystackAmount, // Amount in kobo (KES cents)
                currency:currency, // Explicitly set the currency
                ref: "{{ uniqid('PS_') }}", // Unique transaction reference
                metadata: {
                    manual_subscription_uid: manual_subscription_uid, // Pass subscription UID in metadata
                },
                callback: function (response) {

                    // On successful payment, verify the transaction
                    verifyTransaction(response.reference, manual_subscription_uid);
                },
                onClose: function () {
                    // Handle payment modal close
                    showAlert("Payment window closed.");
                },
            });

            // Open Paystack payment modal
            handler.openIframe();
        } catch (error) {
            // Handle errors gracefully
            showAlert(error.message);
        }
    });

   //Convert subscription amount to Paystack-compatible format (kobo)
    function getPaystackAmount(amount) {
        return amount * 100; // Convert amount to kobo
    }

    //Verify the Paystack transaction
    function verifyTransaction(reference, subscriptionUid) {
        // Define the base URL with a placeholder
        const paystackVerifyUrl = "<?= route('verify.paystack.payment', ['reference' => 'reference']) ?>";

        // Replace the placeholder with the actual reference
        const url = paystackVerifyUrl.replace('reference', reference);

        // Make an AJAX call to your Laravel backend
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', // Ensure JSON format
                'X-CSRF-TOKEN': '{{ csrf_token() }}', // Include CSRF token
            },
            body: JSON.stringify({
                manual_subscription_uid: subscriptionUid // Send the subscription UID in the request body
            }),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            window.location =response.data.redirectRoute;
        })
        .catch(error => {
            console.error("{{ __tr('Error verifying transaction:') }}", error);
			});
    }
    })();
</script>

@endpush
@endif
