<!DOCTYPE html>
@php

    $currentAppTheme = '';
    // Default theme from settings
    $currentAppTheme = getUserAppTheme();
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $CURRENT_LOCALE_DIRECTION ?? '' }}"
    data-theme="{{ $currentAppTheme }}">
@php
    $appName = getAppSettings('name');
    $currentAppTheme = '';
    // Default theme from settings
    $currentAppTheme = getUserAppTheme();
@endphp

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{ (isset($title) and $title) ? ' - ' . $title : __tr('Welcome') }} - {{ $appName }}</title>
    <!-- Primary Meta Tags -->
    <meta name="title" content="{{ $appName }}" />
    <meta name="description" content="{{ getAppSettings('description') }}" />
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="{{ $appName }}" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:title" content="{{ $appName }}" />
    <meta property="og:description" content="{{ getAppSettings('description') }}" />
    <meta property="og:image" content="{{ getAppSettings('logo_image_url') }}" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="{{ url('/') }}" />
    <meta property="twitter:title" content="{{ $appName }}" />
    <meta property="twitter:description" content="{{ getAppSettings('description') }}" />
    <meta property="twitter:image" content="{{ getAppSettings('logo_image_url') }}" />


    <!-- Light Theme Favicon -->
    <link href="{{ getAppSettings('favicon_image_url') }}" rel="icon" media="(prefers-color-scheme: light)">

    <!-- Dark Theme Favicon -->
    <link href="{{ getAppSettings('dark_theme_favicon_image_url') }}" rel="icon"
        media="(prefers-color-scheme: dark)">

    {!! __yesset([
        'static-assets/packages/fontawesome/css/all.css',
        'static-assets/packages/bootstrap-icons/font/bootstrap-icons.css',
    ]) !!}
    <!-- Google fonts-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <!-- /Google fonts-->

    <!-- /App Theme Change -->
    <link rel="stylesheet" href="{{ __yesset('dist/css/app-theme.css') }}">
</head>

