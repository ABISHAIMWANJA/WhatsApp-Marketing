@extends('layouts.app', ['title' => __tr('Dashboard')])
@php
$vendorIdOrUid = $vendorIdOrUid ?? getVendorUid();
if(!isset($vendorViewBySuperAdmin)) {
$vendorViewBySuperAdmin = null;
}
@endphp
<?php $isExtendedLicence = (getAppSettings('product_registration', 'licence') === 'dee257a8c3a2656b7d7fbe9a91dd8c7c41d90dc9'); ?>
@section('content')
@if(hasCentralAccess())
@include('users.partials.header', [
'title' => __tr('__vendorTitle__ Dashboard', [
'__vendorTitle__' => $vendorInfo['title'] ?? getVendorSettings('title')
]),
'description' => '',
// 'class' => 'col-lg-7'
])
@else
@include('users.partials.header', [
'title' => __tr('Hi __userFullName__,', [
'__userFullName__' => getUserAuthInfo('profile.first_name')
]),

'description' => '',
// 'class' => 'col-lg-7'
])
@endif
<div class="container-fluid">
    @if(hasCentralAccess())
    @php
    $currentActivePlanDetails = getVendorCurrentActiveSubscription($vendorInfo['id']);
    $planDetails = vendorPlanDetails(null, null, $vendorInfo['id']);
    @endphp
    <div class="col-xl-12 p-0">
        <!-- breadcrumbs -->
        <nav aria-label="breadcrumb" class="lw-breadcrumb-container">
            <ol class="breadcrumb bg-transparent text-light p-0 m-0">
                <li class=" breadcrumb-item mb-3">
                    <a class="text-decoration-none" href="{{ route('central.vendors') }}">{{ __tr('Manage Vendors')
                        }}</a>

                </li>
                <li class="text-light breadcrumb-item" aria-current="page">{{ __tr('Dashboard') }}</li>
            </ol>
        </nav>
        <!-- /breadcrumbs -->
    </div>
    @endif
    @include('layouts.headers.cards')
    @if(hasVendorAccess() or $vendorViewBySuperAdmin )
<div class="container-fluid">
    @if (getVendorSettings('whatsapp_access_token_expired', null, null, $vendorIdOrUid))
    <div class="alert alert-danger">
        {{ __tr('Your WhatsApp token seems to be expired, Generate new token, prefer creating permanent token and save.') }}
        <br>
        <a class="btn btn-sm btn-white my-2"
            href="{{ route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) }}">{{ __tr('Cloud API setup') }}</a>
    </div>
    @elseif (!isWhatsAppBusinessAccountReady($vendorIdOrUid))
    <div class="alert alert-danger">
        {{ __tr('You are not ready to send messages, WhatsApp Setup is Incomplete') }}
        <br>
        <a class="btn btn-sm btn-white my-2"
            href="{{ route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) }}">{{ __tr('Complete your WhatsApp Cloud API setup') }}</a>
    </div>
    @endif
    @if (getAppSettings('pusher_by_vendor') and !getVendorSettings('pusher_app_id', null, null, $vendorIdOrUid))
    <div class="alert alert-warning">
        {{ __tr('Pusher keys needs to setup for realtime communication like Chat etc., You can get it from __pusherLink__, choose channel and create the app to get the required keys.', [
        '__pusherLink__' => '<a target="blank" href="https://pusher.com">pusher.com</a>'
        ]) }}
        <br>
        <a class="btn btn-sm btn-white my-2"
            href="{{ route('vendor.settings.read', ['pageType' => 'general']) }}#pusherKeysConfiguration">{{ __tr('Pusher Configuration') }}</a>
    </div>
    @endif
    @if(!$vendorViewBySuperAdmin)
    <div class="row">
        <div class="col-12 mb-5">
            <fieldset>
                <legend>{{ __tr('Quick Start') }}</legend>
                <h3>
                    <ol>
                        <li>{{ __tr('Login to your Facebook Account') }}</li>
                        <li>{!! __tr('Complete Setup as Shown in __cloudApiSetupLink__', [
                            '__cloudApiSetupLink__' => '<a
                                href="'. route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) .'">'.
                                __tr('WhatsApp Cloud API Setup').'</a>'
                            ]) !!}</li>
                        <li>{!! __tr('Manage and Sync WhatsApp templates at __manageContactsLink__',[
                            '__manageContactsLink__' => '<a href="'. route('vendor.whatsapp_service.templates.read.list_view') .'">'. __tr('Manage WhatsApp Templates').'</a>'
                            ]) !!}</li>
                        <li>{!! __tr('Create your contact groups using __manageGroupsLink__', [
                            '__manageGroupsLink__' => '<a href="'. route('vendor.contact.group.read.list_view') .'">'.
                                __tr('Manage Groups').'</a>'
                            ]) !!}</li>
                        <li>{!! __tr('Create your Contacts or Upload excel file with predefined exportable template at __manageContactsLink__',[
                            '__manageContactsLink__' => '<a href="'. route('vendor.contact.read.list_view') .'">'.
                                __tr('Manage Contacts').'</a>'
                            ]) !!}</li>
                        <li>{!! __tr('Create & Schedule your Campaigns at __manageCampaignsLink__',[
                            '__manageCampaignsLink__' => '<a href="'. route('vendor.campaign.read.list_view') .'">'.
                                __tr('Manage Campaigns').'</a>'
                            ]) !!}</li>
                    </ol>
                </h3>
            </fieldset>
        </div>
    </div>
    @endif
