@php
$apiRequestRoute = route('api.vendor.chat_template_message.send.process', ['vendorUid' => getVendorUid()]);
$parameterData = [
    'from_phone_number_id' => '<span x-text="fromPhoneNumberId"></span>',
    'phone_number' => '<span x-text="phoneNumber"></span>',
    'template_name' => $template['template_name'],
    'template_language' => $template['language'],
];
if ($headerFormat == 'IMAGE') {
    $parameterData['header_image'] = __tr('Insert image URL here');
} elseif ($headerFormat == 'VIDEO') {
    $parameterData['header_video'] = __tr('Insert video URL here');
} elseif ($headerFormat == 'DOCUMENT') {
    $parameterData['header_document'] = __tr('Insert document URL here');
    $parameterData['header_document_name'] = '<span id="lw_header_document_name_text"></span>';
} elseif ($headerFormat == 'LOCATION') {
    $parameterData['location_latitude'] = '<span id="lw_location_latitude_text"></span>';
    $parameterData['location_longitude'] = '<span id="lw_location_longitude_text"></span>';
    $parameterData['location_name'] = '<span id="lw_location_name_text"></span>';
    $parameterData['location_address'] = '<span id="lw_location_address_text"></span>';
}
// Merge header parameters
if (!__isEmpty($headerParameters)) {
    $headerParameterData = [];
    foreach($headerParameters as $headerParameter) {
        $headerParameterData[$headerParameter] = '<span id="lw_'.$headerParameter.'_text"></span>';
    }
    $parameterData = array_merge($parameterData, $headerParameterData);
}
// Merge body parameters
if (!__isEmpty($bodyParameters)) {
    $bodyParameterData = [];
    foreach($bodyParameters as $bodyParameterValue) {
        $bodyParameterData[$bodyParameterValue] = '<span id="lw_'.$bodyParameterValue.'_text"></span>';
    }
    $parameterData = array_merge($parameterData, $bodyParameterData);
}
// Merge button parameters
if (!__isEmpty($buttonParameters)) {
    $buttonParameterData = [];
    foreach($buttonParameters as $btnParameterValue) {
        $buttonParameterData[$btnParameterValue] = '<span id="lw_'.$btnParameterValue.'_text"></span>';
    }
    $parameterData = array_merge($parameterData, $buttonParameterData);
}
// Merge button items
if (!__isEmpty($buttonItems)) {
    if (array_key_exists('COPY_CODE', $buttonItems)) {
        $parameterData = array_merge($parameterData, [
            'copy_code' => '<span id="lw_copy_code_text"></span>'
        ]);
    }
}
// Check if template type is Carousel
if ($templateType == 'CAROUSEL') {
    $carouselTemplateDetails = [];
    if (!__isEmpty($carouselTemplateData)) {
        foreach($carouselTemplateData[1]['cards'] as $cardIndex => $carouselTemplateCard) {
            $mediaType = $carouselTemplateCard['components'][0]['format'];
            $buttonTypes = [];
            foreach($carouselTemplateCard['components'][2]['buttons'] as $carouselButton) {
                $buttonTypes[] = $carouselButton['type'];
            }
            $carouselBodyData = [];
            if (isset($carouselTemplateCard['components'][1]['example'])) {
                foreach ($carouselTemplateCard['components'][1]['example']['body_text'][0] as $bodyTextIndex => $bodyTextExample) {
                    $carouselBodyData[] = "<span id='lw_"."$cardIndex"."_"."$bodyTextIndex"."_text'></span>";
                }
            }
            $carouselTemplateDetails[] = [
                'media_type' => $mediaType,
                'media_url' => __tr('Insert __media__ URL here', ['__media__' => strtolower($mediaType)]),
                'button_type' => $buttonTypes,
                'carouselBodyData' => $carouselBodyData
            ];
        }

        $parameterData['carousel_templates'] = $carouselTemplateDetails;
    }
}
// Check if template requested for create new campaign
if ($pageType == 'create-new-campaign') {
    $apiRequestRoute = route('api.vendor.campaign.write.schedule', ['vendorUid' => getVendorUid()]);
    unset($parameterData['phone_number']);
    $parameterData = array_merge($parameterData, [
        'title' => '<span x-text="campaignTitle"></span>',                
        'contact_group' => '<span x-text="contactGroup"></span>', 
        'contact_labels' => '<span x-text="labelTags"></span>', 
        'restrict_by_templated_contact_language' => '<span x-text="restrictByTemplatedContactLanguage"></span>', 
        'timezone' => '<span x-text="campaignTimeZone"></span>', 
        'schedule_at' => '<span x-text="scheduleAt"></span>', 
        'expire_at' => '<span x-text="expireAt"></span>',
    ]);
}
// Prepare carousel template payload
function prepareCarouselPayload($carouselData) {
    
    $carouselKeys = array_keys($carouselData);
    $lastKey = end($carouselKeys);
    $carouselHtml = '"carousel_templates": [';
    foreach ($carouselData as $carouselIndex => $carouselValue) {
        $isLastKey = ($carouselIndex != $lastKey) ? ',' : '';
        $carouselHtml .= '{
                    "media_type": "'.$carouselValue['media_type'].'",
                    "media_url": "'.$carouselValue['media_url'].'",
                    "body_example_fields": ["'.implode('","', $carouselValue['carouselBodyData']).'"],
                    "button_type": ["'.implode('","', $carouselValue['button_type']).'"]
                }'.$isLastKey;
    }
    $carouselHtml .= '],';

    return $carouselHtml;
}
@endphp
<div class="mt-4">
    <button id="lwApiCodeCopyBtn" class="btn btn-outline-light float-right" type="button" onclick="copyCodeToClipboard('{{ __tr('Copied!') }}')">
        <i class="fas fa-copy"></i> <?= __tr('Copy') ?>
    </button>
    <h3>{{ __tr('API Request') }}</h3>
    <pre class="lw-pre-wrap">
        <code id="lwApiRequestCode">
    curl --location '{{ $apiRequestRoute }}' \
        --header 'Content-Type: application/json' \
        --header 'Authorization: Bearer {{ getVendorSettings('vendor_api_access_token') }}' \
        --data '{
    @foreach($parameterData as $parameterKey => $parameterValue)
    @if($parameterKey == 'carousel_templates')
    {!! prepareCarouselPayload($parameterValue)!!}
    @else
    "{{ $parameterKey }}": "{!! $parameterValue !!}"{{ !$loop->last ? ',' : '' }}
    @endif
    @endforeach
}'
        </code>
    </pre>
</div>