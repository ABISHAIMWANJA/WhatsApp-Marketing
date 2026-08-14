<?php use Carbon\CarbonInterval; ?>
@if(!__isEmpty($whatsappCallingData))
    @php
        $callDuration = data_get($whatsappCallingData, 'terminate.0.changes.0.value.calls.0.duration');
        if (!__isEmpty($callDuration)) {
            $interval = CarbonInterval::seconds($callDuration);
            $humanFormatDuration = $interval->cascade()->forHumans();
        }
        $isRinging = array_key_exists('ringing', $whatsappCallingData);
        $isTerminated = array_key_exists('terminate', $whatsappCallingData);
        $callDirection = data_get($whatsappCallingData, 'incoming.0.changes.0.value.calls.0.direction');
        $isIncomingCall = ($callDirection == 'USER_INITIATED') ? true : false;
        $accepted = data_get($whatsappCallingData, 'accepted');
    @endphp
    <div class="lw-whatsapp-calling-chat-box">
        @if(__isEmpty($callDuration))
            @if($accepted and !$isTerminated and (!$isIncomingCall or !$isRinging))
                <div class="lw-whatsapp-call-message ended">
                    <span class="lw-whatsapp-call-message-icon-circle">
                        <i class="fas fa-phone fa-rotate-90"></i>
                    </span>
                    {{ __tr('Accepted...') }}
                </div>
            @endif

            {{-- Check if call is terminated --}}
            @if($isTerminated)
                <!-- Missed call -->
                <div class="lw-whatsapp-call-message missed">
                    <span class="lw-whatsapp-call-message-icon-circle">
                        <i class="fas fa-phone-slash fa-rotate-90"></i>
                    </span>
                    {{ __tr('Missed voice call') }}
                </div>
            @endif
            {{-- /Check if call is terminated --}}

            {{-- Check if incoming call --}}
            @if($isIncomingCall and !$isTerminated and !$accepted)
                <div class="lw-whatsapp-call-message ended">
                    <span class="lw-whatsapp-call-message-icon-circle">
                        <i class="fas fa-phone fa-rotate-90"></i>
                    </span>
                    {{ __tr('Ringing...') }}
                </div>
            @endif
            {{-- /Check if incoming call --}}
            
            {{-- Check if outgoing call initiated and status is ringing --}}
            @if($isRinging and !$isTerminated and !$accepted)
                <div class="lw-whatsapp-call-message ended">
                    <span class="lw-whatsapp-call-message-icon-circle">
                        <i class="fas fa-phone fa-rotate-90"></i>
                    </span>
                    {{ __tr('Ringing...') }}
                </div>
            @endif
            {{-- /Check if outgoing call initiated and status is ringing --}}
        @endif

        @if(!__isEmpty($callDuration))
            <!-- Ended call -->
            <div class="lw-whatsapp-call-message ended">
                <span class="lw-whatsapp-call-message-icon-circle">
                    <i class="fas fa-phone fa-rotate-90"></i>
                </span>
                {{ __tr('Voice call ended — __time__', ['__time__' => $humanFormatDuration]) }}
            </div>
        @endif
    </div>
@else
    <div class="lw-whatsapp-call-message ended">
        <span class="lw-whatsapp-call-message-icon-circle">
            <i class="fas fa-phone fa-rotate-90"></i>
        </span>
        {{ __tr('Something went wrong.') }}
    </div>
@endif
