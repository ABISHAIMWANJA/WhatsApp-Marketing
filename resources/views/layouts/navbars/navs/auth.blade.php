<!-- Top navbar -->
<nav class="navbar navbar-top navbar-expand-md navbar-dark d-md-flex d-none" id="navbar-main">
    <div class="container-fluid">
        <!-- Brand -->
         @if(hasVendorAccess() or hasVendorUserAccess())
        <span class="h3 mb-0 text-white d-none d-lg-inline-block">{!! __tr('Welcome to __vendorName__ Account', [
            '__vendorName__' => '<strong>'. getVendorSettings('title') .'</strong>'
        ]) !!}</span>
        @else
            <a class="h4 mb-0 text-white d-none d-lg-inline-block" href="{{ route('home') }}">{{ __tr('Dashboard') }}</a>
        @endif
        <!-- User -->
        <ul class="navbar-nav align-items-center d-none d-md-flex">
            @if(session('loggedByVendor') or session('loggedBySuperAdmin'))
              <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="lwLoggedAsSuperadminMenu" role="button" data-bs-toggle="dropdown"
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-sign-in-alt"></i> {{  __tr('Logged as') }} <i class="fas fa-exclamation text-warning"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-right">
                     @if(session('loggedByVendor'))
                    <div class=" dropdown-header noti-title">
                       <div class="text-muted" style="white-space: normal;text-transform:none">
                        {{ __tr('You (__userFullName__) are logged to this user account.', [
                            '__userFullName__' => session('loggedByVendor.name')
                        ]) }}
                       </div>
                    </div>
                    <a data-method="post" href="{{ route('vendor.user.write.logout_as') }}" class="lw-ajax-link-action px-5 dropdown-item">
                        <span>{{ __tr('Back to your account') }}</span>
                        <i class="fa fa-sign-in-alt mr-1"></i>
                    </a>
                    @elseif(session('loggedBySuperAdmin'))
                    <div class=" dropdown-header noti-title">
                       <div class="text-muted" style="white-space: normal;text-transform:none">
                         {{ __tr('You (__userFullName__) are logged to this vendor admin account.', [
                            '__userFullName__' => session('loggedBySuperAdmin.name')
                        ]) }}
                       </div>
                    </div>
                    <a data-method="post" href="{{ route('central.vendors.user.write.logout_as') }}" class="lw-ajax-link-action px-5 dropdown-item">
                        <span>{{ __tr('Back to Super Admin Area') }}</span>
                        <i class="fa fa-sign-in-alt mr-1"></i>
                    </a>
                    @endif
                </div>
              </li>
            @endif
              </li>
             <!--theme change -->
             @if(getAppSettings('allow_to_change_theme'))
             <li class="nav-item">
                @include('layouts.navbars.app-theme')
              </li>
             @endif
               <!--theme change -->
            @if(hasVendorAccess('messaging'))
            <li class="nav-item">
                <a class="nav-link lw-ajax-link-action" href="{{ route('vendor.disable.sound_message_sound_notification.write') }}"><i class="fa " :title="disableSoundForMessageNotification ? '{{ __tr('Sound Notifications are disabled for incoming messages') }}' : '{{ __tr('Sound Notifications are enabled for incoming messages') }}'" :class="disableSoundForMessageNotification ? 'fa-bell-slash' : 'fa-bell'"></i></a>
            </li>
            @endif
          <li class="nav-item">
            @include('layouts.navbars.locale-menu')
          </li>
            <li class="nav-item dropdown">
                <a class="nav-link pr-0 dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <i class="fa fa-user"></i> <span>{{ getUserAuthInfo('profile.full_name') }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-right">
                    <div class=" dropdown-header noti-title">
                        <h6 class="text-overflow m-0">{{ __tr('Welcome __firstName__', [
                            '__firstName' => getUserAuthInfo('profile.first_name')
                        ]) }}</h6>
                    </div>
                    <a href="{{ route('user.profile.edit') }}" class="dropdown-item">
                        <i class="fa fa-user mr-1"></i>
                        <span>{{ __tr('My Profile') }}</span>
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
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        <span>{{ __tr('Logout') }}</span>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>