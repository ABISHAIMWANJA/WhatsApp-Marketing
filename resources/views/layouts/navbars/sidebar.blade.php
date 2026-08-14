<!-- theme Menu -->
@php
//user current theme
$currentAppTheme=getUserAppTheme();
@endphp

<nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light bg-white lw-sidebar-container"
    id="sidenav-main">
    <div class="container-fluid">
        <span>
            <!-- Toggler -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main"
                aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span><span class="fa fa-bars d-none"></span>
            </button>
            <!-- Brand -->
            <a class="navbar-brand pt-0 d-none d-sm-inline" href="{{ url('/') }}">
                <!-- App Theme Change -->
                @if( $currentAppTheme=='dark')
                <!-- dark theme -->
                <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-normal dark-theme-logo text-dark" alt="{{ getAppSettings('name') }}">
                <img src="{{ getAppSettings('dark_theme_small_logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-small dark-theme-small-logo text-dark"
                    alt="{{ getAppSettings('name') }}">
                <!-- /dark theme -->
                @elseif($currentAppTheme=='system_default')
                <!-- dark theme -->
                <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-normal dark-theme-logo system-theme-dark-logo text-dark"
                    alt="{{ getAppSettings('name') }}" media="(prefers-color-scheme: dark)">
                <img src="{{ getAppSettings('dark_theme_small_logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-small dark-theme-small-logo system-theme-dark-small-logo text-dark"
                    alt="{{ getAppSettings('name') }}">
                <!-- /dark theme -->
                <!-- light theme -->
                <img src="{{ getAppSettings('logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-normal light-theme-logo system-theme-light-logo"
                    alt="{{ getAppSettings('name') }}">
                <img src="{{ getAppSettings('small_logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-small light-theme-small-logo system-theme-light-small-logo text-dark"
                    alt="{{ getAppSettings('name') }}">

                @else
                <!-- light theme -->
                <img src="{{ getAppSettings('logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-normal light-theme-logo" alt="{{ getAppSettings('name') }}">
                <img src="{{ getAppSettings('small_logo_image_url') }}"
                    class="navbar-brand-img lw-sidebar-logo-small light-theme-small-logo text-dark"
                    alt="{{ getAppSettings('name') }}">
                <!-- /light theme -->

                @endif
                <!-- /App Theme Change -->


            </a>
        </span>
        <!-- User -->
        <ul class="nav align-items-center d-md-none">
            <!--theme change -->
            @if(getAppSettings('allow_to_change_theme'))
            <li class="nav-item">
                @include('layouts.navbars.app-theme')
            </li>
            @endif
            <!--theme change -->
            <li class="nav-item">
                @include('layouts.navbars.locale-menu')
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <div class="media align-items-center">
                        <span class="avatar avatar-sm rounded-circle">
                            <i class="fa fa-user"></i>
                        </span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-right">
                    <div class=" dropdown-header noti-title">
                        <h6 class="text-overflow m-0">{{ __tr('Welcome!') }}</h6>
                    </div>
                    <a href="{{ route('user.profile.edit') }}" class="dropdown-item">
                        <i class="fa fa-user"></i>
                        <span>{{ __tr('My profile') }}</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    @if(session('loggedByVendor') or session('loggedBySuperAdmin'))
                        @if(session('loggedByVendor'))
                        <a data-method="post" href="{{ route('vendor.user.write.logout_as') }}" class="lw-ajax-link-action dropdown-item">
                            <span>{{ __tr('Back to your account') }}</span>
                            <i class="fa fa-sign-in-alt mr-1"></i>
                        </a>
                        @elseif(session('loggedBySuperAdmin'))
                        <a data-method="post" href="{{ route('central.vendors.user.write.logout_as') }}" class="lw-ajax-link-action dropdown-item">
                            <span>{{ __tr('Back to Super Admin Area') }}</span>
                            <i class="fa fa-sign-in-alt mr-1"></i>
                        </a>
                        @endif
                    <div class="dropdown-divider"></div>
                    @endif
                    <a data-method="post" href="{{ route('auth.logout') }}" class="dropdown-item lw-ajax-link-action">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>{{ __tr('Logout') }}</span>
                    </a>
                </div>
            </li>
        </ul>
        <!-- Collapse -->
        <div class="collapse navbar-collapse" id="sidenav-collapse-main">
            <!-- Collapse header -->
            <div class="navbar-collapse-header d-md-none">
                <div class="row">
                    <div class="col-6 collapse-brand">
                        <a href="{{ url('/') }}">

                            <!-- App Theme Change -->
                            @if( $currentAppTheme=='dark')

                            <!-- dark theme logo  -->
                            <img src="{{ getAppSettings('dark_theme_logo_image_url') }}" class="dark-theme-logo "
                                alt="{{ getAppSettings('name') }}">

                            <!-- /dark theme -->
                            @elseif($currentAppTheme=='system_default')

                            <!-- dark theme logo  -->
                            <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                                class="dark-theme-logo system-theme-dark-logo" alt="{{ getAppSettings('name') }}">
                            <!-- light theme logo  -->
                            <img src="{{ getAppSettings('logo_image_url') }}"
                                class="light-theme-logo system-theme-light-logo" alt="{{ getAppSettings('name') }}">


                            @else

                            <!-- light theme logo  -->
                            <img src="{{ getAppSettings('logo_image_url') }}" class="light-theme-logo "
                                alt="{{ getAppSettings('name') }}">
                            <!-- /light theme -->

                            @endif
                            <!-- /App Theme Change -->


                        </a>
                    </div>
                    <div class="col-6 collapse-close">
                        <button type="button" class="navbar-toggler" data-toggle="collapse"
                            data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false"
                            aria-label="Toggle sidenav">
                            <span></span>
                            <span></span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Navigation -->
            <ul class="navbar-nav">
                @if (hasCentralAccess())
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('central.console') }}" href="{{ route('central.console') }}">
                        <i class="fa fa-tachometer-alt"></i> {{ __tr('Dashboard') }}
                    </a>
                </li>

                <li class="nav-item {{ markAsActiveLink('central.vendors') }}">
                    <a class="nav-link" href="{{ route('central.vendors') }}">
                        <i class="fa fa-store"></i> {{ __tr('Vendors') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#lwSubscriptionSubMenu" data-toggle="collapse" role="button"
                        aria-expanded="true" aria-controls="lwSubscriptionSubMenu">
                        <i class="fa fa-user-tag text-dark"></i>
                        <span class="nav-link-text">{{ __tr('Subscriptions') }}</span>
                    </a>
                    <div class="collapse show lw-expandable-nav" id="lwSubscriptionSubMenu">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item {{ markAsActiveLink('central.subscriptions') }}">
                                <a class="nav-link" href="{{ route('central.subscriptions') }}">
                                    <i class="fa fa-sync-alt"></i> {{ __tr('Recurring Stripe') }}
                                </a>
                            </li>
                            @stack('autoSubscriptionSidebarLinkStack')
                            <li
                                class="nav-item {{ markAsActiveLink('central.subscription.manual_subscription.read.list_view') }}">
                                <a class="nav-link"
                                    href="{{ route('central.subscription.manual_subscription.read.list_view') }}">
                                    <i class="fa fa-user-tag"></i> {{ __tr('Manual/Prepaid') }}
                                    @if(getPendingSubscriptionCount())<span class="badge badge-danger ml-2">{{
                                        getPendingSubscriptionCount() }}</span> @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('media.files.read_view') }}"
                        href="{{ route('media.files.read_view') }}">
                        <i class="fas fa-photo-video"></i> {!! __tr('Local Files & Media') !!}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('manage.translations.languages') }}"
                        href="{{ route('manage.translations.languages') }}">
                        <i class="fa fa-language"></i> {{ __tr('Translations') }}
                    </a>
                </li>
                <li class="nav-item ">
                    <a class="nav-link {{ markAsActiveLink('page.list') }}" href="{{ route('page.list') }}">
                        <i class="fas fa-file"></i> {{ __tr('Pages') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#configurationMenu" data-toggle="collapse" role="button"
                        aria-expanded="true" aria-controls="configurationMenu">
                        <i class="fa fa-cogs text-dark"></i>
                        <span class="nav-link-text">{{ __tr('Configurations') }}</span>
                    </a>

                    <div class="collapse show lw-expandable-nav" id="configurationMenu">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request('pageType') == 'general' ? 'active' : '' }}"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'general']) }}">
                                    <i class="fa fa-cog"></i>
                                    {{ __tr('General') }}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'user' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'user']) }}">
                                    <i class="fa fa-user"></i>
                                    {!! __tr('User & Vendor') !!}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'currency' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'currency']) }}">
                                    <i class="fa fa-money-check-alt"></i>
                                    {{ __tr('Currency') }}
                                </a>
                            </li>
                            <li class="nav-item {{ markAsActiveLink('manage.configuration.payment') }}">
                                <a class="nav-link <?= (isset($pageType) and $pageType == 'payment') ? 'active' : '' ?>"
                                    href="<?= route('manage.configuration.read', ['pageType' => 'payment']) ?>">
                                    <i class="fa fa-money-check-alt"></i>
                                    {{ __tr('Payment Gateways') }}
                                </a>
                            </li>
                            <li class="nav-item {{ markAsActiveLink('manage.configuration.subscription-plans') }}">
                                <a class="nav-link" href="{{ route('manage.configuration.subscription-plans') }}">
                                    <i class="fa fa-user"></i>
                                    {{ __tr('Subscription Plans') }}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'email' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'email']) }}">
                                    <i class="fa fa-at"></i>
                                    {{ __tr('Email') }}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'social-login' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'social-login']) }}">
                                    <i class="fas fa-user-plus"></i>
                                    {{ __tr('Social Login') }}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'other' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'other']) }}">
                                    <i class="fa fa-cog"></i>
                                    {!! __tr('Setup & Integrations') !!}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'misc' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'misc']) }}">
                                    <i class=" fa fa-cogs"></i>
                                    {!! __tr('Misc') !!}
                                </a>
                            </li>
                            <li class="nav-item {{ request('pageType') == 'whatsapp-onboarding' ? 'active' : '' }}">
                                <a class="nav-link"
                                    href="{{ route('manage.configuration.read', ['pageType' => 'whatsapp-onboarding']) }}">
                                    <i class="fab fa-facebook text-blue"></i>
                                    {!! __tr('WhatsApp Onboarding') !!}
                                </a>
                            </li>
                            <li class="nav-item ">
                                <a class="nav-link {{ markAsActiveLink('central.addons.read.list') }}"
                                    href="{{ route('central.addons.read.list') }}">
                                    <i class="fas fa-puzzle-piece text-danger"></i> {{ __tr('Addons') }}
                                </a>
                            </li>
                            @stack('centralSidebarSettingsLinks')
                            <li
                                class="nav-item <?= Request::fullUrl() == route('manage.configuration.read', ['pageType' => 'licence-information']) ? 'active' : '' ?>">
                                <a class="nav-link"
                                    href="<?= route('manage.configuration.read', ['pageType' => 'licence-information']) ?>">
                                    <i class="fas fa-certificate"></i>
                                    <span>
                                        <?= __tr('License') ?>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                @if (hasVendorAccess() or hasVendorUserAccess())
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('vendor.console') }}" href="{{ route('vendor.console') }}">
                        <i class="fa fa-tachometer-alt"></i>
                        {{ __tr('Dashboard') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#lwScanMeDialog">
                        <i class="fa fa-qrcode text-dark"></i>
                        {{ __tr('QR Code') }}
                    </a>
                </li>
                @if (hasVendorAccess('messaging'))
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('vendor.chat_message.contact.view') }}"
                        href="{{ route('vendor.chat_message.contact.view') }}">
                        <span x-cloak x-show="unreadMessagesCount" class="badge badge-success rounded-pill ml--2"
                            x-text="unreadMessagesCount"></span><i class="fa fa-comments mr-2"></i> <span
                            class="ml--2">{{ __tr('WhatsApp Chat') }}</span>
                    </a>
                </li>
                @endif
                @if (hasVendorAccess('manage_campaigns'))
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('vendor.campaign.read.list_view') }}"
                        href="{{ route('vendor.campaign.read.list_view') }}">
                        <i class="fa fa-bullhorn"></i>
                        {{ __tr('Campaigns') }}
                    </a>
                </li>
                @endif
                @if (hasVendorAccess('manage_contacts'))
                <li class="nav-item">
                    <a class="nav-link" href="#vendorContactSubmenuNav" data-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="vendorContactSubmenuNav">
                        <i class="fa fa-users text-dark"></i>
                        <span class="">{{ __tr('Contacts') }}</span>
                    </a>
                    <div class="collapse lw-expandable-nav" id="vendorContactSubmenuNav">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ markAsActiveLink('vendor.contact.read.list_view') }}"
                                    href="{{ route('vendor.contact.read.list_view') }}">
                                    <i class="fa fa-list"></i>
                                    {{ __tr('List') }}
                                </a>
                            </li>
                            @if(hasVendorAccess('manage_contacts', 'add_edit_delete_archive_group'))
                                <li class="nav-item">
                                    <a class="nav-link {{ markAsActiveLink('vendor.contact.group.read.list_view') }}"
                                        href="{{ route('vendor.contact.group.read.list_view') }}">
                                        <i class="fa fa-list-alt"></i>
                                        {{ __tr('Groups') }}
                                    </a>
                                </li>
                            @endif
                            @if(hasVendorAccess('manage_contacts', 'add_edit_delete_custom_contact_fields'))
                                <li class="nav-item">
                                    <a class="nav-link {{ markAsActiveLink('vendor.contact.custom_field.read.list_view') }}"
                                        href="{{ route('vendor.contact.custom_field.read.list_view') }}">
                                        <i class="fa fa-stream"></i>
                                        {{ __tr('Custom Fields') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif
                @if (hasVendorAccess('manage_templates'))
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('vendor.whatsapp_service.templates.read.list_view') }}"
                        href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">
                        <i class="fa fa-layer-group"></i>
                        {{ __tr('Templates') }}
                    </a>
                </li>
                @endif
                @if (hasVendorAccess('manage_bot_replies'))
                <li class="nav-item">
                    <a class="nav-link" href="#vendorAutomationSubmenuNav" data-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="vendorAutomationSubmenuNav">
                        <i class="fa fa-robot text-dark"></i>
                        <span class="">{{ __tr('Automation') }}</span>
                    </a>
                    <div class="collapse lw-expandable-nav" id="vendorAutomationSubmenuNav">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ markAsActiveLink('vendor.bot_reply.read.list_view') }}"
                                    href="{{ route('vendor.bot_reply.read.list_view') }}">
                                    <i class="fa fa-robot"></i>
                                    {{ __tr('Bots') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ markAsActiveLink('vendor.bot_reply.bot_flow.read.list_view') }}"
                                    href="{{ route('vendor.bot_reply.bot_flow.read.list_view') }}">
                                    <i class="fas fa-project-diagram"></i>
                                    {{ __tr('Bot Flows') }}
                                </a>
                            </li>
                            @stack('vendorSidebarAutomationLinks')
                        </ul>
                    </div>
                </li>
                @endif
                @if (hasVendorAccess('administrative'))
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('vendor.user.read.list_view') }}"
                        href="{{ route('vendor.user.read.list_view') }}">
                        <i class="fa fa-users"></i>
                        {{ __tr('Team Members') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ markAsActiveLink('subscription.read.show') }}"
                        href="{{ route('subscription.read.show') }}">
                        <i class="fa fa-id-card"></i>
                        {{ __tr('My Subscription') }}
                    </a>
                </li>
                <li class="nav-item">

                <a class="nav-link {{ markAsActiveLink('vendor.whatsapp.message.log.view') }}"
                                            href="{{ route('vendor.whatsapp.message.log.view') }}">
                                            <i class="fas fa-history"></i>
                                            {{ __tr('Message Log') }}
                                        </a>
                                    </li>
                <li class="nav-item">
                    <a class="nav-link @if(isWhatsAppBusinessAccountReady()) collapsed @else text-warning @endif"
                        href="#vendorSettingsNav" data-toggle="collapse" role="button"
                        aria-expanded="@php echo !isWhatsAppBusinessAccountReady() ? 'true' : 'false'; @endphp"
                        aria-controls="vendorSettingsNav">
                        <i class="fa fa-cogs"></i>
                        <span class="">{{ __tr('Settings') }}</span>
                    </a>
                    <div class="collapse @if(!isWhatsAppBusinessAccountReady()) show @endif lw-expandable-nav"
                        id="vendorSettingsNav">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($pageType) and $pageType == 'general') ? 'active' : '' ?>"
                                    href="<?= route('vendor.settings.read', ['pageType' => 'general']) ?>">
                                    <i class="fa fa-cog"></i>
                                    {{ __tr('General') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <strong><a
                                        class="nav-link <?= (isset($pageType) and $pageType == 'whatsapp-cloud-api-setup') ? 'active' : '' ?> @if(!isWhatsAppBusinessAccountReady()) text-warning @endif"
                                        href="<?= route('vendor.settings.read', ['pageType' => 'whatsapp-cloud-api-setup']) ?>">
                                        <i class="fab fa-whatsapp"></i>
                                        {{ __tr('WhatsApp Setup') }} @if(!isWhatsAppBusinessAccountReady())<i
                                            class="fas fa-exclamation-triangle ml-1"></i>@endif
                                    </a></strong>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($pageType) and $pageType == 'ai-chat-bot-setup') ? 'active' : '' ?>"
                                    href="<?= route('vendor.settings.read', ['pageType' => 'ai-chat-bot-setup']) ?>">
                                    <i class="fa fa-brain"></i>
                                    {!! __tr('AI Bot & Bot Settings') !!}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($pageType) and $pageType == 'api-access') ? 'active' : '' ?>"
                                    href="<?= route('vendor.settings.read', ['pageType' => 'api-access']) ?>">
                                    <i class="fa fa-terminal"></i>
                                    {!! __tr('API & Webhook') !!}
                                </a>
                            </li>
                            @stack('vendorSidebarSettingsLinks')
                        </ul>
                    </div>
                </li>
                @endif
                @endif
            </ul>
        </div>
    </div>
</nav>