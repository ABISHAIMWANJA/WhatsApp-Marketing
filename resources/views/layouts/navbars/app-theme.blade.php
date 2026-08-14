<!-- theme Menu -->
@php
$themeOptions = configItem('theme_options');
// Default theme from settings
$currentAppTheme = getUserAppTheme()
@endphp
<li class="nav-item dropdown no-arrow">
    <a class="nav-link dropdown-toggle" href="#" id="themeMenuDropdown" role="button" data-bs-toggle="dropdown"
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{-- {{ isset($themeOptions[$currentAppTheme]) ? $themeOptions[$currentAppTheme] : '' }} --}}
        <span class="d-md-inline-block">
            @if($currentAppTheme === 'dark')
            <i class="fas fa-moon"></i> <!-- Icon for dark theme -->
            @elseif($currentAppTheme === 'light')
            <i class="fas fa-sun text-yellow"></i> <!-- Icon for light theme -->
            @elseif($currentAppTheme === 'system_default')
            <i class=" fas fa-desktop text-dark text-black"></i> <!-- Icon for default theme -->
            @else
            <i class="fas fa-palette"></i> <!-- Fallback icon for unknown themes -->
            @endif
        </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-right dropdown-menu-end shadow animated--grow-in"
        aria-labelledby="themeMenuDropdown">
        <li class="dropdown-item dropdown-header text-gray disabled">
            {{ __tr('Choose Mode') }}
        </li>
        <li class="dropdown-divider"></li>
        <!-- System -->
        <li class="@if($currentAppTheme === 'system_default') active @endif">
            <a class="dropdown-item lw-ajax-link-action d-flex align-items-center" data-show-processing="true"
                href="{{ route('change.app.theme', ['themeID' => 'system_default']) }}">
                <span >
                    <i class="fas fa-desktop mr-3 "></i>
                </span>
                <span > {{ __tr('System') }}</span>
            </a>
        </li>
        <!-- /System -->

        <!-- Dark -->
        <li class="@if($currentAppTheme  === 'dark') active @endif">
            <a class="dropdown-item lw-ajax-link-action d-flex align-items-center " data-show-processing="true"
                href="{{ route('change.app.theme', ['themeID' => 'dark']) }}">
                <span class="">
                    <i class="fas fa-moon mr-3"></i>
                </span>
                <span> {{ __tr('Dark') }}</span>
            </a>
        </li>
        <!-- /Dark -->

        <!-- Light  -->
        <li class="@if($currentAppTheme  === 'light') active @endif">
            <a class="dropdown-item lw-ajax-link-action d-flex align-items-center " data-show-processing="true"
                href="{{ route('change.app.theme', ['themeID' => 'light']) }}">
                <span class="">
                    <i class="fas fa-sun mr-3 text-yellow"></i>
                </span>
                <span> {{ __tr('Light') }}</span>
            </a>
        </li>
        <!-- /Light -->
    </ul>
</li>


<!--theme Menu -->