</div>
@endif

@if(hasCentralAccess())
    <div class="col-xl-12 pl-1">
        <div class="">
            <div class="card-body">
                <fieldset class="mb-5">
                    <legend>{{ __tr('Vendor Details') }}</legend>
                     <div class="col-xl-12 ">
                        <a data-method="post" class="btn btn-light btn-sm lw-ajax-link-action float-right" href="{{ route('central.vendors.user.write.login_as',['vendorUid'=>$vendorIdOrUid])}}"   data-confirm="#lwLoginAs-template" title="{{ __tr('Login as Vendor Admin') }}"><i class="fa fa-sign-in-alt"></i> {{  __tr('Login') }}</a>
                    </div>
                    <div class="my-2 ">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Vendor Title:') }}</h4>
                        <p class="card-text">{{$vendorInfo['title']}} </p>
                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Account Status:') }}</h4>
                        @if($vendorInfo['status']==0)
                        <p class="card-text">{{__tr('Inactive') }}</p>
                        @else
                        <p class="card-text">{{configItem('status_codes',$vendorInfo['status'])}}</p>
                        @endif

                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Created On:') }}</h4>
                        <p class="card-text">{{formatDate($vendorUserData['created_at'])}}</p>
                    </div>
                    <hr>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Admin User Name:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['first_name'] . ' ' . $vendorUserData['last_name'])}}</p>
                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Username:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['username'])}}</p>
                    </div>
                    @if($vendorUserData['mobile_number'])
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Phone Number:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['mobile_number'])}}</p>
                    </div>
                    @endif
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Email:') }}</h4>
                        <p class="card-text">{{maskForDemo($vendorUserData['email'])}}</p>
                    </div>
                    <div class="my-2">
                        <h4 class="text-dark font-weight-bold">{{ __tr('Admin User Status:') }}</h4>
                        @if($vendorUserData['status']==0)
                        <p class="card-text">{{__tr('Inactive') }}</p>
                        @else
                        <p class="card-text">{{configItem('status_codes', $vendorUserData['status'])}}</p>
                        @endif
                    </div>
                </fieldset>

                <fieldset class="mb-4">
                    @php
                        $planStructure = $planDetails->plan_id ? getPaidPlans($planDetails->plan_id) : getFreePlan();
                        $planCharges = $planStructure['charges'][$planDetails->frequency] ?? null;
                    @endphp
                    <legend>{{ __tr('Current Subscribed Plan') }}</legend>
                    <div class="row">
                        <div class="col-md-7">
                            @if ($planDetails->hasActivePlan())
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Plan Title:') }}</h4>
                                <p class="card-text">{{$planDetails->planTitle()}} </p>
                            </div>
                            @if($planCharges)
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Current Plan Charges:') }}</h4>
                                <p class="card-text"> {{ $planCharges['title'] ?? '' }} {{ formatAmount($planCharges['charge'],
                                    true) }}</p>
                            </div>
                            @endif
                            @if($currentActivePlanDetails)
                            @if($planDetails['subscription_type']=='manual')
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Status:') }}</h4>
                                <p class="card-text">{{configItem('subscription_status',$currentActivePlanDetails['status'])}}</p>
                            </div>
                            @elseif($planDetails['subscription_type']=='auto')
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Status:') }}</h4>
                                <p class="card-text">{{configItem('subscription_status',$currentActivePlanDetails['stripe_status'])}}</p>
                            </div>
                            @else
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Status:') }}</h4>
                                <p class="card-text">{{__tr('Active') }}</p>
                            </div>
                            @endif
                            @endif
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Subscription Type:') }}</h4>
                                @if (data_get($currentActivePlanDetails, ('is_auto_recurring')))
                                     <p class="card-text">{{configItem('subscription_methods', 'auto')}}
                                @else
                                    <p class="card-text">{{configItem('subscription_methods',$planDetails['subscription_type'])}}
                                @endif
                                </p>
                            </div>
                            @if($currentActivePlanDetails)
                            {{--  check payment method is manual for payment method --}}
                            @if($planDetails['subscription_type']=='manual')
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Payment Method:') }}</h4>
                                <p class="card-text">{{ $currentActivePlanDetails['__data']['manual_txn_details']['selected_payment_method'] ?? 'NA' }}</p>
                            </div>
                            @endif
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Created On:') }}</h4>
                                <p class="card-text">{{formatDate($currentActivePlanDetails['created_at'])}}</p>
                            </div>
                            @endif
                            <div class="my-2">
                                <h4 class="text-dark font-weight-bold">{{ __tr('Expire On:') }}</h4>
                                <p class="card-text">{{ $planDetails['ends_at'] ? formatDate($planDetails['ends_at']):  'NA'}}</p>
                            </div>
                            @else
                            <div class="alert alert-warning">{{ __tr('Vendor does not have any active plan.') }}</div>
                            @endif
                        </div>
                        <div class="col-md-4 mt--5">
                            <fieldset class="mb-4">
                                <legend>{{ __tr('Plan Details') }}</legend>
                                @if ($planDetails->hasActivePlan())
                                    <h2 class="text-primary">{{ $planDetails->planTitle() }}</h2>
                                    @php
                                        $planStructure = $planDetails->plan_id ? getPaidPlans($planDetails->plan_id) : getFreePlan();
                                        $planCharges = $planStructure['charges'][$planDetails->frequency] ?? null;
                                    @endphp
                                    <?php if (!__isEmpty(data_get($planStructure, 'features'))) { ?>
                                    @foreach ($planStructure['features'] as $featureKey => $featureValue)
                                        @php
                                            $structureFeatureValue = $featureValue;
                                            $featureValue = $featureValue;
                                        @endphp
                                        <div class="my-2">
                                            @if (isset($featureValue['type']) and ($featureValue['type'] == 'switch'))
                                                @if (isset($featureValue['limit']) and $featureValue['limit'])
                                                    <i class="fa fa-check mr-2 text-success"></i>
                                                @else
                                                    <i class="fa fa-times mr-2 text-danger"></i>
                                                @endif
                                                {{ ($structureFeatureValue['description']) }}
                                            @else
                                                <i class="fa fa-check text-success mr-2"></i>
                                                @if (isset($featureValue['limit']) and $featureValue['limit'] < 0)
                                                    {{ __tr('Unlimited') }}
                                                @elseif(isset($featureValue['limit']))
                                                    {{ $featureValue['limit'] }}
                                                @endif
                                                    {{ ($structureFeatureValue['description']) }}
                                                @if(isset($featureValue['limit_duration_title']))
                                                    {{ ($featureValue['limit_duration_title']) }}
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                    <?php } ?>
                                @else
                                    <hr><div class="alert alert-warning">{{  __tr('Vendor does not have any active plan.') }}</div>
                                @endif
                                @if($planDetails->isAuto())
                                    <hr>
                                    <h4 class="text-warning">
                                        {{  __tr('Please note vendor is on the Auto Renewal Subscription Plan, First you need to cancel it to manage manual subscription.') }}
                                    </h4>
                                    <a data-show-processing="true" class="lw-ajax-link-action btn btn-danger" data-method="post" href="{{ route('central.subscription.write.cancel', [
                                        'vendorUid' => $vendorIdOrUid
                                    ]) }}">
                                        {{ __tr('Cancel Auto Subscription and Discard Grace Period if any') }}
                                    </a>
                                @else
                                    @if (!$isExtendedLicence)
                                        <hr><div class="alert alert-danger">
                                            {{  __tr('Extended licence required to enable manage subscription') }}
                                        </div>
                                    @endif
                                @endif
                                {{-- show warning message to admin --}}
                                @stack('autoSubscriptionWarningMessagesStack')
                                {{-- /show warning message to admin --}}
                            </fieldset>
                        </div>
                    </div>
                    @if ($isExtendedLicence and !data_get($currentActivePlanDetails, 'is_auto_recurring'))
                        <hr><button type="button" class="lw-btn btn btn-primary" data-toggle="modal" data-target="#lwAddNewManualSubscription"> {{ __tr('Create New Subscription') }}</button>
                    @endif
                </fieldset>
            </div>

            <div class="col-xl-12">
                @if (data_get($currentActivePlanDetails, ('is_auto_recurring')))
                    <h1>{{  __tr('Razorpay Subscription Log') }}</h1>
                @else
                    <h1>{{  __tr('Manual/Prepaid Subscription Log') }}</h1>
                @endif
                <x-lw.datatable data-page-length="100" id="lwManualSubscriptionList" data-page-length="10"
                    :url="route('central.subscription.manual_subscription.read.list', [
                        'vendorUid' => $vendorIdOrUid,
                        'isAutoRecurring' => data_get($currentActivePlanDetails, ('is_auto_recurring'))
                    ])">
                    <th data-orderable="true" data-name="plan_id">{{ __tr('Plan') }}</th>
                    <th data-order-by="true" data-order-type="desc" data-orderable="true" data-name="created_at">{{ __tr('Created At') }}</th>
                    <th data-orderable="true" data-name="ends_at">{{ __tr('Expiry On') }}</th>
                    <th data-orderable="true" data-name="charges">{{ __tr('Plan Charges') }}</th>
                    <th data-orderable="true" data-name="charges_frequency">{{ __tr('Frequency') }}</th>
                    <th data-template="#manualSubscriptionStatusColumnTemplate" data-name="null">{{ __tr('Status') }}</th>
                    <th data-template="#manualSubscriptionActionColumnTemplate" name="null">{{ __tr('Action') }}</th>
                </x-lw.datatable>
                <!-- Edit Manual Subscription Modal -->
                <x-lw.modal id="lwEditManualSubscription" :header="__tr('Update Subscription')" :hasForm="true">
                    <!--  Edit Manual Subscription Form -->
                    <x-lw.form id="lwEditManualSubscriptionForm"
                        :action="route('central.subscription.manual_subscription.write.update')"
                        :data-callback-params="['modalId' => '#lwEditManualSubscription', 'datatableId' => '#lwManualSubscriptionList']"
                        data-callback="appFuncs.modelSuccessCallback">
                        <!-- form body -->
                        <div id="lwEditManualSubscriptionBody" class="lw-form-modal-body"></div>
                        <script type="text/template" id="lwEditManualSubscriptionBody-template">
                            @if ($isExtendedLicence)
                                <fieldset>
                                    <legend>{{  __tr('Provided Payment Details') }}</legend>
                                    <dl>
                                        <dt>{{  __tr('Payment Method') }}</dt>
                                        <dd><%- __tData.__data?.manual_txn_details?.selected_payment_method %></dd>
                                        <dt>{{  __tr('Transaction Reference') }}</dt>
                                        <dd><%- __tData.__data?.manual_txn_details?.txn_reference %></dd>
                                        <dt>{{  __tr('Transaction Date') }}</dt>
                                        <dd><%- __tData.transactionDate %></dd>
                                    </dl>
                                </fieldset>
                                <input type="hidden" name="manualSubscriptionIdOrUid" value="<%- __tData._uid %>" />
                                <!-- form fields -->
                                <x-lw.input-field type="number" min="0" id="lwChargesEditField" data-form-group-class="" :label="__tr('Charges')" value="<%- __tData.charges %>" name="charges"  required="true" />
                                <!-- Ends_At -->
                                <x-lw.input-field type="date" id="lwEndsAtEditField" data-form-group-class="" :label="__tr('Expiry On')" value="<%- __tData.ends_at %>" name="ends_at"  required="true"                 />
                                <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSubscriptionStatus" data-form-group-class="" data-selected="<%- __tData.status %>" :label="__tr('Status')" name="status" required="true">
                                    <x-slot name="selectOptions">
                                        <option value="">{{  __tr('Select Status') }}</option>
                                        @foreach (configItem('subscription_status') as $subscriptionStatusKey => $subscriptionStatus)
                                        <option value="{{ $subscriptionStatusKey }}">{{ $subscriptionStatus }}</option>
                                        @endforeach
                                    </x-slot>
                                </x-lw.input-field>
                                <div class="form-group">
                                    <label for="lwEditRemarks">{{  __tr('Remarks if any') }}</label>
                                    <textarea class="form-control" name="remarks" id="lwEditRemarks" rows="2"><%- __tData.remarks %></textarea>
                                </div>
                                <!-- /Ends_At -->
                            @else
                                <div class="alert alert-danger">
                                    {{  __tr('Extended licence required to enable manage subscription') }}
                                </div>
                            @endif
                        </script>
                        <!-- form footer -->
                        <div class="modal-footer">
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
                        </div>
                    </x-lw.form>
                    <!--/  Edit Manual Subscription Form -->
                </x-lw.modal>
                <!--/ Edit Manual Subscription Modal -->
                <script type="text/template" id="manualSubscriptionActionColumnTemplate">
                    <a data-pre-callback="appFuncs.clearContainer" title="{{  __tr('Update') }}" class="lw-btn btn btn-sm btn-default lw-ajax-link-action" data-response-template="#lwEditManualSubscriptionBody" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.read.update.data', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>"  data-toggle="modal" data-target="#lwEditManualSubscription"><i class="fa fa-edit"></i> {{  __tr('Update') }}</a>
                    <a data-method="post" href="<%= __Utils.apiURL("{{ route('central.subscription.manual_subscription.write.delete', [ 'manualSubscriptionIdOrUid']) }}", {'manualSubscriptionIdOrUid': __tData._uid}) %>" class="btn btn-danger btn-sm lw-ajax-link-action-via-confirm" data-confirm="#lwDeleteManualSubscription-template" title="{{ __tr('Delete') }}" data-callback-params="{{ json_encode(['datatableId' => '#lwManualSubscriptionList']) }}" data-callback="appFuncs.modelSuccessCallback"><i class="fa fa-trash"></i> {{  __tr('Delete') }}</a>
                </script>
                <!-- Manual Subscription delete template -->
                <script type="text/template" id="lwDeleteManualSubscription-template">
                    <h2>{{ __tr('Are You Sure!') }}</h2>
                    <p>{{ __tr('You want to delete this Subscription?') }}</p>
                </script>
                <script type="text/template" id="manualSubscriptionStatusColumnTemplate">
                    <% if(__tData.status == 'Pending') { %>
                        <span class="badge badge-warning">{{  __tr('Pending') }}</span>
                    <% } else if(__tData.status == 'Active') { %>
                        <span class="badge badge-success">{{  __tr('Active') }}</span>
                    <% }  else { %>
                        <%- __tData.status %>
                    <% } %>
                    <% if(__tData.options.is_expired) { %>
                        <span class="badge badge-danger">{{  __tr('Expired') }}</span>
                    <% } %>
                </script>
            </div>
            
        </div>
    </div>
