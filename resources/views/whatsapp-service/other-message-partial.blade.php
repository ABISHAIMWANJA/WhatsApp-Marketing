@php
    $otherMessageType = $messageDataValues['type'] ?? null;
    $messageDataValues = $messageDataValues['data'] ?? [];
@endphp
@if ($otherMessageType)
@if ($otherMessageType != 'contacts')
<div class="lw-whatsapp-preview-message-container">
    <div class="lw-whatsapp-preview">
        <div class="card ">
            <div class="lw-whatsapp-header-placeholder ">
                @if ($otherMessageType == 'location')
                <iframe height="100" src="https://maps.google.com/maps/place?q={{ $messageDataValues['latitude'] ?? '' }},{{ $messageDataValues['longitude'] ?? '' }}&output=embed&language={{ app()->getLocale() }}" frameborder="0" scrolling="no"></iframe>
                @endif
            </div>
            @if ($otherMessageType == 'location')
            <div class="lw-whatsapp-location-meta bg-secondary text-white p-2">
                <small>{{ $messageDataValues['name'] ?? '' }}</small><br>
                <small>{{ $messageDataValues['address'] ?? '' }}</small>
            </div>
            @endif
            @isset($messageDataValues['caption'])
            <div class="p-2 lw-plain-message-text">{!! $messageDataValues['caption'] !!}</div>
            @endisset
        </div>
    </div>
</div>
@elseif ($otherMessageType == 'contacts')
    @foreach ($messageDataValues as $contact)
        <h3><strong>{{ $contact['name']['formatted_name'] ?? '' }}</strong></h3>
        @foreach ($contact as $contactDataKey => $contactDataValue)
            @if ($contactDataKey != 'name')
                @foreach ($contactDataValue as $contactDataItemKey => $contactDataItemValue)
                    <div>{{ $contactDataItemValue['type'] ?? '' }}: {{ $contactDataItemValue[Str::singular($contactDataKey)] ?? '' }}</div>
                @endforeach
            @endif
        @endforeach
        <hr>
    @endforeach
@endif
@else
<div class="lw-whatsapp-preview-message-container">
    <div class="lw-whatsapp-preview">
        <div class="card ">
            <div class="text-warning p-3">{{  __tr('Unknown message type.') }}</div>
        </div>
    </div>
</div>
@endif