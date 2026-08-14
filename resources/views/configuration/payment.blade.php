<!-- Page Heading -->
<h1>
    <?= __tr('Payment Settings') ?>
</h1>
<!-- /Page Heading -->
<?php $isExtendedLicence = (getAppSettings('product_registration', 'licence') === 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9'); ?>
<!-- Payment Setting Form -->
@if(!$isExtendedLicence)
<div class="alert alert-warning my-3">
	<strong title="Extended Licence Required"><?= __tr('Extended Licence Required') ?></strong> <br>
	<?= __tr('To charge customers you need to buy an Extended licence. While you can test Stripe Gateway with Regular licence, But you need to purchase Extended licence to use Live Keys.') ?>
</div>
@endif
<hr>
        <fieldset class="lw-fieldset mb-3" x-cloak>
            <legend class="lw-fieldset-legend">
                <i class="fab fa-stripe"></i> <?= __tr('Gateway for Subscription (Recurring - Auto Debit)') ?>
            </legend>
            <!-- stripe settings -->
            <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
                <legend @click="panelOpened = !panelOpened" class="lw-fieldset-legend">
                    <i class="fab fa-stripe"></i> <?= __tr('Stripe Gateway') ?>
                    <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
                </legend>
                <!-- Payment Setting Form -->
                <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" data-callback="onPaymentGatewayFormCallback"
                    action="<?= route('manage.configuration.write', ['pageType' => request()->pageType]) ?>">
                    <!-- input field body -->
                    <div class="form-group mt-2">

                        @if(getAppSettings('enable_razorpay_subscription') and getAppSettings('lwEnableWhatsJetRazorpaySubscriptionAddon'))
                            <div class="px-4">
                                <div class="alert alert-danger">
                                    <strong>{{  __tr('Note:') }}</strong>
                                    {{  __tr(configItem('razorpay_enable_warning_message'), [
                                            '__firstPaymentGatewayName__' => 'Stripe',
                                            '__SecondPaymentGatewayName__' => 'Razorpay',
                                        ]) 
                                    }}
                                </div>
                            </div>
                        @endif

                        <!-- Enable stripe Checkout field -->
                        <div class="form-group pt-3">
                            <label for="lwEnableStripe">
                                <input type="hidden" name="enable_stripe" value="0">
                                <input type="checkbox" id="lwEnableStripe" data-lw-plugin="lwSwitchery" name="enable_stripe"
                                    <?=$configurationData['enable_stripe']==true ? 'checked' : '' ?>>
                                <?= __tr('Enable Stripe Subscription Checkout') ?>
                            </label>
                        </div>
                        <!-- / Enable stripe Checkout field -->
                        <span id="lwStripeCheckoutContainer">
                            <div class="p-2 border rounded">
                                <h3>{{  __tr('Options') }}</h3>
                                <span class="mr-4">
                                    <x-lw.checkbox id="lwEnableCalculateTaxes" data-size="small" data-color="orange" name="stripe_enable_calculate_taxes" :offValue="0" :checked="getAppSettings('stripe_enable_calculate_taxes')" data-lw-plugin="lwSwitchery" :label="__tr('Calculate Taxes by Stripe')" />
                                </span>
                                <x-lw.checkbox id="lwEnableInvoices" data-size="small" data-color="orange" name="stripe_enable_invoice_list" :offValue="0" :checked="getAppSettings('stripe_enable_invoice_list')" data-lw-plugin="lwSwitchery" :label="__tr('Enable Stripe Invoices List Table')" />
                            </div>
                            <!-- use testing stripe checkout input fieldset -->
                            <fieldset class="lw-fieldset mb-3">
                                <!-- use testing input radio field -->
                                <legend class="lw-fieldset-legend">
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input type="radio" id="lwUseStripeCheckoutTest" name="use_test_stripe"
                                            class="custom-control-input" value="1" <?=$configurationData['use_test_stripe']==true
                                            ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="lwUseStripeCheckoutTest">
                                            <?= __tr('Use Testing') ?>
                                        </label>
                                    </div>
                        </legend>
                        <!-- /use testing input radio field -->

                        <!-- show after added testing stipe checkout information -->
                        <div class="btn-group" id="lwTestStripeCheckoutExists">
                            <button type="button" disabled="true" class="btn btn-success lw-btn">
                                <?= __tr('Testing Stripe Checkout keys are installed.') ?>
                            </button>
                            <button type="button" class="btn btn-light lw-btn" id="lwUpdateTestStripeCheckout">
                                <?= __tr('Update') ?>
                            </button>
                        </div>
                        <!-- show after added testing stipe checkout information -->

                        <!-- stripe test secret key exists hidden field -->
                        <input type="hidden" name="stripe_test_keys_exist" id="lwStripeTestKeysExist"
                            value="<?= $configurationData['stripe_testing_secret_key'] ?>" />
                        <!-- stripe test secret key exists hidden field -->

                        <div id="lwTestStripeInputField">
                            <!-- Testing Secret Key Key -->
                            <div class="mb-3">
                                <label for="lwStripeTestSecretKey">
                                    <?= __tr('Secret Key') ?>
                                </label>
                                <input type="text" class="form-control form-control-user" value=""
                                    id="lwStripeTestSecretKey" name="stripe_testing_secret_key"
                                    placeholder="<?= __tr('Secret Key') ?>">
                            </div>
                            <!-- / Testing Secret Key Key -->

                            <!-- Testing Publish Key -->
                            <div class="mb-3">
                                <label for="lwStripeTestPublishKey">
                                    <?= __tr('Publish Key') ?>
                                </label>
                                <input type="text" class="form-control form-control-user" value=""
                                    id="lwStripeTestPublishKey" name="stripe_testing_publishable_key"
                                    placeholder="<?= __tr('Publish Key') ?>">
                            </div>
                            <!-- / Testing Publish Key -->

                            <!-- Stripe Webhook Secret (optional) -->
                            <div class="mb-3">
                                <label for="lwStripeTestWebhookSecret">
                                    <?= __tr('Stripe Webhook Secret (optional)') ?>
                                </label>
                                <input type="text" class="form-control form-control-user" value=""
                                    id="lwStripeTestWebhookSecret" name="stripe_testing_webhook_secret"
                                    placeholder="<?= __tr('Stripe Webhook Secret (optional)') ?>">
                            </div>
                            <!-- / Stripe Webhook Secret (optional) -->
                        </div>
                    </fieldset>
                    <!-- /use testing paypal checkout input fieldset -->

                    <!-- use live stripe checkout input fieldset -->
                    <fieldset class="lw-fieldset mb-3">
                        <!-- use live input radio field -->
                        <legend class="lw-fieldset-legend">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="lwUseStripeCheckoutLive" name="use_test_stripe"
                                    class="custom-control-input" value="0" <?=$configurationData['use_test_stripe']==false
                                    ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="lwUseStripeCheckoutLive">
                                    <?= __tr('Use Live') ?>
                                </label>
                            </div>
                        </legend>
                        <!-- /use live input radio field -->
                        @if($isExtendedLicence)
                        <!-- show after added Live stripe checkout information -->
                        <div class="btn-group" id="lwLiveStripeCheckoutExists">
                            <button type="button" disabled="true" class="btn btn-success lw-btn">
                                <?= __tr('Live Stripe Checkout keys are installed.') ?>
                            </button>
                            <button type="button" class="btn btn-light lw-btn" id="lwUpdateLiveStripeCheckout">
                                <?= __tr('Update') ?>
                            </button>
                        </div>
                        <!-- show after added Live stripe checkout information -->

                        <!-- stripe live secret key exists hidden field -->
                        <input type="hidden" name="stripe_live_keys_exist" id="lwStripeLiveKeysExist"
                            value="<?= $configurationData['stripe_live_secret_key'] ?>" />
                        <!-- stripe live secret key exists hidden field -->

                        <div id="lwLiveStripeInputField">
                            <div class="alert border-danger text-danger">
                                {{  __tr('While going live you may need to clear your existing subscription. It only may required if you are switching from test mode.') }}
                            <div>
                                <a class="btn btn-danger btn-sm lw-ajax-link-action mt-4" data-show-processing="true" data-method="post" data-confirm="{{ __tr('Are you sure? You want to delete all the subscriptions entries.') }}" href="{{ route('central.subscription.write.delete_all_entries') }}"> <i class="fa fa-cog"></i> {{  __tr('Delete existing subscription entries') }}</a>
                            </div>
                            </div>
                            <!-- Live Secret Key Key -->
                            <div class="mb-3">
                                <label for="lwStripeLiveSecretKey">
                                    <?= __tr('Secret Key') ?>
                                </label>
                                <input type="text" class="form-control form-control-user" value=""
                                    id="lwStripeLiveSecretKey" name="stripe_live_secret_key"
                                    placeholder="<?= __tr('Secret Key') ?>">
                            </div>
                            <!-- / Live Secret Key Key -->

                            <!-- Live Publish Key -->
                            <div class="mb-3">
                                <label for="lwStripeLivePublishKey">
                                    <?= __tr('Publish Key') ?>
                                </label>
                                <input type="text" class="form-control form-control-user" value=""
                                    id="lwStripeLivePublishKey" name="stripe_live_publishable_key"
                                    placeholder="<?= __tr('Publish Key') ?>">
                            </div>
                            <!-- / Live Publish Key -->

                            <!-- Live Stripe Webhook Secret (optional) -->
                            <div class="mb-3">
                                <label for="lwStripeLiveWebhookSecret">
                                    <?= __tr('Stripe Webhook Secret (optional)') ?>
                                </label>
                                <input type="text" class="form-control form-control-user" value=""
                                    id="lwStripeLiveWebhookSecret" name="stripe_live_webhook_secret"
                                    placeholder="<?= __tr('Stripe Webhook Secret (optional)') ?>">
                            </div>
                            <!-- / Live Stripe Webhook Secret (optional) -->
                        </div>
                        @else
                        <div class="alert alert-danger">
                            {{  __tr('Extended licence required to use live keys') }}
                        </div>
                        @endif
                    </fieldset>
                    <!-- /use live stripe checkout input fieldset -->
                    <div class="form-group">
                            <!-- Update Button -->
                    <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                        <?= __tr('Save') ?>
                    </a>
                    <!-- /Update Button -->
                    </div>
                    <fieldset>
                        <legend>{{  __tr('Auto Stripe Webhook Creation (Recommended)') }}</legend>
                        <div>
                            @if(!config('cashier.secret'))
                                <div class="alert alert-dark">
                                    {{  __tr('Stripe keys should be present to create webhook automatically.') }}
                                </div>
                            @endif
                            <div class="alert border-success text-success">
                                {{  __tr('Clicking on this button will create webhook in your stripe account with all required events.') }}
                            <div class="mt-4">
                                <a class="btn btn-success lw-ajax-link-action @if(!config('cashier.secret')) disabled @endif" data-show-processing="true" data-method="post" data-confirm="{{ __tr('Are you sure? It will call Stripe API to create webhook with events and will store it\'s secret into the system.') }}" href="{{ route('manage.configuration.create_stripe_webhook') }}"> <i class="fa fa-cog"></i> {{  __tr('Create Stripe Webhook Automatically') }}</a>
                            </div>
                            </div>
                            @php
                                $testWebHookCreated = getAppSettings('payment_gateway_info', 'auto_stripe_webhook_info.testing.created_at');
                                $liveWebHookCreated = getAppSettings('payment_gateway_info', 'auto_stripe_webhook_info.live.created_at');
                            @endphp
                            <div x-cloak x-data="{lastTestWebhookCreatedAt:'{{ $testWebHookCreated ? formatDateTime($testWebHookCreated) : '' }}',lastLiveWebhookCreatedAt:'{{ $liveWebHookCreated ? formatDateTime($liveWebHookCreated) : '' }}'}" class="my-3">
                                <div x-show="lastTestWebhookCreatedAt">
                                    {!! __tr('Last Test Webhook created at __createdAt__', [
                                    '__createdAt__' => '<span x-text="lastTestWebhookCreatedAt"></span>'
                                ]) !!}
                                </div>
                                <div x-show="lastLiveWebhookCreatedAt">
                                    {!! __tr('Last Live Webhook created at __createdAt__', [
                                    '__createdAt__' => '<span x-text="lastLiveWebhookCreatedAt"></span>'
                                ]) !!}
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <h2 class="col-12 text-center text-muted my-4">{{  __tr('-- OR --') }}</h2>
                    <fieldset>
                        <legend>{{  __tr('Manual Stripe Webhook Creation') }}</legend>
                        <div class="form-group">
                            <label for="lwStripeWebhookUrl">{{ __tr('Stripe Webhook') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly id="lwStripeWebhookUrl" value="{{ getViaSharedUrl(route('cashier.webhook')) }}">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-light" type="button" onclick="lwCopyToClipboard('lwStripeWebhookUrl')">
                                        <?= __tr('Copy') ?>
                                    </button>
                                </div>
                            </div>
                            <div class="alert alert-light my-3">
                                <h3>{{  __tr('Select following events whiles creating webhook') }}</h3>
                                <p>{{  __tr('customer.subscription.created, customer.subscription.updated, customer.subscription.deleted, customer.updated, customer.deleted, payment_method.automatically_updated, invoice.payment_action_required, invoice.payment_succeeded') }}</p>
                            </div>
                            <div class="text-danger help-text mt-2 text-sm">{{  __tr('IMPORTANT: It is very important that you should add this Webhook to Stripe account, as all the payment information gets updated using this webhook.') }}</div>
                        </div>
                    </fieldset>
                    <!-- / stripe settings -->
                <fieldset>
                    <legend>{{  __tr('Note') }}</legend>
                    {{  __tr('Please make sure you have enabled billing portal link in your Stripe account') }}
                    <a target="_blank" href="https://dashboard.stripe.com/settings/billing/portal">https://dashboard.stripe.com/settings/billing/portal</a>
                </fieldset>
                </span>
                <hr class="my-4">
                <div class="form-group">
                            <!-- Update Button -->
                    <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                        <?= __tr('Save') ?>
                    </a>
                    <!-- /Update Button -->
                    </div>
                </div>
                <!-- / input field body -->
                </form>
            </fieldset>
            <!-- /Payment Setting Form -->

            <!-- For Recurring Payment Only -->
            @stack('autoSubscriptionSettingStack')
            <!-- /For Recurring Payment Only -->
        </fieldset>

        <!-- For one time Payment Only -->
        @stack('oneTimeGatewaySettingStackStart')
        <!-- /For one time Payment Only -->

        <!-- Paypal checkout start -->
          <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
            <legend class="lw-fieldset-legend" @click="panelOpened = !panelOpened">
                <i class="fab fa-paypal"></i> <?= __tr('PayPal') ?>
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>
            <!-- Payment Setting Form -->
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" data-callback="onPaymentGatewayFormCallback"
                action="<?= route('manage.configuration.write', ['pageType' => 'paypal_payment']) ?>">
                <!-- input field body -->
                <div class="form-group mt-2">

                    <!-- Enable Paypal Checkout field -->
                    <div class="form-group pt-3">
                        <label for="lwEnablePaypal">
                            <input type="hidden" name="enable_paypal" value="0">
                            <input type="checkbox" id="lwEnablePaypal" data-lw-plugin="lwSwitchery" name="enable_paypal"
                                <?= getAppSettings('enable_paypal')==true ? 'checked' : '' ?>>
                            <?= __tr('Enable PayPal Checkout') ?>
                        </label>
                    </div>
                    <div>
                        <p class="mt-3 ml-3"><?= __tr('You can create PayPal credential') ?><a href="https://www.paypal.com/signin?returnUri=https%3A%2F%2Fdeveloper.paypal.com%2Fdeveloper%2Fapplications&intent=developer" target="_blank">{{  __tr('click here') }}</a></p>

                     </div>
                    <!-- / Enable Paypal Checkout field -->
                    <span id="lwPayPalCheckoutContainer">
                        <!-- use testing Paypal checkout input fieldset -->
                        <fieldset class="lw-fieldset mb-3">
                            <!-- use testing input radio field -->
                            <legend class="lw-fieldset-legend">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="lwUsePaypalCheckoutTest"  name="use_test_paypal_checkout"
                                        class="custom-control-input" value="1" <?= getAppSettings('use_test_paypal_checkout')==true
                                        ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="lwUsePaypalCheckoutTest">
                                        <?= __tr('Use Testing') ?>
                                    </label>
                                </div>
                    </legend>
                    <!-- /use testing input radio field -->

                    <!-- show after added testing Paypal checkout information -->
                    <div class="btn-group" id="lwTestPaypalCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Testing PayPal Checkout keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateTestPaypalCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added testing Paypal checkout information -->

                             <!-- paypal test secret key exists hidden field -->
                             <input type="hidden" name="paypal_test_keys_exist" id="lwPaypalTestKeysExist"
                             value="<?= getAppSettings('paypal_checkout_testing_secret_key') ?>" />
                         <!-- paypal test secret key exists hidden field -->


                    <div id="lwTestPaypalInputField">

                        <!-- Testing Publish Key -->
                        <div class="mb-3">
                            <label for="lwPaypalTestPublishKey">
                                <?= __tr('Client Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaypalTestPublishKey" name="paypal_checkout_testing_publishable_key"
                                placeholder="<?= __tr('Client Key') ?>">
                        </div>
                        <!-- / Testing Publish Key -->
                           <!-- Testing Secret Key Key -->
                           <div class="mb-3">
                            <label for="lwPaypalTestSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaypalTestSecretKey" name="paypal_checkout_testing_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Testing Secret Key Key -->
                    </div>
                </fieldset>
                <!-- /use testing paypal checkout input fieldset -->

                <!-- use live Paypal checkout input fieldset -->
                <fieldset class="lw-fieldset mb-3">
                    <!-- use live input radio field -->
                    <legend class="lw-fieldset-legend">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lwUsePaypalCheckoutLive" name="use_test_paypal_checkout"
                                class="custom-control-input" value="0" <?= getAppSettings('use_test_paypal_checkout')==false
                                ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="lwUsePaypalCheckoutLive">
                                <?= __tr('Use Live') ?>
                            </label>
                        </div>
                    </legend>
                    <!-- /use live input radio field -->
                    @if($isExtendedLicence)
                    <!-- show after added Live Paypal checkout information -->
                    <div class="btn-group" id="lwLivePaypalCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Live PayPal Checkout keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateLivePaypalCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added Live Paypal checkout information -->


                    <div id="lwLivePaypalInputField">
                        <!-- Live Publish Key -->
                        <div class="mb-3">
                            <label for="lwPaypalLivePublishKey">
                                <?= __tr('Client Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaypalLivePublishKey" name="paypal_checkout_live_publishable_key"
                                placeholder="<?= __tr('Client Key') ?>">
                        </div>
                        <!-- / Live Client Key -->
                           <!-- Live Secret Key Key -->
                           <div class="mb-3">
                            <label for="lwPaypalLiveSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaypalLiveSecretKey" name="paypal_checkout_live_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Live Secret Key Key -->
                    </div>
                    @else
					<div class="alert alert-danger">
						{{  __tr('Extended licence required to use live keys') }}
					</div>
					@endif
                </fieldset>
                <!-- /use live Paypal checkout input fieldset -->
            </span>
            <hr class="my-4">
            <div class="form-group">
                        <!-- Update Button -->
                <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </a>
                <!-- /Update Button -->
                </div>
            </div> 
            <!-- / input field body -->
            </form>
        </fieldset>
        <!-- /Payment Setting Form -->
        <!-- /Paypal checkout -->
        <!-- Razorpay start -->
        <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
            <legend class="lw-fieldset-legend" @click="panelOpened = !panelOpened">
                <img height="20" src="{{ asset('imgs/razorpay.png') }}" alt="<?= __tr('Razorpay') ?>">                
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>
            <!-- Payment Setting Form -->
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" data-callback="onPaymentGatewayFormCallback"
                action="<?= route('manage.configuration.write', ['pageType' => 'razorpay_payment']) ?>">
                <!-- input field body -->
                <div class="form-group mt-2">

                    <!-- Enable razorpay Checkout field -->
                    <div class="form-group pt-3">
                        <label for="lwEnableRazorpay">
                            <input type="hidden" name="enable_razorpay" value="0">
                            <input type="checkbox" id="lwEnableRazorpay" data-lw-plugin="lwSwitchery" name="enable_razorpay"
                                <?= getAppSettings('enable_razorpay')==true ? 'checked' : '' ?>>
                            <?= __tr('Enable Razorpay') ?>
                        </label>
                    </div>
                    {{-- webhook URL --}}
                    <div class="form-group mt-3">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text text-primary" id="basic-addon1">{{  __tr('Webhook') }}</span>
                            </div>
                            <input type="text" class="form-control" readonly id="lwRazorPayWebhookUrl" value="{{ getViaSharedUrl(route('razorpay-webhook')) }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="lwCopyToClipboard('lwRazorPayWebhookUrl')">
                                    <i class="fas fa-solid fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- /webhook URL --}}
                    <div>
                        <p class="mt-3 ml-3"><?= __tr('You can create Razorpay credential') ?><a href="https://dashboard.razorpay.com/" target="_blank">{{  __tr('click here') }}</a>.</p>

                    </div>

                    <!-- / Enable razorpay Checkout field -->
                    <span id="lwPayRazorpayContainer">
                        <!-- use testing razorpay checkout input fieldset -->
                        <fieldset class="lw-fieldset mb-3">
                            <!-- use testing input radio field -->
                            <legend class="lw-fieldset-legend">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="lwUseRazorpayTest"  name="use_test_razorpay"
                                        class="custom-control-input" value="1" <?= getAppSettings('use_test_razorpay')==true
                                        ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="lwUseRazorpayTest">
                                        <?= __tr('Use Testing') ?>
                                    </label>
                                </div>
                    </legend>
                    <!-- /use testing input radio field -->

                    <!-- show after added testing Razorpay information -->
                    <div class="btn-group" id="lwTestRazorpayExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Testing Razorpay keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateTestRazorpay">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added testing Razorpay information -->

                            <!-- razorpay test secret key exists hidden field -->
                            <input type="hidden" name="razorpay_test_keys_exist" id="lwRazorpayTestKeysExist"
                            value="<?= getAppSettings('razorpay_testing_secret_key') ?>" />
                        <!-- razorpay test secret key exists hidden field -->

                    <div id="lwTestRazorpayInputField">
                        <!-- Testing Publish Key -->
                        <div class="mb-3">
                            <label for="lwRazorpayTestPublishKey">
                                <?= __tr('Key Id') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwRazorpayTestPublishKey" name="razorpay_testing_publishable_key"
                                placeholder="<?= __tr('Key Id') ?>">
                        </div>
                        <!-- / Testing Publish Key -->
                        <!-- Testing Secret Key Key -->
                        <div class="mb-3">
                            <label for="lwRazorpayTestSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwRazorpayTestSecretKey" name="razorpay_testing_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Testing Secret Key Key -->
                        <!-- Testing webhook Key -->
                        <div class="mb-3">
                            <label for="lwRazorpayTestWebhook">
                                <?= __tr('Secret Test Webhook') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwRazorpayTestWebhook" name="razorpay_testing_webhook_secret"
                                placeholder="<?= __tr('Webhook secret') ?>">
                        </div>
                        <!-- /Testing webhook Key -->
                    </div>
                </fieldset>
                <!-- /use testing razorpay input fieldset -->

                <!-- use live razorpay input fieldset -->
                <fieldset class="lw-fieldset mb-3">
                    <!-- use live input radio field -->
                    <legend class="lw-fieldset-legend">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lwUseRazorpayCheckoutLive" name="use_test_razorpay"
                                class="custom-control-input" value="0" <?= getAppSettings('use_test_razorpay')==false
                                ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="lwUseRazorpayCheckoutLive">
                                <?= __tr('Use Live') ?>
                            </label>
                        </div>
                    </legend>
                    <!-- /use live input radio field -->
                    @if($isExtendedLicence)
                    <!-- show after added Live Razorpay information -->
                    <div class="btn-group" id="lwLiveRazorpayCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Live Razorpay keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateLiveRazorpayCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added Live Razorpay information -->

                    <div id="lwLiveRazorpayInputField">
                        <!-- Live Publish Key -->
                        <div class="mb-3">
                            <label for="lwRazorpayLivePublishKey">
                                <?= __tr('Key Id') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwRazorpayLivePublishKey" name="razorpay_live_publishable_key"
                                placeholder="<?= __tr('Key Id') ?>">
                        </div>
                        <!-- / Live Client Key -->
                        <!-- Live Secret Key Key -->
                        <div class="mb-3">
                            <label for="lwRazorpayLiveSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwRazorpayLiveSecretKey" name="razorpay_live_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Live Secret Key Key -->
                        <!-- live webhook Key -->
                        <div class="mb-3">
                            <label for="lwRazorpayLiveWebhook">
                                <?= __tr('Secret Live Webhook') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwRazorpayLiveWebhook" name="razorpay_live_webhook_secret"
                                placeholder="<?= __tr('Webhook secret') ?>">
                        </div>
                        <!-- /live webhook Key -->
                    </div>
                    @else
                    <div class="alert alert-danger">
                        {{  __tr('Extended licence required to use live keys') }}
                    </div>
                    @endif
                </fieldset>
                <!-- /use live razorpay checkout input fieldset -->
            </span>
            <hr class="my-4">
            <div class="form-group">
                        <!-- Update Button -->
                <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </a>
                <!-- /Update Button -->
                </div>
            </div>
            <!-- / input field body -->
            </form>
        </fieldset>
        <!-- /Razorpay end -->

        <!-- UPI Payment start -->
        <fieldset x-data="{panelOpened:false}">
            <legend @click="panelOpened = !panelOpened"> <img height="50" src="{{ asset('imgs/upi-icon.png') }}"> {{ __tr('UPI Payments for India - Offline/Manual') }}
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>
            @if($isExtendedLicence)
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post"
            action="<?= route('manage.configuration.write', ['pageType' => 'upi_payment']) ?>" x-cloak x-data="{}">
        <div class="alert alert-light my-3">{{  __tr('User will add payment details you need to confirm it manually and update subscription as active etc for the particular vendor from manual subscriptions.') }}</div>
            <div class="col-sm-12 col-md-6 col-lg-4">
                <div class="my-4">
                    <x-lw.checkbox id="lwEnableUpiPayment" :offValue="0" :checked="getAppSettings('enable_upi_payment')" name="enable_upi_payment" data-lw-plugin="lwSwitchery" :label="__tr('Enable UPI Payments')" />
                </div>
                <x-lw.input-field type="text" id="lwUpiPaymentAddress" value="{{ getAppSettings('payment_upi_address') }}" data-form-group-class="" :label="__tr('UPI Address')" name="payment_upi_address" />
                <div class="form-group">
                    <label for="lwUpiCustomerInstructions">{{  __tr('Customer Instructions or Notes') }}</label>
                    <textarea class="form-control" name="payment_upi_customer_notes" id="lwUpiCustomerInstructions" rows="4">{{ getAppSettings('payment_upi_customer_notes') }}</textarea>
                </div>
            <div class="form-group">
                {{-- submit button --}}
            <button type="submit" href class="btn btn-primary btn-user lw-btn-block-mobile">
                <?= __tr('Save') ?>
            </button>
            </div>
         </div>
            </form>
            @else
            <div class="alert alert-danger">
                {{  __tr('Extended licence required to enable it') }}
            </div>
            @endif
        </fieldset>
        <!-- /UPI Payment end -->
        <!-- Bank Transfer -->
        <fieldset x-data="{panelOpened:false}">
            <legend @click="panelOpened = !panelOpened"> <i class="fas fa-university"></i> {{ __tr('Bank Transfer - Offline/Manual') }}
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>

            @if($isExtendedLicence)
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post"
            action="<?= route('manage.configuration.write', ['pageType' => 'bank_transfer']) ?>" x-cloak x-data="{}">
            <div class="alert alert-light my-3">{{  __tr('User will add payment details you need to confirm it manually and update subscription as active etc for the particular vendor from manual subscriptions.') }}</div>
            <div class="col-sm-12 col-lg-8">
                <div class="my-4">
                    <x-lw.checkbox id="lwEnableBankTransfer" :offValue="0" :checked="getAppSettings('enable_bank_transfer')" name="enable_bank_transfer" data-lw-plugin="lwSwitchery" :label="__tr('Enable Bank Transfer')" />
                </div>
                <div class="form-group">
                    <label for="lwBankTransferInstructions">{{  __tr('Bank Transfer Instructions') }}</label>
                    <textarea class="form-control" name="bank_transfer_instructions" id="lwBankTransferInstructions" rows="10">{{ getAppSettings('bank_transfer_instructions') }}</textarea>
                </div>
            <div class="form-group">
                {{-- submit button --}}
            <button type="submit" href class="btn btn-primary btn-user lw-btn-block-mobile">
                <?= __tr('Save') ?>
            </button>
            </div>
            </div>
            </form>
            @else
            <div class="alert alert-danger">
                {{  __tr('Extended licence required to enable it') }}
            </div>
            @endif
        </fieldset>
        <!-- /Bank Transfer -->
          <!-- Paystack checkout  start -->
          <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
            <legend class="lw-fieldset-legend" @click="panelOpened = !panelOpened">
                <img height="45" src="{{ asset('imgs/paystack-icon.png') }}" alt="<?= __tr('Paystack') ?>">
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>
            <!-- Payment Setting Form -->
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" data-callback="onPaymentGatewayFormCallback"
                action="<?= route('manage.configuration.write', ['pageType' => 'paystack_payment']) ?>">
                <!-- input field body -->
                <div class="form-group mt-2">

                    <!-- Enable Paystack Checkout field -->
                    <div class="form-group pt-3">
                        <label for="lwEnablePaystack">
                            <input type="hidden" name="enable_paystack" value="0">
                            <input type="checkbox" id="lwEnablePaystack" data-lw-plugin="lwSwitchery" name="enable_paystack"
                                <?= getAppSettings('enable_paystack')==true ? 'checked' : '' ?>>
                            <?= __tr('Enable Paystack Checkout') ?>
                        </label>
                    </div>
                       
                    <!-- / Enable Paystack Checkout field -->
                    <span id="lwPaystackCheckoutContainer">
                        <!-- use testing Paystack checkout input fieldset -->
                        <fieldset class="lw-fieldset mb-3">
                            <!-- use testing input radio field -->
                            <legend class="lw-fieldset-legend">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="lwUsePaystackCheckoutTest"  name="use_test_paystack_checkout"
                                        class="custom-control-input" value="1" <?= getAppSettings('use_test_paystack_checkout')==true
                                        ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="lwUsePaystackCheckoutTest">
                                        <?= __tr('Use Testing') ?>
                                    </label>
                                </div>
                    </legend>
                    <!-- /use testing input radio field -->

                    <!-- show after added testing Paystack checkout information -->
                    <div class="btn-group" id="lwTestPaystackCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Testing Paystack Checkout keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateTestPaystackCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added testing Paystack checkout information -->

                             <!-- paypal test secret key exists hidden field -->
                             <input type="hidden" name="paystack_test_keys_exist" id="lwPaystackTestKeysExist"
                             value="<?= getAppSettings('paystack_checkout_testing_secret_key') ?>" />
                         <!-- paypal test secret key exists hidden field -->


                    <div id="lwTestPaystackInputField">

                       
                           <!-- Testing Secret Key Key -->
                           <div class="mb-3">
                            <label for="lwPaystackTestSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaystackTestSecretKey" name="paystack_checkout_testing_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                         <!-- Testing Publish Key -->
                         <div class="mb-3">
                            <label for="lwPaystackTestPublishKey">
                                <?= __tr('Public Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaystackTestPublishKey" name="paystack_checkout_testing_publishable_key"
                                placeholder="<?= __tr('Public Key') ?>">
                        </div>
                        <!-- / Testing Publish Key -->
                    </div>
                </fieldset>
                <!-- /use testing paypal checkout input fieldset -->

                <!-- use live Paystack checkout input fieldset -->
                <fieldset class="lw-fieldset mb-3">
                    <!-- use live input radio field -->
                    <legend class="lw-fieldset-legend">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lwUsePaystackCheckoutLive" name="use_test_paystack_checkout"
                                class="custom-control-input" value="0" <?= getAppSettings('use_test_paystack_checkout')==false
                                ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="lwUsePaystackCheckoutLive">
                                <?= __tr('Use Live') ?>
                            </label>
                        </div>
                    </legend>
                    <!-- /use live input radio field -->
                    @if($isExtendedLicence)
                    <!-- show after added Live Paystack checkout information -->
                    <div class="btn-group" id="lwLivePaystackCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Live Paystack Checkout keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateLivePaystackCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added Live Paystack checkout information -->


                    <div id="lwLivePaystackInputField">
                      
                           <!-- Live Secret Key Key -->
                           <div class="mb-3">
                            <label for="lwPaystackLiveSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaystackLiveSecretKey" name="paystack_checkout_live_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                           </div>
                          <!-- Live Publish Key -->
                          <div class="mb-3">
                            <label for="lwPaystackLivePublishKey">
                                <?= __tr('Public Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPaystackLivePublishKey" name="paystack_checkout_live_publishable_key"
                                placeholder="<?= __tr('Public Key') ?>">
                        </div>
                        <!-- / Live Client Key -->
                    </div>
                    @else
					<div class="alert alert-danger">
						{{  __tr('Extended licence required to use live keys') }}
					</div>
					@endif
                </fieldset>
                <!-- /use live Paystack checkout input fieldset -->
            </span>
            {{-- webhook URL --}}
            <div class="form-group mt-3">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-primary" id="paystackWebhookUrl">{{  __tr('Webhook') }}</span>
                    </div>
                    <input type="text" class="form-control" readonly id="lwPaystackWebhookUrl" value="{{ getViaSharedUrl(route('paystack-webhook')) }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" onclick="lwCopyToClipboard('lwPaystackWebhookUrl')">
                            <i class="fas fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            {{-- /webhook URL --}}
            {{-- callback URL --}}
            <div class="form-group mt-3">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text text-primary" id="paystackCallbackUrl">{{  __tr('Callback URL') }}</span>
                    </div>
                    <input type="text" class="form-control" readonly id="lwPaystackCallBackUrl" value="{{ getViaSharedUrl(route('subscription.read.show')) }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" onclick="lwCopyToClipboard('lwPaystackCallBackUrl')">
                            <i class="fas fa-solid fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            {{-- /callback URL --}}
            <div>
                <p class="mt-3 ml-3"><?= __tr('You can create Paystack credential') ?><a href="https://dashboard.paystack.com/" target="_blank">{{  __tr('click here') }}</a>.</p>

             </div>
            <hr class="my-4">
            <div class="form-group">
                        <!-- Update Button -->
                <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </a>
                <!-- /Update Button -->
                </div>
            </div> 
            <!-- / input field body -->
            </form>
        </fieldset>
        <!-- /Payment Setting Form -->
        <!-- /Paystack checkout -->
         <!-- yoomoney start -->
         <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
            <legend class="lw-fieldset-legend" @click="panelOpened = !panelOpened">
                <img height="50" src="{{ asset('imgs/yoomoney.png') }}" alt="<?= __tr('YooMoney') ?>">
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>
            <!-- Payment Setting Form -->
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" data-callback="onPaymentGatewayFormCallback"
                action="<?= route('manage.configuration.write', ['pageType' => 'yoomoney_payment']) ?>">
                <!-- input field body -->
                <div class="form-group mt-2">

                    <!-- Enable YooMoney Checkout field -->
                    <div class="form-group pt-3">
                        <label for="lwEnableYooMoney">
                            <input type="hidden" name="enable_yoomoney" value="0">
                            <input type="checkbox" id="lwEnableYooMoney" data-lw-plugin="lwSwitchery" name="enable_yoomoney"
                                <?= getAppSettings('enable_yoomoney')==true ? 'checked' : '' ?>>
                            <?= __tr('Enable YooMoney') ?>
                        </label>
                    </div>
                    {{-- webhook URL --}}
                    <div class="form-group mt-3">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text text-primary" id="basic-addon1">{{  __tr('Webhook') }}</span>
                            </div>
                            <input type="text" class="form-control" readonly id="lwYooMoneyWebhookUrl" value="{{ getViaSharedUrl(route('yoomoney-webhook')) }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="lwCopyToClipboard('lwYooMoneyWebhookUrl')">
                                    <i class="fas fa-solid fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- /webhook URL --}}
                    <div>
                        <p class="mt-3 ml-3"><?= __tr('You can create YooMoney credential') ?><a href="https://yookassa.ru/developers/payment-acceptance/getting-started/quick-start?lang=en" target="_blank">{{  __tr('click here') }}</a></p>

                    </div>
                    <!-- / Enable YooMoney Checkout field -->
                    <span id="lwPayYooMoneyContainer">
                        <!-- use testing YooMoney checkout input fieldset -->
                        <fieldset class="lw-fieldset mb-3">
                            <!-- use testing input radio field -->
                            <legend class="lw-fieldset-legend">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="lwUseYooMoneyTest"  name="use_test_yoomoney"
                                        class="custom-control-input" value="1" <?= getAppSettings('use_test_yoomoney')==true
                                        ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="lwUseYooMoneyTest">
                                        <?= __tr('Use Testing') ?>
                                    </label>
                                </div>
                    </legend>
                    <!-- /use testing input radio field -->

                    <!-- show after added testing YooMoney information -->
                    <div class="btn-group" id="lwTestYooMoneyExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Testing YooMoney keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateTestYooMoney">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added testing YooMoney information -->

                            <!-- YooMoney test secret key exists hidden field -->
                            <input type="hidden" name="yoomoney_test_keys_exist" id="lwYooMoneyTestKeysExist"
                            value="<?= getAppSettings('yoomoney_testing_secret_key') ?>" />
                        <!-- YooMoney test secret key exists hidden field -->

                    <div id="lwTestYooMoneyInputField">
                        <!-- Testing Publish Key -->
                        <div class="mb-3">
                            <label for="lwYooMoneyTestPublishKey">
                                <?= __tr('Shop Id') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwYooMoneyTestPublishKey" name="yoomoney_testing_shop_id"
                                placeholder="<?= __tr('Shop Id') ?>">
                        </div>
                        <!-- / Testing Publish Key -->
                        <!-- Testing Secret Key Key -->
                        <div class="mb-3">
                            <label for="lwYooMoneyTestSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwYooMoneyTestSecretKey" name="yoomoney_testing_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Testing Secret Key Key -->
                       
                    </div>
                </fieldset>
                <!-- /use testing YooMoney input fieldset -->

                <!-- use live YooMoney input fieldset -->
                <fieldset class="lw-fieldset mb-3">
                    <!-- use live input radio field -->
                    <legend class="lw-fieldset-legend">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lwUseYooMoneyCheckoutLive" name="use_test_yoomoney"
                                class="custom-control-input" value="0" <?= getAppSettings('use_test_yoomoney')==false
                                ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="lwUseYooMoneyCheckoutLive">
                                <?= __tr('Use Live') ?>
                            </label>
                        </div>
                    </legend>
                    <!-- /use live input radio field -->
                    @if($isExtendedLicence)
                    <!-- show after added Live YooMoney information -->
                    <div class="btn-group" id="lwLiveYooMoneyCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Live YooMoney keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateLiveYooMoneyCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added Live YooMoney information -->

                    <div id="lwLiveYooMoneyInputField">
                        <!-- Live Publish Key -->
                        <div class="mb-3">
                            <label for="lwYooMoneyLivePublishKey">
                                <?= __tr('Shop Id') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwYooMoneyLivePublishKey" name="yoomoney_live_shop_id"
                                placeholder="<?= __tr('Shop Id') ?>">
                        </div>
                        <!-- / Live Client Key -->
                        <!-- Live Secret Key Key -->
                        <div class="mb-3">
                            <label for="lwYooMoneyLiveSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwYooMoneyLiveSecretKey" name="yoomoney_live_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Live Secret Key Key -->

                         <!-- Live Vat Key Key -->
                         <div class="mb-3">
                            <label for="lwYooMoneyLiveVatKey">
                                <?= __tr('Vat Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value="1"
                                id="lwYooMoneyLiveVatKey" name="yoomoney_live_vat_id"
                                placeholder="<?= __tr('Vat Key') ?>">
                        </div>
                        <!-- / Live Vat Key Key -->
                    </div>
                    @else
                    <div class="alert alert-danger">
                        {{  __tr('Extended licence required to use live keys') }}
                    </div>
                    @endif
                </fieldset>
                <!-- /use live YooMoney checkout input fieldset -->
            </span>
            <hr class="my-4">
            <div class="form-group">
                        <!-- Update Button -->
                <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </a>
                <!-- /Update Button -->
                </div>
            </div>
            <!-- / input field body -->
            </form>
        </fieldset>
        <!-- /yoomoney end -->

        <!-- phonepe start -->
        <fieldset class="lw-fieldset mb-3" x-data="{panelOpened:false}" x-cloak>
            <legend class="lw-fieldset-legend" @click="panelOpened = !panelOpened">
                <img height="50" src="{{ asset('imgs/phonepe.png') }}" alt="<?= __tr('PhonePe') ?>">
                <small class="text-muted">{{  __tr('Click to expand/collapse') }}</small>
            </legend>
            <!-- Payment Setting Form -->
            <form x-show="panelOpened" class="lw-ajax-form lw-form" method="post" data-callback="onPaymentGatewayFormCallback"
                action="<?= route('manage.configuration.write', ['pageType' => 'phonepe_payment']) ?>">
                <!-- input field body -->
                <div class="form-group mt-2">

                    <!-- Enable PhonePe Checkout field -->
                    <div class="form-group pt-3">
                        <label for="lwEnablePhonePe">
                            <input type="hidden" name="enable_phonepe" value="0">
                            <input type="checkbox" id="lwEnablePhonePe" data-lw-plugin="lwSwitchery" name="enable_phonepe"
                                <?= getAppSettings('enable_phonepe')==true ? 'checked' : '' ?>>
                            <?= __tr('Enable PhonePe') ?>
                        </label>
                    </div>
                    <div>
                        <p class="mt-3 ml-3"><?= __tr('You can create PhonePe credential here - ') ?><a href="https://business.phonepe.com/register?utm_source=homepage_banner_pg_pfb_website" target="_blank">{{  __tr('click here') }}</a></p>

                    </div>
                    <!-- / Enable PhonePe Checkout field -->
                    <span id="lwPhonePeContainer">
                        <!-- use testing PhonePe checkout input fieldset -->
                        <fieldset class="lw-fieldset mb-3">
                            <!-- use testing input radio field -->
                        <legend class="lw-fieldset-legend">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="lwUsePhonePeTest"  name="use_test_phonepe"
                                    class="custom-control-input" value="1" <?= getAppSettings('use_test_phonepe')==true
                                    ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="lwUsePhonePeTest">
                                    <?= __tr('Use Testing') ?>
                                </label>
                            </div>
                        </legend>
                        <!-- /use testing input radio field -->

                        <!-- show after added testing PhonePe information -->
                        <div class="btn-group" id="lwTestPhonePeExists">
                            <button type="button" disabled="true" class="btn btn-success lw-btn">
                                <?= __tr('Testing PhonePe keys are installed.') ?>
                            </button>
                            <button type="button" class="btn btn-light lw-btn" id="lwUpdateTestPhonePe">
                                <?= __tr('Update') ?>
                            </button>
                        </div>
                        <!-- show after added testing PhonePe information -->

                        <!-- PhonePe test secret key exists hidden field -->
                        <input type="hidden" name="phonepe_test_keys_exist" id="lwPhonePeTestKeysExist"
                        value="<?= getAppSettings('phonepe_testing_secret_key') ?>" />
                        <!-- PhonePe test secret key exists hidden field -->

                    <div id="lwTestPhonePeInputField">
                        <!-- Testing Publish Key -->
                        <div class="mb-3">
                            <label for="lwPhonePeTestPublishKey">
                                <?= __tr('Client Id') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPhonePeTestPublishKey" name="phonepe_testing_client_id"
                                placeholder="<?= __tr('Client Id') ?>">
                        </div>
                        <!-- / Testing Publish Key -->
                        <!-- Testing Secret Key Key -->
                        <div class="mb-3">
                            <label for="lwPhonePeTestSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPhonePeTestSecretKey" name="phonepe_testing_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Testing Secret Key Key -->
                        <!-- Testing Client Version -->
                        <div class="mb-3">
                            <label for="lwPhonePeTestClientVersion">
                                <?= __tr('Client Version') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPhonePeTestClientVersion" name="phonepe_testing_client_version"
                                placeholder="<?= __tr('Client Version') ?>">
                        </div>
                        <!-- / Testing Client Version -->
                    </div>
                </fieldset>
                <!-- /use testing PhonePe input fieldset -->

                <!-- use live PhonePe input fieldset -->
                <fieldset class="lw-fieldset mb-3">
                    <!-- use live input radio field -->
                    <legend class="lw-fieldset-legend">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lwUsePhonePeCheckoutLive" name="use_test_phonepe"
                                class="custom-control-input" value="0" <?= getAppSettings('use_test_phonepe')==false
                                ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="lwUsePhonePeCheckoutLive">
                                <?= __tr('Use Live') ?>
                            </label>
                        </div>
                    </legend>
                    <!-- /use live input radio field -->
                    @if($isExtendedLicence)
                    <!-- show after added Live PhonePe information -->
                    <div class="btn-group" id="lwLivePhonePeCheckoutExists">
                        <button type="button" disabled="true" class="btn btn-success lw-btn">
                            <?= __tr('Live PhonePe keys are installed.') ?>
                        </button>
                        <button type="button" class="btn btn-light lw-btn" id="lwUpdateLivePhonePeCheckout">
                            <?= __tr('Update') ?>
                        </button>
                    </div>
                    <!-- show after added Live PhonePe information -->

                    <!-- PhonePe Live secret key exists hidden field -->
                    <input type="hidden" name="phonepe_live_keys_exist" id="lwPhonePeLiveKeysExist"
                    value="<?= getAppSettings('phonepe_live_secret_key') ?>" />
                    <!-- PhonePe Live secret key exists hidden field -->

                    <div id="lwLivePhonePeInputField">
                        <!-- Live Publish Key -->
                        <div class="mb-3">
                            <label for="lwPhonePeLivePublishKey">
                                <?= __tr('Client Id') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPhonePeLivePublishKey" name="phonepe_live_client_id"
                                placeholder="<?= __tr('Client Id') ?>">
                        </div>
                        <!-- / Live Client Key -->
                        <!-- Live Secret Key Key -->
                        <div class="mb-3">
                            <label for="lwPhonePeLiveSecretKey">
                                <?= __tr('Secret Key') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value=""
                                id="lwPhonePeLiveSecretKey" name="phonepe_live_secret_key"
                                placeholder="<?= __tr('Secret Key') ?>">
                        </div>
                        <!-- / Live Secret Key Key -->

                        <!-- Live Client Version Key -->
                        <div class="mb-3">
                            <label for="lwPhonePeLiveClientKey">
                                <?= __tr('Client Version') ?>
                            </label>
                            <input type="text" class="form-control form-control-user" value="1"
                                id="lwPhonePeLiveClientKey" name="phonepe_live_client_version"
                                placeholder="<?= __tr('Client Version') ?>">
                        </div>
                        <!-- / Live Client Version Key -->
                    </div>
                    @else
                    <div class="alert alert-danger">
                        {{  __tr('Extended licence required to use live keys') }}
                    </div>
                    @endif
                </fieldset>
                <!-- /use live PhonePe checkout input fieldset -->
            </span>
            <hr class="my-4">
            <div class="form-group">
                        <!-- Update Button -->
                <a href class="lw-ajax-form-submit-action btn btn-primary btn-user lw-btn-block-mobile">
                    <?= __tr('Save') ?>
                </a>
                <!-- /Update Button -->
                </div>
            </div>
            <!-- / input field body -->
            </form>
        </fieldset>
        <!-- /PhonePe end -->

        <!-- For one time Payment Only -->
        @stack('oneTimeGatewaySettingStackEnd')
        <!-- /For one time Payment Only -->
@push('appScripts')
<script>
        (function($) {
        'use strict';
    /*********** Stripe Enable / Disable Checkout start here ***********/
	var isStripeCheckoutEnable = $('#lwEnableStripe').is(':checked'),
		isUseStripeCheckoutTest = $("#lwUseStripeCheckoutTest").is(':checked'),
		isUseStripeCheckoutLive = $("#lwUseStripeCheckoutLive").is(':checked');

	if (!isStripeCheckoutEnable) {
		$('#lwStripeCheckoutContainer').addClass('lw-disabled-block-content d-none');
	}
	$("#lwEnableStripe").on('change', function(event) {
		isStripeCheckoutEnable = $(this).is(":checked");
		//check is enable false then add class
		if (!isStripeCheckoutEnable) {
			$("#lwStripeCheckoutContainer").addClass('lw-disabled-block-content d-none');
			//else remove class
		} else {
			$("#lwStripeCheckoutContainer").removeClass('lw-disabled-block-content d-none');
		}
	});
  
	//check stripe test mode is true then disable stripe live input field
	if (isUseStripeCheckoutTest) {
		$('#lwUpdateLiveStripeCheckout').attr('disabled', true);
		$('#lwLiveStripeInputField').addClass('lw-disabled-block-content');
		//check stripe test mode is false then disable stripe test input field
	} else if (isUseStripeCheckoutLive) {
		$('#lwUpdateTestStripeCheckout').attr('disabled', true);
		$('#lwTestStripeInputField').addClass('lw-disabled-block-content');
	}

	//check stripe test mode is true on change
	//then disable stripe live input field
	$("#lwUseStripeCheckoutTest").on('change', function(event) {
		var isUseStripeCheckoutTest = $(this).is(':checked');
		if (isUseStripeCheckoutTest) {
			$('#lwUpdateLiveStripeCheckout').attr('disabled', true);
			$('#lwUpdateTestStripeCheckout').attr('disabled', false);
			$('#lwTestStripeInputField').removeClass('lw-disabled-block-content');
			$('#lwLiveStripeInputField').addClass('lw-disabled-block-content');
		}
	});

	//check stripe test mode is false on change
	//then disable stripe test input field
	$("#lwUseStripeCheckoutLive").on('change', function(event) {
		var isUseStripeCheckoutLive = $(this).is(':checked');
		if (isUseStripeCheckoutLive) {
			$('#lwUpdateTestStripeCheckout').attr('disabled', true);
			$('#lwUpdateLiveStripeCheckout').attr('disabled', false);
			$('#lwLiveStripeInputField').removeClass('lw-disabled-block-content');
			$('#lwTestStripeInputField').addClass('lw-disabled-block-content');
		}
	});
	/*********** Stripe Enable / Disable Checkout end here ***********/

	/*********** Stripe Testing Keys setting start here ***********/
	var isTestStripeKeysInstalled = "<?= $configurationData['stripe_testing_publishable_key'] ?>",
		lwTestStripeInputField = $('#lwTestStripeInputField'),
		lwTestStripeCheckoutExists = $('#lwTestStripeCheckoutExists');

	// Check if test stripe keys are installed
	if (isTestStripeKeysInstalled) {
		lwTestStripeInputField.hide();
	} else {
		lwTestStripeCheckoutExists.hide();
	}
	// Update stripe checkout testing keys
	$('#lwUpdateTestStripeCheckout').on('click', function() {
		$("#lwStripeTestKeysExist").val(0);
		lwTestStripeInputField.show();
		lwTestStripeCheckoutExists.hide();
	});
	/*********** Stripe Testing Keys setting end here ***********/

	/*********** Stripe Live Keys setting start here ***********/
	var isLiveStripePublishKeysInstalled = "<?= $configurationData['stripe_live_publishable_key'] ?>",
		lwLiveStripeInputField = $('#lwLiveStripeInputField'),
		lwLiveStripeCheckoutExists = $('#lwLiveStripeCheckoutExists');

	// Check if test Stripe keys are installed
	if (isLiveStripePublishKeysInstalled) {
		lwLiveStripeInputField.hide();
	} else {
		lwLiveStripeCheckoutExists.hide();
	}
	// Update Stripe checkout testing keys
	$('#lwUpdateLiveStripeCheckout').on('click', function() {
		$("#lwStripeLiveKeysExist").val(0);
		lwLiveStripeInputField.show();
		lwLiveStripeCheckoutExists.hide();
	});
	/*********** Stripe Live Keys setting end here ***********/

      /*********** Paystack Enable / Disable Checkout start here ***********/
	var isPaypalCheckoutEnable = $('#lwEnablePaypal').is(':checked'),
		isUsePaypalCheckoutTest = $("#lwUsePaypalCheckoutTest").is(':checked'),
		isUsePaypalCheckoutLive = $("#lwUsePaypalCheckoutLive").is(':checked');

	if (!isPaypalCheckoutEnable) {
		$('#lwPayPalCheckoutContainer').addClass('lw-disabled-block-content d-none');
	}
	$("#lwEnablePaypal").on('change', function(event) {
		isPaypalCheckoutEnable = $(this).is(":checked");
		//check is enable false then add class
		if (!isPaypalCheckoutEnable) {
			$("#lwPayPalCheckoutContainer").addClass('lw-disabled-block-content d-none');
			//else remove class
		} else {
			$("#lwPayPalCheckoutContainer").removeClass('lw-disabled-block-content d-none');
		}
	});
  
	//check paypal test mode is true then disable paypal live input field
	if (isUsePaypalCheckoutTest) {
		$('#lwUpdateLivePaypalCheckout').attr('disabled', true);
		$('#lwLivePaypalInputField').addClass('lw-disabled-block-content');
		//check paypal test mode is false then disable paypal test input field
	} else if (isUsePaypalCheckoutLive) {
		$('#lwUpdateTestPaypalCheckout').attr('disabled', true);
		$('#lwTestPaypalInputField').addClass('lw-disabled-block-content');
	}

	//check paypal test mode is true on change
	//then disable paypal live input field
	$("#lwUsePaypalCheckoutTest").on('change', function(event) {
		var isUsePaypalCheckoutTest = $(this).is(':checked');
		if (isUsePaypalCheckoutTest) {
			$('#lwUpdateLivePaypalCheckout').attr('disabled', true);
			$('#lwUpdateTestPaypalCheckout').attr('disabled', false);
			$('#lwTestPaypalInputField').removeClass('lw-disabled-block-content');
			$('#lwLivePaypalInputField').addClass('lw-disabled-block-content');
		}
	});

	//check paypal test mode is false on change
	//then disable paypal test input field
	$("#lwUsePaypalCheckoutLive").on('change', function(event) {
		var isUsePaypalCheckoutLive = $(this).is(':checked');
		if (isUsePaypalCheckoutLive) {
			$('#lwUpdateTestPaypalCheckout').attr('disabled', true);
			$('#lwUpdateLivePaypalCheckout').attr('disabled', false);
			$('#lwLivePaypalInputField').removeClass('lw-disabled-block-content');
			$('#lwTestPaypalInputField').addClass('lw-disabled-block-content');
		}
	});
	/*********** PayPal Enable / Disable Checkout end here ***********/
    //=========================================================================-============-============-=
    /*********** PayPal Testing Keys setting start here ***********/
	var isTestPaypalKeysInstalled = "<?= getAppSettings('paypal_checkout_testing_publishable_key') ?>",
    lwTestPaypalInputField = $('#lwTestPaypalInputField'),
    lwTestPaypalCheckoutExists = $('#lwTestPaypalCheckoutExists');

	// Check if test PayPal keys are installed
	if (isTestPaypalKeysInstalled) {
		lwTestPaypalInputField.hide();
	} else {
		lwTestPaypalCheckoutExists.hide();
	}
	// Update PayPal checkout testing keys
	$('#lwUpdateTestPaypalCheckout').on('click', function() {
		$("#lwPaypalTestKeysExist").val(0);
		lwTestPaypalInputField.show();
		lwTestPaypalCheckoutExists.hide();
	});
	/*********** PayPal Testing Keys setting end here ***********/

	/*********** PayPal Live Keys setting start here ***********/
	var isLiveStripePublishKeysInstalled = "<?= getAppSettings('paypal_checkout_live_publishable_key') ?>",
    lwLivePaypalInputField = $('#lwLivePaypalInputField'),
    lwLivePaypalCheckoutExists = $('#lwLivePaypalCheckoutExists');

	// Check if test PayPal keys are installed
	if (isLiveStripePublishKeysInstalled) {
		lwLivePaypalInputField.hide();
	} else {
		lwLivePaypalCheckoutExists.hide();
	}
	// Update PayPal checkout testing keys
	$('#lwUpdateLivePaypalCheckout').on('click', function() {
		$("#lwPaypalLiveKeysExist").val(0);
		lwLivePaypalInputField.show();
		lwLivePaypalCheckoutExists.hide();
	});
	/*********** PayPal Live Keys setting end here ***********/
    /**********====================== /PayPal chceckout end here/======================**********/

     /*********** Razorpay Enable / Disable Checkout start here ***********/
     var isRazorpayCheckoutEnable = $('#lwEnableRazorpay').is(':checked'),
		isUseRazorpayCheckoutTest = $("#lwUseRazorpayTest").is(':checked'),
		isUseRazorpayLive = $("#lwUseRazorpayCheckoutLive").is(':checked');

	if (!isRazorpayCheckoutEnable) {
		$('#lwPayRazorpayContainer').addClass('lw-disabled-block-content d-none');
	}
	$("#lwEnableRazorpay").on('change', function(event) {
		isRazorpayCheckoutEnable = $(this).is(":checked");
		//check is enable false then add class
		if (!isRazorpayCheckoutEnable) {
			$("#lwPayRazorpayContainer").addClass('lw-disabled-block-content d-none');
			//else remove class
		} else {
			$("#lwPayRazorpayContainer").removeClass('lw-disabled-block-content d-none');
		}
	});

	//check razorpay test mode is true then disable razorpay live input field
	if (isUseRazorpayCheckoutTest) {
		$('#lwUpdateLiveRazorpayCheckout').attr('disabled', true);
		$('#lwLiveRazorpayInputField').addClass('lw-disabled-block-content');
		//check razorpay test mode is false then disable razorpay test input field
	} else if (isUseRazorpayLive) {
		$('#lwUpdateTestRazorpay').attr('disabled', true);
		$('#lwTestRazorpayInputField').addClass('lw-disabled-block-content');
	}

	//check razorpay test mode is true on change
	//then disable razorpay live input field
	$("#lwUseRazorpayTest").on('change', function(event) {
		var isUseRazorpayCheckoutTest = $(this).is(':checked');
		if (isUseRazorpayCheckoutTest) {
			$('#lwUpdateLiveRazorpayCheckout').attr('disabled', true);
			$('#lwUpdateTestRazorpay').attr('disabled', false);
			$('#lwTestRazorpayInputField').removeClass('lw-disabled-block-content');
			$('#lwLiveRazorpayInputField').addClass('lw-disabled-block-content');
		}
	});

	//check razorpay test mode is false on change
	//then disable razorpay test input field
	$("#lwUseRazorpayCheckoutLive").on('change', function(event) {
		var isUseRazorpayLive = $(this).is(':checked');
		if (isUseRazorpayLive) {
			$('#lwUpdateTestRazorpay').attr('disabled', true);
			$('#lwUpdateLiveRazorpayCheckout').attr('disabled', false);
			$('#lwLiveRazorpayInputField').removeClass('lw-disabled-block-content');
			$('#lwTestRazorpayInputField').addClass('lw-disabled-block-content');
		}
	});
	/*********** razorpay Enable / Disable Checkout end here ***********/
    //===================================================================
    /*********** razorpay Testing Keys setting start here ***********/
	var isTestRazorpayKeysInstalled = "<?= getAppSettings('razorpay_testing_publishable_key') ?>",
    lwTestRazorpayInputField = $('#lwTestRazorpayInputField'),
    lwTestRazorpayExists = $('#lwTestRazorpayExists');

	// Check if test razorpay keys are installed
	if (isTestRazorpayKeysInstalled) {
		lwTestRazorpayInputField.hide();
	} else {
		lwTestRazorpayExists.hide();
	}
	// Update razorpay checkout testing keys
	$('#lwUpdateTestRazorpay').on('click', function() {
		$("#lwRazorpayTestKeysExist").val(0);
		lwTestRazorpayInputField.show();
		lwTestRazorpayExists.hide();
	});
	/*********** razorpay Testing Keys setting end here ***********/

	/*********** razorpay Live Keys setting start here ***********/
	var isLiveRazorpayPublishKeysInstalled = "<?= getAppSettings('razorpay_live_publishable_key') ?>",
    lwLiveRazorpayInputField = $('#lwLiveRazorpayInputField'),
    lwLiveRazorpayCheckoutExists = $('#lwLiveRazorpayCheckoutExists');

	// Check if test razorpay keys are installed
	if (isLiveRazorpayPublishKeysInstalled) {
		lwLiveRazorpayInputField.hide();
	} else {
		lwLiveRazorpayCheckoutExists.hide();
	}
	// Update razorpay checkout testing keys
	$('#lwUpdateLiveRazorpayCheckout').on('click', function() {
		$("#lwRazorpayLiveKeysExist").val(0);
		lwLiveRazorpayInputField.show();
		lwLiveRazorpayCheckoutExists.hide();
	});
	/*********** Razorpay Live Keys setting end here ***********/
    /**********====================== /Razorpay chceckout ============================**********/


      /*********** Paystack Enable / Disable Checkout start here ***********/
	var isPaystackCheckoutEnable = $('#lwEnablePaystack').is(':checked'),
		isUsePaystackCheckoutTest = $("#lwUsePaystackCheckoutTest").is(':checked'),
		isUsePaystackCheckoutLive = $("#lwUsePaystackCheckoutLive").is(':checked');

	if (!isPaystackCheckoutEnable) {
		$('#lwPaystackCheckoutContainer').addClass('lw-disabled-block-content d-none');
	}
	$("#lwEnablePaystack").on('change', function(event) {
		isPaystackCheckoutEnable = $(this).is(":checked");
		//check is enable false then add class
		if (!isPaystackCheckoutEnable) {
			$("#lwPaystackCheckoutContainer").addClass('lw-disabled-block-content d-none');
			//else remove class
		} else {
			$("#lwPaystackCheckoutContainer").removeClass('lw-disabled-block-content d-none');
		}
	});
  
	//check paypal test mode is true then disable paypal live input field
	if (isUsePaystackCheckoutTest) {
		$('#lwUpdateLivePaystackCheckout').attr('disabled', true);
		$('#lwLivePaystackInputField').addClass('lw-disabled-block-content');
		//check paypal test mode is false then disable paypal test input field
	} else if (isUsePaystackCheckoutLive) {
		$('#lwUpdateTestPaystackCheckout').attr('disabled', true);
		$('#lwTestPaystackInputField').addClass('lw-disabled-block-content');
	}

	//check paypal test mode is true on change
	//then disable paypal live input field
	$("#lwUsePaystackCheckoutTest").on('change', function(event) {
		var isUsePaystackCheckoutTest = $(this).is(':checked');
		if (isUsePaystackCheckoutTest) {
			$('#lwUpdateLivePaystackCheckout').attr('disabled', true);
			$('#lwUpdateTestPaystackCheckout').attr('disabled', false);
			$('#lwTestPaystackInputField').removeClass('lw-disabled-block-content');
			$('#lwLivePaystackInputField').addClass('lw-disabled-block-content');
		}
	});

	//check paypal test mode is false on change
	//then disable paypal test input field
	$("#lwUsePaystackCheckoutLive").on('change', function(event) {
		var isUsePaystackCheckoutLive = $(this).is(':checked');
		if (isUsePaystackCheckoutLive) {
			$('#lwUpdateTestPaystackCheckout').attr('disabled', true);
			$('#lwUpdateLivePaystackCheckout').attr('disabled', false);
			$('#lwLivePaystackInputField').removeClass('lw-disabled-block-content');
			$('#lwTestPaystackInputField').addClass('lw-disabled-block-content');
		}
	});
	/*********** PayPal Enable / Disable Checkout end here ***********/
    //=========================================================================-============-============-=
    /*********** PayPal Testing Keys setting start here ***********/
	var isTestPaystackKeysInstalled = "<?= getAppSettings('paystack_checkout_testing_publishable_key') ?>",
    lwTestPaystackInputField = $('#lwTestPaystackInputField'),
    lwTestPaystackCheckoutExists = $('#lwTestPaystackCheckoutExists');

	// Check if test PayPal keys are installed
	if (isTestPaystackKeysInstalled) {
		lwTestPaystackInputField.hide();
	} else {
		lwTestPaystackCheckoutExists.hide();
	}
	// Update PayPal checkout testing keys
	$('#lwUpdateTestPaystackCheckout').on('click', function() {
		$("#lwPaystackTestKeysExist").val(0);
		lwTestPaystackInputField.show();
		lwTestPaystackCheckoutExists.hide();
	});
	/*********** PayPal Testing Keys setting end here ***********/

	/*********** PayPal Live Keys setting start here ***********/
	var isLivePaystackPublishKeysInstalled = "<?= getAppSettings('paystack_checkout_live_publishable_key') ?>",
    lwLivePaystackInputField = $('#lwLivePaystackInputField'),
    lwLivePaystackCheckoutExists = $('#lwLivePaystackCheckoutExists');

	// Check if test PayPal keys are installed
	if (isLivePaystackPublishKeysInstalled) {
		lwLivePaystackInputField.hide();
	} else {
		lwLivePaystackCheckoutExists.hide();
	}
	// Update PayPal checkout testing keys
	$('#lwUpdateLivePaystackCheckout').on('click', function() {
		$("#lwPaystackLiveKeysExist").val(0);
		lwLivePaystackInputField.show();
		lwLivePaystackCheckoutExists.hide();
	});
    
	/*********** pystack Live Keys setting end here ***********/
    /**********====================== /Paystack chceckout /======================**********/
      /***********yoomoney Enable / Disable Checkout start here ***********/
      var isYooMoneyCheckoutEnable = $('#lwEnableYooMoney').is(':checked'),
		isUseYooMoneyCheckoutTest = $("#lwUseYooMoneyTest").is(':checked'),
		isUseYooMoneyLive = $("#lwUseYooMoneyCheckoutLive").is(':checked');

	if (!isYooMoneyCheckoutEnable) {
		$('#lwPayYooMoneyContainer').addClass('lw-disabled-block-content d-none');
	}
	$("#lwEnableYooMoney").on('change', function(event) {
		isYooMoneyCheckoutEnable = $(this).is(":checked");
		//check is enable false then add class
		if (!isYooMoneyCheckoutEnable) {
			$("#lwPayYooMoneyContainer").addClass('lw-disabled-block-content d-none');
			//else remove class
		} else {
			$("#lwPayYooMoneyContainer").removeClass('lw-disabled-block-content d-none');
		}
	});

	//check YooMoney test mode is true then disable YooMoney live input field
	if (isUseYooMoneyCheckoutTest) {
		$('#lwUpdateLiveYooMoneyCheckout').attr('disabled', true);
		$('#lwLiveYooMoneyInputField').addClass('lw-disabled-block-content');
		//check YooMoney test mode is false then disable YooMoney test input field
	} else if (isUseYooMoneyLive) {
		$('#lwUpdateTestYooMoney').attr('disabled', true);
		$('#lwTestYooMoneyInputField').addClass('lw-disabled-block-content');
	}

	//check YooMoney test mode is true on change
	//then disable YooMoney live input field
	$("#lwUseYooMoneyTest").on('change', function(event) {
		var isUseYooMoneyCheckoutTest = $(this).is(':checked');
		if (isUseYooMoneyCheckoutTest) {
			$('#lwUpdateLiveYooMoneyCheckout').attr('disabled', true);
			$('#lwUpdateTestYooMoney').attr('disabled', false);
			$('#lwTestYooMoneyInputField').removeClass('lw-disabled-block-content');
			$('#lwLiveYooMoneyInputField').addClass('lw-disabled-block-content');
		}
	});

	//check YooMoney test mode is false on change
	//then disable YooMoney test input field
	$("#lwUseYooMoneyCheckoutLive").on('change', function(event) {
		var isUseYooMoneyLive = $(this).is(':checked');
		if (isUseYooMoneyLive) {
			$('#lwUpdateTestYooMoney').attr('disabled', true);
			$('#lwUpdateLiveYooMoneyCheckout').attr('disabled', false);
			$('#lwLiveYooMoneyInputField').removeClass('lw-disabled-block-content');
			$('#lwTestYooMoneyInputField').addClass('lw-disabled-block-content');
		}
	});
	/*********** YooMoney Enable / Disable Checkout end here ***********/
    //===================================================================
    /*********** YooMoney Testing Keys setting start here ***********/
	var isTestYooMoneyKeysInstalled = "<?= getAppSettings('yoomoney_testing_shop_id') ?>",
    lwTestYooMoneyInputField = $('#lwTestYooMoneyInputField'),
    lwTestYooMoneyExists = $('#lwTestYooMoneyExists');

	// Check if test YooMoney keys are installed
	if (isTestYooMoneyKeysInstalled) {
		lwTestYooMoneyInputField.hide();
	} else {
		lwTestYooMoneyExists.hide();
	}
	// Update YooMoney checkout testing keys
	$('#lwUpdateTestYooMoney').on('click', function() {
		$("#lwYooMoneyTestKeysExist").val(0);
		lwTestYooMoneyInputField.show();
		lwTestYooMoneyExists.hide();
	});
	/*********** YooMoney Testing Keys setting end here ***********/

	/*********** YooMoney Live Keys setting start here ***********/
	var isLiveYooMoneyPublishKeysInstalled = "<?= getAppSettings('yoomoney_live_shop_id') ?>",
    lwLiveYooMoneyInputField = $('#lwLiveYooMoneyInputField'),
    lwLiveYooMoneyCheckoutExists = $('#lwLiveYooMoneyCheckoutExists');

	// Check if test YooMoney keys are installed
	if (isLiveYooMoneyPublishKeysInstalled) {
		lwLiveYooMoneyInputField.hide();
	} else {
		lwLiveYooMoneyCheckoutExists.hide();
	}
	// Update YooMoney checkout testing keys
	$('#lwUpdateLiveYooMoneyCheckout').on('click', function() {
		$("#lwYooMoneyLiveKeysExist").val(0);
		lwLiveYooMoneyInputField.show();
		lwLiveYooMoneyCheckoutExists.hide();
	});
	/*********** YooMoney Live Keys setting end here ***********/
    /**********====================== /YooMoney chceckout ============================**********/

    /*********** PhonePe Enable / Disable Checkout start here ***********/
      var isPhonePeCheckoutEnable = $('#lwEnablePhonePe').is(':checked'),
		isUsePhonePeCheckoutTest = $("#lwUsePhonePeTest").is(':checked'),
		isUsePhonePeLive = $("#lwUsePhonePeCheckoutLive").is(':checked');

	if (!isPhonePeCheckoutEnable) {
		$('#lwPhonePeContainer').addClass('lw-disabled-block-content d-none');
	}
	$("#lwEnablePhonePe").on('change', function(event) {
		isPhonePeCheckoutEnable = $(this).is(":checked");
		//check is enable false then add class
		if (!isPhonePeCheckoutEnable) {
			$("#lwPhonePeContainer").addClass('lw-disabled-block-content d-none');
			//else remove class
		} else {
			$("#lwPhonePeContainer").removeClass('lw-disabled-block-content d-none');
		}
	});

	//check PhonePe test mode is true then disable PhonePe live input field
	if (isUsePhonePeCheckoutTest) {
		$('#lwUpdateLivePhonePeCheckout').attr('disabled', true);
		$('#lwLivePhonePeInputField').addClass('lw-disabled-block-content');
		//check PhonePe test mode is false then disable PhonePe test input field
	} else if (isUsePhonePeLive) {
		$('#lwUpdateTestPhonePe').attr('disabled', true);
		$('#lwTestPhonePeInputField').addClass('lw-disabled-block-content');
	}

	//check PhonePe test mode is true on change
	//then disable PhonePe live input field
	$("#lwUsePhonePeTest").on('change', function(event) {
		var isUsePhonePeCheckoutTest = $(this).is(':checked');
		if (isUsePhonePeCheckoutTest) {
			$('#lwUpdateLivePhonePeCheckout').attr('disabled', true);
			$('#lwUpdateTestPhonePe').attr('disabled', false);
			$('#lwTestPhonePeInputField').removeClass('lw-disabled-block-content');
			$('#lwLivePhonePeInputField').addClass('lw-disabled-block-content');
		}
	});

	//check PhonePe test mode is false on change
	//then disable PhonePe test input field
	$("#lwUsePhonePeCheckoutLive").on('change', function(event) {
		var isUsePhonePeLive = $(this).is(':checked');
		if (isUsePhonePeLive) {
			$('#lwUpdateTestPhonePe').attr('disabled', true);
			$('#lwUpdateLivePhonePeCheckout').attr('disabled', false);
			$('#lwLivePhonePeInputField').removeClass('lw-disabled-block-content');
			$('#lwTestPhonePeInputField').addClass('lw-disabled-block-content');
		}
	});
	/*********** PhonePe Enable / Disable Checkout end here ***********/
    //===================================================================
    /*********** PhonePe Testing Keys setting start here ***********/
	var isTestPhonePeKeysInstalled = "<?= getAppSettings('phonepe_testing_client_id') ?>",
    lwTestPhonePeInputField = $('#lwTestPhonePeInputField'),
    lwTestPhonePeExists = $('#lwTestPhonePeExists');

	// Check if test PhonePe keys are installed
	if (isTestPhonePeKeysInstalled) {
		lwTestPhonePeInputField.hide();
	} else {
		lwTestPhonePeExists.hide();
	}
	// Update PhonePe checkout testing keys
	$('#lwUpdateTestPhonePe').on('click', function() {
		$("#lwPhonePeTestKeysExist").val(0);
		lwTestPhonePeInputField.show();
		lwTestPhonePeExists.hide();
	});
	/*********** PhonePe Testing Keys setting end here ***********/

	/*********** PhonePe Live Keys setting start here ***********/
	var isLivePhonePePublishKeysInstalled = "<?= getAppSettings('phonepe_live_client_id') ?>",
    lwLivePhonePeInputField = $('#lwLivePhonePeInputField'),
    lwLivePhonePeCheckoutExists = $('#lwLivePhonePeCheckoutExists');

	// Check if test PhonePe keys are installed
	if (isLivePhonePePublishKeysInstalled) {
		lwLivePhonePeInputField.hide();
	} else {
		lwLivePhonePeCheckoutExists.hide();
	}
	// Update PhonePe checkout testing keys
	$('#lwUpdateLivePhonePeCheckout').on('click', function() {
		$("#lwPhonePeLiveKeysExist").val(0);
		lwLivePhonePeInputField.show();
		lwLivePhonePeCheckoutExists.hide();
	});
	/*********** PhonePe Live Keys setting end here ***********/

    //on payment setting success callback function
	window.onPaymentGatewayFormCallback = function(responseData) {
		//check reaction code is 1 then reload view
		if (responseData.reaction == 1) {
			showConfirmation('Settings Updated Successfully', function() {
                __Utils.viewReload();
            });
		}
	};
})(jQuery);
</script>
@endpush