@endif
</div>
@if(isThisDemoVendorAccountAccess())
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <div class="alert alert-dark">
                <h2 class="text-white">{{ __tr('Demo Account') }}</h2>
                <p>{{ __tr('Contacts created here with your numbers will be deleted frequently. You need to add your number to allow for test') }}</p>
                <p>{{ __tr('If you want to test system with your own account. Facebook also provides Test Number which
                    is very easy to setup and test. You can follow the steps given in Quick Start on dashboard to get
                    started.') }}</p>
                     <a title="{{  __tr('You can update your numbers for test on this demo account') }}" class="lw-btn btn btn-xl btn-danger" href="#"  data-toggle="modal" data-target="#lwRegisterDemoNumber"><i class="fab fa-whatsapp"></i> {{  __tr('Add your WhatsApp Numbers for Demo Test') }}</a>
            </div>
        </div>
    </div>
</div>
@include('vendors.demo-instructions')
@endif
@if(hasVendorAccess() or $vendorViewBySuperAdmin)
<!-- New Subscription Modal -->
<x-lw.modal id="lwAddNewManualSubscription" :header="__tr('Create New Subscription')" :hasForm="true">
    <!--  New Subscription Form -->
    <x-lw.form x-data="{calculated_ends_at:null}" id="lwAddNewManualSubscriptionForm"
        :action="route('central.subscription.manual_subscription.write.create')"
        :data-callback-params="['modalId' => '#lwAddNewManualSubscription', 'datatableId' => '#lwManualSubscriptionList']"
        data-callback="appFuncs.modelSuccessCallback">
        <!-- form body -->
        <div class="lw-form-modal-body">
            @if ($isExtendedLicence)
            <div class="alert alert-danger">
                {{  __tr('It will cancelled all the existing active subscriptions and create new subscription') }}
            </div>
            <!-- form fields form fields -->
            <input type="hidden" name="vendor_uid" value="{{ $vendorInfo['uid'] }}">
            <!-- Plan_Id -->
            <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwPlanIdField"
                data-form-group-class="" data-selected=" " :label="__tr('Plan')" name="plan"
                required="true">
                <x-slot name="selectOptions">
                    <option value="">{{  __tr('Select Plan') }}</option>
                    @foreach (getPaidPlans() as $paidPlanKey => $paidPlan)
                    <optgroup label="{{ $paidPlan['title'] }} @if(!$paidPlan['enabled']) ({{ __tr('Disabled') }}) @endif">
                        @foreach ($paidPlan['charges'] as $planChargeKey => $planCharge)
                            <option value="{{ $paidPlanKey }}___{{ $planChargeKey }}">{{ $paidPlan['title'] }} - {{ formatAmount($planCharge['charge'], true) }} {{ $planCharge['title'] }}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </x-slot>
            </x-lw.input-field>
            <!-- /Plan_Id -->
            <!-- Ends_At -->
            <x-lw.input-field x-model="calculated_ends_at" type="date" id="lwEndsAtField" data-form-group-class="" :label="__tr('Expiry on')"
                name="ends_at" required="true" />
            <!-- /Ends_At -->
            <div class="form-group">
                <label for="lwRemarks">{{  __tr('Remarks if any') }}</label>
                <textarea class="form-control" name="remarks" id="lwRemarks" rows="2"></textarea>
            </div>
        </div>
        <!-- form footer -->
        <div class="modal-footer">
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __tr('Close') }}</button>
        </div>
        @else
        <div class="alert alert-danger">
            {{  __tr('Extended licence required to enable manage subscription') }}
        </div>
        @endif
    </x-lw.form>
    <!--/  New Subscription Form -->
</x-lw.modal>
<!--/ New Subscription Modal -->
@endif
<script type="text/template" id="lwLoginAs-template">
    <h2>{{ __tr('Are You Sure!') }}</h2>
    <p>{{ __tr('You want login to this vendor admin account?') }}</p>
</script>

@push('appScripts')
<script>
    (function(window) {
    'use strict';
    $('#lwPlanIdField').on('lwSelectizeOnChange', function(event, value) {
        __DataRequest.post("{{ route('central.subscription.manual_subscription.read.selected_plan_details') }}", {
            'selected_plan' : value
        });
    });
    })(window);
</script>
@endpush
@push('head')
<?= __yesset(['dist/css/dashboard.css'],true) ?>
@endpush
@push('js')
<?= __yesset(['dist/js/dashboard.js'],true)?>
@endpush
@endsection()