<body id="page-top" class="lw-outer-home-page">
    <!-- Navigation-->
    <nav class="navbar navbar-expand-lg fixed-top bg-dark-blue" id="mainNav">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand pt-0" href="{{ url('/') }}">
                <!-- App Theme Change -->
                @if ($currentAppTheme == 'dark')
                    {{-- dark theme logo --}}
                    <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                        class="navbar-brand-img  dark-theme-logo" alt="{{ getAppSettings('name') }}">
                    <!-- /dark theme -->
                @elseif($currentAppTheme == 'system_default')
                    <img src="{{ getAppSettings('logo_image_url') }}"
                        class="navbar-brand-img light-theme-logo system-theme-light-logo"
                        alt="{{ getAppSettings('name') }}">
                    {{-- dark theme logo --}}
                    <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                        class="navbar-brand-img  dark-theme-logo system-theme-dark-logo"
                        alt="{{ getAppSettings('name') }}" media="(prefers-color-scheme: dark)">
                @else
                    {{-- light theme logo --}}
                    <img src="{{ getAppSettings('logo_image_url') }}" class="navbar-brand-img light-theme-logo"
                        alt="{{ getAppSettings('name') }}">
                    <!-- /App Theme Change -->
                @endif
                <!-- App Theme Change -->
            </a>
            <!-- Logo -->
            <button class="navbar-toggler lw-btn-block-mobile" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                aria-label="{{ __tr('Toggle navigation') }}">
                {{ __tr('Menu') }}
                <i class="bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-4 my-3 my-lg-0 text-center">

                    <!-- Features -->
                    <li class="nav-item"><a class="nav-link" href="#features">{{ __tr('Features') }}</a>
                    </li>
                    <!-- /Features -->

                    <!-- Pricing -->
                    <li class="nav-item"><a class="nav-link" href="#pricing">{{ __tr('Pricing') }}</a></li>
                    <!-- /Pricing -->

                    <!-- Contact -->
                    <li class="nav-item"><a class="nav-link"
                            href="{{ route('user.contact.form') }}">{{ __tr('Contact') }}</a></li>
                    <!-- /Contact -->

                    <!-- pages -->
                    <li class="nav-item">
                        @include('layouts.navbars.navs.pages-menu-partial')
                    </li>
                    <!-- /pages -->
                    <!--theme change -->
                    @if (getAppSettings('allow_to_change_theme'))
                        <li class="nav-item">
                            @include('layouts.navbars.app-theme')
                        </li>
                    @endif
                    <!--theme change -->
                    <!-- language -->
                    @include('layouts.navbars.locale-menu')
                    <!-- /language -->

                    @if (!isLoggedIn())
                        @if (getAppSettings('enable_vendor_registration') or getAppSettings('message_for_disabled_registration'))
                            <!-- Register -->
                            <li class="nav-item"><a class="nav-link text-danger fw-bold"
                                    href="{{ route('auth.register') }}">{{ __tr('Register') }}</a></li>
                        @endif
                        <!-- /Register -->

                        <!-- Login -->
                        <li class="nav-item"><a class="nav-link "
                                href="{{ route('auth.login') }}">{{ __tr('Login') }}</a></li>
                    @endif
                    <!-- /Login -->

                    <!-- Dashboard -->
                    @if (isLoggedIn())
                        <li class="nav-item"><a class="nav-link fw-bold text-orange lw-warning-text"
                                href="{{ route('central.console') }}">{{ __tr('Dashboard') }}</a></li>
                    @endif
                    <!-- /Dashboard -->
                </ul>
            </div>
        </div>
    </nav>
    <!-- /Navigation -->

    <!-- masthead section -->
    <header class="bg-dark-blue">
        <div class="container">
            <div class="lw-masthead-section d-flex align-items-center">
                <div class="text-white text-center">
                    <!-- heading -->
                    <div class="lw-masthead-title fw-bolder">
                        {{ __tr('Transform Customer Engagement with WhatsApp – Experience the Power of __appName__', [
                            '__appName__' => $appName,
                        ]) }}
                    </div>
                    <!-- heading -->

                    <!-- description -->
                    <div class="description my-4">
                        {{ __tr(
                            'Unlock the full potential of customer engagement with __appName__  your comprehensive WhatsApp Marketing Platform.',
                            [
                                '__appName__' => $appName,
                            ],
                        ) }}
                    </div>
                    <!-- /description -->

                    <!-- buttons -->
                    <div class="my-5">
                        <a href="{{ route('auth.login') }}" class="btn btn-primary mx-1 lw-special-btn">
                            {{ __tr('Get Started') }}
                        </a>
                        <a href="{{ route('auth.register') }}" class="btn btn-secondary mx-1">
                            {{ __tr('Learn more') }}
                        </a>
                    </div>
                    <!-- buttons -->

                    <!-- image -->
                    <div class="mt-4"><img class="" src="{{ asset('imgs/outer-home/lw-masthead.png') }}"
                            alt="lw-masthead" />
                    </div>
                    <!-- /image -->
                </div>
            </div>
        </div>
    </header>
    <!-- /masthead section -->

    <!-- why choose section -->
    <section class="lw-why-choose-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-sm-12 col-md-12 col-lg-6">

                    <!-- heading -->
                    <h5 class="text-primary">
                        {{ __tr('Why __appName__ Stands Out :', [
                            '__appName__' => $appName,
                        ]) }}
                    </h5>
                    <h1 class="fw-bold">{{ __tr('Top Reasons to Choose Our Services') }}</h1>
                    <!-- /heading -->

                    <!-- Increased Engagement -->
                    <div class="card d-flex flex-row mt-4 mb-3">
                        <i class="fas fa-trophy"></i>
                        <div class="ms-3">
                            <h6>{{ __tr('Increased Engagement') }}</h6>
                            <p>{{ __tr('Engage directly with your customers in real-time on WhatsApp.') }}
                            </p>
                        </div>
                    </div>
                    <!-- /Increased Engagement -->

                    <!-- Higher Conversion -->
                    <div class="card d-flex flex-row mb-3">
                        <i class="fas fa-people-arrows btn-primary"></i>
                        <div class="ms-3">
                            <h6>{{ __tr('Higher Conversion Rates') }}</h6>
                            <p>{{ __tr('Turn conversations into conversions with targeted messaging through __appName__.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                            </p>
                        </div>
                    </div>
                    <!-- /Higher Conversion -->

                    <!-- Customer Support -->
                    <div class="card d-flex flex-row mb-3">
                        <i class="fas fa-hands-helping btn-secondary"></i>
                        <div class="ms-3">
                            <h6>{{ __tr('24/7 Customer Support') }}</h6>
                            <p>{{ __tr("Automated responses ensure you're always there for your customers with __appName__.", [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                            </p>
                        </div>
                    </div>
                    <!-- /Customer Support -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- image -->
                    <img class="img-fluid image-fluid" src="{{ asset('imgs/outer-home/why-choose.png') }}" />
                    <!-- image -->
                </div>
            </div>
        </div>
    </section>
    <!-- /why choose section -->

    <!-- Features section -->
    <section class="lw-features-section" id="features">
        <div class="container">
            <!-- heading -->
            <h1 class="text-center text-primary mb-5 fw-bolder">
                {{ __tr('__appName__ : Simplify Connections, Boost Your Brand!', [
                    '__appName__' => $appName,
                ]) }}
            </h1>
            <!-- /heading -->
            <!-- Campaign Management -->
            <div class="row align-items-center p-3">
                <div class="col-sm-12 col-md-12 col-lg-6">

                    <!-- heading -->
                    <h4 class="fw-bold my-4">{{ __tr('Campaign Management') }}</h4>
                    <!-- /heading -->

                    <!-- description -->
                    <p class="lw-secondary-text">
                        {{ __tr('Effortlessly manage your campaigns with our intuitive campaign management feature. Create or schedule campaigns instantly for all contacts or specific groups, allowing for immediate reach or strategic timing. Maximize the impact of your marketing efforts and take control of your messaging with ease.') }}
                    </p>
                    <!-- /description -->

                    <!-- button -->
                    <a href="{{ route('auth.login') }}" class="btn text-primary">{{ __tr('Learn more') }}
                        <span><i class="fa fa-arrow-right"></i></span></a>
                    <!-- / button -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- image -->
                    <img class="img-fluid my-4 image-fluid" src="{{ asset('imgs/outer-home/campaign-man.png') }}" />
                    <!-- image -->
                </div>
            </div>
            <!-- /Campaign Management -->

            <!-- Application Colors System -->
            <div class="row align-items-center p-3 lw-flex-wrap">
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- image -->
                    <img class="img-fluid my-4 image-block"
                        src="{{ asset('imgs/outer-home/app-color-system.png') }}" />
                    <!-- image -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6">

                    <!-- heading -->
                    <h4 class="fw-bold my-4">{{ __tr('Application Colors  System') }}</h4>
                    <!-- /heading -->

                    <!-- description -->
                    <p class="lw-secondary-text">
                        {{ __tr(
                            'Custom app colors made easy — __appName__ gives you the power of pick and apply the colors to customize your app as per your choice.',
                            [
                                '__appName__' => $appName,
                            ],
                        ) }}
                    </p>
                    <!-- /description -->

                    <!-- button -->
                    <a href="{{ route('auth.login') }}" class="btn text-primary">{{ __tr('Learn more') }}
                        <span><i class="fa fa-arrow-right"></i></span></a>
                    <!-- / button -->
                </div>
            </div>
            <!-- /Application Colors System -->

            <!-- Flowise AI -->
            <div class="row align-items-center p-3">
                <div class="col-sm-12 col-md-12 col-lg-6">

                    <!-- heading -->
                    <h4 class="fw-bold my-4">{{ __tr('AI Bot Integration For Vendor Using Flowise AI Setting') }}
                    </h4>
                    <!-- /heading -->

                    <!-- description -->
                    <p class="lw-secondary-text">
                        {{ __tr('Flowise AI offers AI-powered chatbots for vendors to automate customer interactions and enhance engagement.') }}
                    </p>
                    <!-- /description -->

                    <!-- button -->
                    <a href="{{ route('auth.login') }}" class="btn text-primary">{{ __tr('Learn more') }}
                        <span><i class="fa fa-arrow-right"></i></span></a>
                    <!-- / button -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- image -->
                    <img class="img-fluid my-4" src="{{ asset('imgs/outer-home/ai-bot-integration.png') }}" />
                    <!-- image -->
                </div>
            </div>
            <!-- /Flowise AI -->

            <!-- Subscription system used Stripe -->
            <div class="row align-items-center p-3 lw-flex-wrap">
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- image -->
                    <img class="img-fluid my-4 image-block" src="{{ asset('imgs/outer-home/payment.png') }}" />
                    <!-- image -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6">

                    <!-- heading -->
                    <h4 class="fw-bold my-4">{{ __tr('Subscription System Used Stripe') }}</h4>
                    <!-- /heading -->

                    <!-- description -->
                    <p class="lw-secondary-text">
                        {{ __tr('Automate recurring Subscription Payments effortlessly with Stripe. Receive payments from your users seamlessly, ensuring a smooth and efficient subscription process. Simplify your payment management and enhance user experience with our Stripe integration.') }}
                    </p>
                    <!-- /description -->

                    <!-- button -->
                    <a href="{{ route('auth.login') }}" class="btn text-primary">{{ __tr('Learn more') }}
                        <span><i class="fa fa-arrow-right"></i></span></a>
                    <!-- / button -->
                </div>
            </div>
            <!-- /Subscription system used Stripe -->
        </div>
    </section>
    <!-- /Features section -->

    <!-- advance features section -->
    <section class="bg-dark-blue lw-advanced-feature-cards">
        <div class="container">
            <!-- Heading -->
            <div class="text-center text-white mb-5">
                <h1 class="fw-bolder mb-3">{{ __tr('Tech Empowerment') }} </h1>
                <p>{{ __tr('Features that would make your life easier with WhatsApp Marketing') }}</p>
            </div>
            <!-- /Heading -->
            <div class="row">
                <!-- Embedded Signup -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-sign-in-alt"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('Embedded Signup') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('Onboard customers with ease with our integrated Embedded Signup system.') }}
                        </p>
                    </div>
                </div>
                <!-- /Embedded Signup -->

                <!-- Template Management -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-file-invoice"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('Template Management') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('Handle templates directly within the application without requiring a visit to Meta for creating templates.') }}
                        </p>
                    </div>
                </div>
                <!-- /Template Management -->

                <!-- Multiple Phone Numbers -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-phone-alt"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('Multiple Phone Numbers') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('Supports multiple phone numbers for  same WhatsApp Business Account.') }}
                        </p>
                    </div>
                </div>
                <!-- /Multiple Phone Numbers -->

                <!-- WhatsApp Chat -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fab fa-rocketchat"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('WhatsApp Chat') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr(
                                '__appName__ chat feature replicates the native WhatsApp interface, guaranteeing users a seamless and familiar messaging experience.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </p>
                    </div>
                </div>
                <!-- /WhatsApp Chat -->

                <!-- Bot Replies/ Chat Bot -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-robot"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('Bot Replies/ Chat Bot') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('Automate responses and engage customers 24/7 with intelligent bot replies through.') }}
                        </p>
                    </div>
                </div>
                <!-- /Bot Replies/ Chat Bot -->

                <!-- APIs -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-cogs"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('APIs to connect with other services') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('API’s enable seamless connection between different services, allowing data sharing and functionality integration.') }}
                        </p>
                    </div>
                </div>
                <!-- /APIs -->

                <!-- Custom Fields -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-bars"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('Custom Fields') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('Personalize your messages with user base information and custom fields tailored to your audience on __appName__', ['__appName__' => $appName]) }}
                        </p>
                    </div>
                </div>
                <!-- /Custom Fields -->

                <!-- Team Members/Agents -->
                <div class="col-sm-12 col-md-6 col-lg-3 mb-4">
                    <div class="card border-0 h-100 text-center text-white">
                        <i class="fas fa-user"></i>
                        <h5 class="mt-3 mb-2 fw-normal">{{ __tr('Team Members/Agents') }}</h5>
                        <p class="fw-light m-0">
                            {{ __tr('Onboard customers with ease with our integrated Embedded Signup system.') }}
                        </p>
                    </div>
                </div>
                <!-- /Team Members/Agents -->
            </div>
        </div>
    </section>
    <!-- /advance features section -->

    <!-- call to action -->
    <section class="lw-call-to-action-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- heading -->
                    <h2 class="fw-bold mb-3 text-primary">{{ __tr('Built for Customer Engagements') }}
                    </h2>
                    <!-- /heading -->

                    <!-- description -->
                    <p class="lw-secondary-text">
                        {{ __tr('__appName__ is a helpful tool for businesses to communicate better with customers. It makes talking to customers easier and simpler, helping businesses grow and build strong relationships.', ['__appName__' => $appName]) }}
                    </p>
                    <!-- /description -->

                    <!-- button -->
                    <a href="{{ route('auth.register') }}"
                        class="btn btn-primary lw-special-btn">{{ __tr('Sign up now') }} </a>
                    <!-- / button -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6">
                    <!-- image -->
                    <img class="img-fluid my-4 lw-call-to-action-img"
                        src="{{ asset('imgs/outer-home/call-to-action-img.png') }}" />
                    <!-- image -->
                </div>
            </div>
        </div>
    </section>
    <!-- /call to action -->

    <!-- AI bot features -->
    <section class="bg-dark-blue lw-ai-oi-cards">
        <div class="container">
            <!-- heading -->
            <h2 class="fw-bold mb-5 text-white text-center">
                {{ __tr('AI Chat Bot Quickly build Automated Chatbots') }}
            </h2>
            <!-- /heading -->

            <div class="lw-ai-card">
                <!-- Bot Flow Builder -->
                <div class="lw-card-io-list">
                    <span>{{ __tr('Bot Flow Builder') }}</span>
                    <div class="lw-card-io-list-content w-100 fade-in">
                        <p class="text-center text-white px-5">
                            {{ __tr('Our Advanced Bot Flow Builder streamlines bot conversation creation, enabling easy setup of triggers between bots using button and list row links.') }}
                        </p>
                        <div class="d-flex justify-content-center">
                            <!-- image -->
                            <img class="img-fluid" src="{{ asset('imgs/outer-home/aibot.png') }}" />
                            <!-- /image -->
                        </div>
                    </div>
                </div>
                <!-- /Bot Flow Builder -->

                <!-- Bot Timing restrictions setting -->
                <div class="lw-card-io-list">
                    <span>{{ __tr('Bot Timing Restrictions Setting') }}</span>
                    <div class="lw-card-io-list-content w-100  fade-in">
                        <p class="text-center text-white px-5">
                            {{ __tr('Set bot operation hours with start/end times, timezone, and enable timing for specific bots, applying restrictions only to selected bots.') }}
                        </p>
                        <div class="d-flex justify-content-center">
                            <!-- image -->
                            <img class="img-fluid lw-ai-bot-img"
                                src="{{ asset('imgs/outer-home/ai-timing.png') }}" />
                            <!-- /image -->
                        </div>
                    </div>
                </div>
                <!-- /Bot Timing restrictions setting -->

                <!-- AI Start / Stop Bots -->
                <div class="lw-card-io-list">
                    <span>{{ __tr('AI General Setting') }}</span>
                    <div class="lw-card-io-list-content w-100  fade-in">
                        <p class="text-center text-white px-5">
                            {{ __tr('A message will be sent if the AI Bot fails to respond due to an error. You can also enable the AI Bot automatically for all new contacts created from incoming messages or imports.') }}
                        </p>
                        <div class="d-flex justify-content-center">
                            <!-- image -->
                            <img class="img-fluid lw-ai-bot-img"
                                src="{{ asset('imgs/outer-home/ai-general-setting.png') }}" />
                            <!-- /image -->
                        </div>
                    </div>
                </div>
                <!-- /AI Start / Stop Bots -->
            </div>
        </div>
    </section>
    <!-- /AI bot features -->

    <!-- pricing blocks -->
    <section class="lw-pricing-block-cards" id="pricing">
        <div class="container text-center">
            <!-- heading -->
            <button class="lw-link-primary text-primary border-0 mb-4">{{ __tr('Our Solution') }}</button>
            <h1 class="fw-bold mb-5 text-center text-white">{{ __tr('Pricing or Features') }}
            </h1>
            <!-- /heading -->
            <!-- free plan  -->
            <div class="row justify-content-center">
                @php
                    $freePlanDetails = getFreePlan();
                    $freePlanStructure = getConfigFreePlan();
                    $paidPlans = getPaidPlans();
                    $planStructure = getConfigPaidPlans();
                @endphp

                @if ($freePlanDetails['enabled'])
                    <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 mb-4">
                        <div class="card border-0 text-center text-dark h-100">
                            <div class="card-header bg-transparent border-0">
                                <!-- title -->
                                <h4 class="mt-3">{{ $freePlanDetails['title'] }}</h4>
                                <!-- title -->
                            </div>
                            <hr class="my-3">
                            <div class="card-body">
                                <!--  pricing -->
                                <h3 class="price mb-4">{{ formatAmount(0, true, true) }}
                                    <span>{{ __tr('/ monthly') }}</span>
                                </h3>
                                <br>
                                <h3 class="price mb-4">
                                    {{ formatAmount(0, true, true) }}<span>{{ __tr('/ yearly') }}</span>
                                </h3>
                                <!--  /pricing -->

                                <small><a class="text-muted " target="_blank"
                                        href="https://business.whatsapp.com/products/platform-pricing">{{ __tr('+ WhatsApp Cloud Messaging Charges') }}
                                        <i class="fas fa-external-link-alt "></i></a></small>
                                <hr class="my-4">
                                <!-- features  -->
                                <ul class="p-0 m-0">
                                    @foreach ($freePlanStructure['features'] as $featureKey => $featureValue)
                                        @php
                                            $configFeatureValue = $featureValue;
                                            $featureValue = $freePlanDetails['features'][$featureKey];
                                        @endphp
                                        <li>
                                            @if (isset($featureValue['type']) and $featureValue['type'] == 'switch')
                                                @if (isset($featureValue['limit']) and $featureValue['limit'])
                                                    <i class="fa fa-check mr-3 text-success"></i>
                                                @else
                                                    <i class="fa fa-times mr-3 text-danger"></i>
                                                @endif
                                                {{ $configFeatureValue['description'] }}
                                            @else
                                                <strong>
                                                    @if (isset($featureValue['limit']) and $featureValue['limit'] < 0)
                                                        {{ __tr('Unlimited') }}
                                                    @elseif(isset($featureValue['limit']))
                                                        {{ __tr($featureValue['limit']) }}
                                                    @endif
                                                </strong>
                                                {{ $configFeatureValue['description'] }}
                                                {{ $configFeatureValue['limit_duration_title'] ?? '' }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <!-- /features -->
                                <div class="pricing-price"></div>
                            </div>
                        </div>
                    </div>
                    <!-- /free plan-->
                @endif
                <!-- paid plan -->
                @foreach ($planStructure as $planKey => $plan)
                    @php
                        $planId = $plan['id'];
                        $features = $plan['features'];
                        $savedPlan = $paidPlans[$planKey];
                        $charges = $savedPlan['charges'];
                        if (!$savedPlan['enabled']) {
                            continue;
                        }
                    @endphp
                    <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 mb-4">
                        <div class="card border-0 text-center text-dark h-100">
                            <div class="card-header border-0 bg-transparent">
                                <!-- title -->
                                <h4 class="mt-3">{{ $savedPlan['title'] ?? $plan['title'] }}
                                </h4>
                                <!-- /title -->
                            </div>
                            <hr class="my-3">
                            <div class="card-body">

                                <!--  pricing -->
                                @foreach ($charges as $itemKey => $itemValue)
                                    @php
                                        if (!$itemValue['enabled']) {
                                            continue;
                                        }
                                    @endphp
                                    <h2 class="price mb-3">
                                        {{ formatAmount($itemValue['charge'], true, true) }}<span>
                                            /{{ Arr::get($plan['charges'][$itemKey], 'title', '') }}</span></h2>
                                    <br>
                                @endforeach
                                <!--  /pricing -->

                                <small><a class="text-muted" target="_blank"
                                        href="https://business.whatsapp.com/products/platform-pricing">{{ __tr('+ WhatsApp Cloud Messaging Charges') }}
                                        <i class="fas fa-external-link-alt"></i></a></small>

                                <hr class="my-4">
                                <!-- features  -->
                                <ul class="p-0 m-0">
                                    @foreach ($plan['features'] as $featureKey => $featureValue)
                                        @php
                                            $configFeatureValue = $featureValue;
                                            $featureValue = $savedPlan['features'][$featureKey];
                                        @endphp
                                        <li>
                                            @if (isset($featureValue['type']) and $featureValue['type'] == 'switch')
                                                @if (isset($featureValue['limit']) and $featureValue['limit'])
                                                    <i class="fa fa-check mr-3 text-success"></i>
                                                @else
                                                    <i class="fa fa-times mr-3 text-danger"></i>
                                                @endif
                                                {{ $configFeatureValue['description'] }}
                                            @else
                                                <strong>
                                                    @if (isset($featureValue['limit']) and $featureValue['limit'] < 0)
                                                        {{ __tr('Unlimited') }}
                                                    @elseif(isset($featureValue['limit']))
                                                        {{ __tr($featureValue['limit']) }}
                                                    @endif
                                                </strong>
                                                {{ $configFeatureValue['description'] }}
                                                {{ $configFeatureValue['limit_duration_title'] ?? '' }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <!-- /features  -->
                                <div class="pricing-price"></div>
                            </div>

                        </div>
                    </div>
                @endforeach
                <!-- /paid plan  -->
            </div>
        </div>
    </section>
    <!-- /pricing blocks -->

    <!-- testimonials -->
    <section class="text-dark lw-testimonial-block">
        <div class="container">

            <!-- heading -->
            <h2 class="fw-bold mb-5 text-dark text-center">
                {{ __tr('Success Stories from the __appName__ Community', [
                    '__appName__' => $appName,
                ]) }}
            </h2>
            <!-- /heading -->

            <div class="row">
                <!-- first testimonial -->
                <div class="col-sm-12 col-md-12 col-lg-4 mb-4">
                    <div class="card border-0 p-5 shadow h-100">
                        <i class="fa fa-quote-left fs-3 text-primary mb-3"></i>
                        <p class="lw-secondary-text">
                            {{ __tr(
                                'Using __appName__ has transformed our customer engagement strategy. The import/export feature is a game-changer for managing our contacts efficiently.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </p>
                        <h5>{{ __tr('John Doe') }}</h5>
                        <p class="lw-secondary-text">{{ __tr('Marketing Manager') }}</p>
                        <div class="d-flex text-warning lw-spacing">
                            <i class="fa fa-star mx-1"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star mx-1"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star mx-1"></i>
                        </div>
                    </div>
                </div>
                <!-- /first testimonial -->

                <div class="col-sm-12 col-md-12 col-lg-4 mb-4">
                    <div class="card border-0 p-5 shadow h-100">
                        <i class="fa fa-quote-left fs-3 text-primary mb-3"></i>
                        <p class="lw-secondary-text">
                            {{ __tr(
                                'The automation capabilities of __appName__, especially the bot replies, have significantly reduced our response times and improved customer satisfaction.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </p>
                        <h5>{{ __tr('Jane Smith') }}</h5>
                        <p class="lw-secondary-text">{{ __tr('Customer Service Lead') }}</p>
                        <div class="d-flex text-warning lw-spacing">
                            <i class="fa fa-star mx-1"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star mx-1"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star mx-1"></i>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12 col-md-12 col-lg-4 mb-4">
                    <div class="card border-0 p-5 shadow h-100">
                        <i class="fa fa-quote-left fs-3 text-primary mb-3"></i>
                        <p class="lw-secondary-text">
                            {{ __tr(
                                '__appName__\'s intuitive design and easy Facebook WhatsApp Business integration made it simple for us to start our marketing campaigns quickly.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </p>
                        <h5>{{ __tr('Alex Johnson') }}</h5>
                        <p class="lw-secondary-text">{{ __tr('Digital Marketing Specialist') }}</p>
                        <div class="d-flex text-warning lw-spacing">
                            <i class="fa fa-star mx-1"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star mx-1"></i>
                            <i class="fa fa-star"></i>
                            <i class="fa fa-star mx-1"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- /testimonials -->

    <!-- FAQ -->
    <section class="bg-dark-blue">
        <div class="container">

            <!-- heading -->
            <h2 class="fw-bold mb-5 text-white text-center">{{ __tr('Frequently Asked Questions') }}
            </h2>
            <!-- /heading -->

            <div class="accordion" id="faqAccordion">
                <!-- FAQ Item 1 -->
                <div class="accordion-item">
                    <h5 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            {{ __tr('How do I sign up for __appName__?', [
                                '__appName__' => $appName,
                            ]) }}
                        </button>
                    </h5>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted p-4">
                            {{ __tr(
                                'Signing up for __appName__ is easy and straightforward. Just visit our sign-up page, fill in your details, and follow the instructions to get started.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </div>
                    </div>
                </div>
                <!-- /FAQ Item 1 -->

                <!-- FAQ Item 2 -->
                <div class="accordion-item">
                    <h5 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            {{ __tr('Can I import contacts from an existing customer database?') }}
                        </button>
                    </h5>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted p-4">
                            {{ __tr(
                                'Yes, __appName__ supports importing contacts through XLSX files. You can easily upload your existing customer database and start sending messages right away.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </div>
                    </div>
                </div>
                <!-- /FAQ Item 2 -->

                <!-- FAQ Item 3 -->
                <div class="accordion-item">
                    <h5 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            {{ __tr('What kind of support does __appName__ offer?', [
                                '__appName__' => $appName,
                            ]) }}
                        </button>
                    </h5>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted p-4">
                            {{ __tr(
                                '__appName__ offers 24/7 customer support through live chat, email, and phone. Our dedicated team is here to help you with any issues or questions you might have.',
                                [
                                    '__appName__' => $appName,
                                ],
                            ) }}
                        </div>
                    </div>
                </div>
                <!-- /FAQ Item 3 -->
            </div>
        </div>
    </section>
    <!-- /FAQ -->

    <!-- footer -->
    <footer class="pt-5 pb-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-sm-12 col-md-12 col-lg-4">
                    <!-- Logo -->
                    <div class="d-flex justify-content-center">
                        <a class="navbar-brand pt-0" href="{{ url('/') }}">
                            <!-- App Theme Change -->
                            @if ($currentAppTheme == 'dark')
                                {{-- dark theme logo --}}
                                <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                                    class="navbar-brand-img  dark-theme-logo" alt="{{ getAppSettings('name') }}">
                                <!-- /dark theme -->
                            @elseif($currentAppTheme == 'system_default')
                                <img src="{{ getAppSettings('logo_image_url') }}"
                                    class="navbar-brand-img light-theme-logo system-theme-light-logo"
                                    alt="{{ getAppSettings('name') }}">
                                {{-- dark theme logo --}}
                                <img src="{{ getAppSettings('dark_theme_logo_image_url') }}"
                                    class="navbar-brand-img  dark-theme-logo system-theme-dark-logo"
                                    alt="{{ getAppSettings('name') }}" media="(prefers-color-scheme: dark)">
                            @else
                                {{-- light theme logo --}}
                                <img src="{{ getAppSettings('logo_image_url') }}"
                                    class="navbar-brand-img light-theme-logo" alt="{{ getAppSettings('name') }}">
                                <!-- /App Theme Change -->
                            @endif
                            <!-- App Theme Change -->
                        </a>
                    </div>
                    <!-- Logo -->
                </div>
                <div class="col-sm-12 col-md-12 col-lg-8">
                    <!-- Links -->
                    <div class="d-flex lw-links">
                        <a href="#" class="text-dark">{{ __tr('Home') }}</a>
                        <div class="separator-line"></div>
                        <a href="{{ route('user.contact.form') }}" class="text-dark">{{ __tr('Contact') }}</a>
                        <a href="{{ route('auth.login') }}" class="text-dark">{{ __tr('Login') }}</a>
                        <a href="{{ route('auth.register') }}" class="text-dark">{{ __tr('Register') }}</a>
                    </div>
                    <!-- /Links -->
                </div>
            </div>
            <hr>
            <div class="text-muted small text-center">
                <div class="mb-2">&copy; {{ getAppSettings('name') }} {{ __tr(date('Y')) }}.
                    {{ __tr('All Rights Reserved.') }}</div>
            </div>
        </div>
    </footer>
    <!-- /footer -->
    <script>
        (function() {
            'use strict';
            window.appConfig = {
                debug: "{{ config('app.debug') }}",
                csrf_token: "{{ csrf_token() }}",
                locale: '{{ app()->getLocale() }}',
            }
        })();
    </script>
    {!! __yesset([
        'dist/js/common-vendorlibs.js',
        'dist/js/vendorlibs.js',
        'dist/packages/bootstrap/js/bootstrap.bundle.min.js',
        'dist/js/jsware.js',
    ]) !!}
    {!! getAppSettings('page_footer_code_all') !!}
    @if (isLoggedIn())
        {!! getAppSettings('page_footer_code_logged_user_only') !!}
    @endif
</body>

</html>
