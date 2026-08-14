@php
$onlyTemplatePreview = request()->has('only-preview');
if ($pageType == 'show-only-preview') {
    $onlyTemplatePreview = true;
} 
@endphp
<div class="row" x-cloak>
    @if(!$onlyTemplatePreview)
    <div class="col-sm-12 col-md-8 col-lg-6 lw-template-structure-form">
        <input type="hidden" name="template_uid" value="{{ $template->_uid }}">
        <fieldset>
            <legend>{{ __tr('Template') }} <template x-if="selectedTemplate">
                    <button class="btn btn-dark btn-sm lw-change-template-btn" @click.prevent="selectedTemplate = ''">{{ __tr('Change')
                        }}</button>
                </template></legend>
            <h3><strong>{{ $template->template_name }}</strong></h3>
            <h4>{{ __tr('Language Code') }}: <strong>{{ $template->language }}</strong></h4>
            <h4>{{ __tr('Category') }}: <strong>{{ $template->category }}</strong></h4>
        </fieldset>
        {{-- Header --}}
        @if($headerFormat)
        <fieldset class="lw-template-header-variables-container">
            <legend>{{ __tr('Header') }}</legend>
            @if($headerFormat == 'LOCATION')
            <h3>{{ __tr('Location Details') }}</h3>
            @include('whatsapp-service.template-partial', [
            'parameters' => [
            'location_latitude',
            'location_longitude',
            'location_name',
            'location_address',
            ],
            'subjectType' => 'header',
            ])
            @elseif($headerFormat == 'TEXT' and !__isEmpty($headerParameters))
            @include('whatsapp-service.template-partial', [
            'parameters' => $headerParameters,
            'subjectType' => 'header',
            ])
            @elseif($headerFormat == 'TEXT' and __isEmpty($headerParameters))
            <div class="alert alert-info">{{  __tr('No variables available for header text.') }}</div>
            <style>
                .lw-template-header-variables-container{display:none;}
            </style>
            @elseif($headerFormat == 'IMAGE')
            <div class="form-group col-md-4 col-sm-12">
                <label for="lwHeaderImageFilepond">{{ __tr('Select Image') }}</label>
                <input id="lwHeaderImageFilepond" type="file" data-allow-revert="true"
                    data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                    data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_image') }}'
                    data-file-input-element="#lwHeaderImage"<?= ($pageType == 'bot_reply_form') ? "data-lw-plugin=\"lwUploader\"" : ''; ?>>
                <input id="lwHeaderImage" type="hidden" value="" name="header_image" />
            </div>
            @elseif($headerFormat == 'VIDEO')
            <div class="form-group col-md-4 col-sm-12">
                <label for="lwHeaderVideoFilepond">{{ __tr('Select Video') }}</label>
                <input id="lwHeaderVideoFilepond" type="file" data-allow-revert="true"
                    data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                    data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_video') }}'
                    data-file-input-element="#lwHeaderVideo"<?= ($pageType == 'bot_reply_form') ? "data-lw-plugin=\"lwUploader\"" : ''; ?>>
                <input id="lwHeaderVideo" type="hidden" value="" name="header_video" />
            </div>
            @elseif($headerFormat == 'DOCUMENT')
            <div class="form-group col-md-4 col-sm-12">
                <label for="lwHeaderDocumentFilepond">{{ __tr('Select Document') }}</label>
                <input id="lwHeaderDocumentFilepond" type="file" data-allow-revert="true"
                    data-label-idle="{{ __tr('Select Document') }}" class="lw-file-uploader" data-instant-upload="true"
                    data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_document') }}'
                    data-file-input-element="#lwHeaderDocument">
                <input id="lwHeaderDocument" type="hidden" value="" name="header_document" />
            </div>
            @include('whatsapp-service.template-partial', [
            'parameters' => [
            'header_document_name'
            ],
            'subjectType' => 'header',
            ])
            @endif
        </fieldset>
        @endif
        {{-- /Header --}}
        {{-- Body Variables --}}
        @if(!__isEmpty($bodyParameters))
        <fieldset>
            <legend>{{ __tr('Body') }}</legend>
            @include('whatsapp-service.template-partial', [
            'parameters' => $bodyParameters,
            'subjectType' => 'body',
            ])
        </fieldset>
        @endif
        {{-- /Body Variables --}}
        {{-- Button Variables --}}
        @if(!__isEmpty($buttonParameters) or !__isEmpty($buttonItems))
        <fieldset>
            <legend>{{ __tr('Buttons') }}</legend>
            @include('whatsapp-service.template-partial', [
            'parameters' => $buttonParameters,
            'buttonItems' => $buttonItems,
            'subjectType' => 'button',
            ])
            @if(array_key_exists('COPY_CODE', $buttonItems))
            <label for="">{{ __tr('Code for Copy') }}</label>
            @include('whatsapp-service.template-partial', [
            'parameters' => [
            'copy_code'
            ],
            'buttonItems' => [],
            'subjectType' => 'button',
            ])
            @endif
        </fieldset>
        @endif
        {{-- /Button Variables --}}

        @if(!__isEmpty($carouselTemplateData))
            <fieldset>
                <legend>{{ __tr('Cards') }}</legend>
                @foreach ($carouselTemplateData[1]['cards'] as $cardIndex => $cardItem)
                    <fieldset>
                        <legend>{{ $cardIndex + 1 }}</legend>
                        @if($cardItem['components'][0]['type'] == 'HEADER')
                            <h3><u>{{ __tr('Media Header') }}</u></h3>
                            <input id="lwUploadMediaType" type="hidden" value="{{ $cardItem['components'][0]['format'] }}" name="carousel_templates[{{$cardIndex}}][uploaded_media_file_type]" />
                            
                            @if($cardItem['components'][0]['format'] == 'IMAGE')
                                <div class="form-group col-md-4 col-sm-12">
                                    <label for="lwHeaderImage{{ $cardIndex }}">{{ __tr('Select Image') }}</label>
                                    <input id="lwHeaderImage{{ $cardIndex }}" type="file" data-allow-revert="true"
                                        data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader" data-instant-upload="true"
                                        data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_image') }}'
                                        data-file-input-element="#lwHeaderUpload{{$cardIndex}}"<?= ($pageType == 'bot_reply_form') ? "data-lw-plugin=\"lwUploader\"" : ''; ?>>
                                    <input id="lwHeaderUpload{{$cardIndex}}" type="hidden" value="" name="carousel_templates[{{$cardIndex}}][uploaded_media_file_name]" required="true"/>
                                </div>
                            @endif

                            @if($cardItem['components'][0]['format'] == 'VIDEO')
                                <div class="form-group col-md-4 col-sm-12">
                                    <label for="lwHeaderImage{{ $cardIndex}}">{{ __tr('Select Video') }}</label>
                                    <input id="lwHeaderImage{{ $cardIndex}}" type="file" data-allow-revert="true"
                                        data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader" data-instant-upload="true"
                                        data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>" data-allowed-media='{{ getMediaRestriction('whatsapp_video') }}'
                                        data-file-input-element="#lwHeaderUpload{{$cardIndex}}" <?= ($pageType == 'bot_reply_form') ? "data-lw-plugin=\"lwUploader\"" : ''; ?>>
                                    <input id="lwHeaderUpload{{$cardIndex}}" type="hidden" value="" name="carousel_templates[{{$cardIndex}}][uploaded_media_file_name]" required="true"/>
                                </div>
                            @endif
                        @endif
                                    
                        @if($cardItem['components'][1]['type'] == 'BODY')
                            @if(isset($cardItem['components'][1]['example']))
                                <h3><u>{{ __tr('Body Text') }}</u></h3>
                                <?php 
                                    $carouselBodyData = [];
                                    foreach ($cardItem['components'][1]['example']['body_text'][0] as $bodyTextIndex => $bodyTextExample) {
                                        $carouselBodyData[] = [
                                            'id' => 'lwField_'. $cardIndex. '_' .$bodyTextIndex, 
                                            'label' => 'field_' . $bodyTextIndex + 1,
                                            'name' => "carousel_templates[$cardIndex][body_example_fields][$bodyTextIndex]"
                                        ];
                                    }
                                ?>
                                
                                @include('whatsapp-service.template-partial', [
                                    'parameters' => $carouselBodyData,
                                    'subjectType' => 'body',
                                ])
                            @endif
                        @endif

                        @if($cardItem['components'][2]['type'] == 'BUTTONS')
                            <h3><u>{{ __tr('Buttons') }}</u></h3>
                            @foreach($cardItem['components'][2]['buttons'] as $buttonIndex => $button)
                                <input type="hidden" value="{{ $button['type'] }}" name="carousel_templates[{{$cardIndex}}][button_type][{{$buttonIndex}}]">
                                @if($button['type'] == 'QUICK_REPLY')
                                    <x-lw.input-field id="lwQuickReplyPayload"
                                        type="text" 
                                        data-form-group-class=""
                                        :label="__tr('Quick Reply Button Payload')"
                                        name="carousel_templates[{{$cardIndex}}][quick_reply_button_payload]">
                                    </x-lw.input-field>
                                @endif

                                @if(isset($button['example']))
                                    @include('whatsapp-service.template-partial', [
                                        'parameters' => [
                                            [
                                                'id' => 'lwField_'. $cardIndex. '_' .$buttonIndex, 
                                                'label' => 'field_' . $buttonIndex,
                                                'name' => "carousel_templates[$cardIndex][button_example_field]"
                                            ]
                                        ],
                                        'subjectType' => 'body',
                                    ])
                                @endif
                            @endforeach
                        @endif
                    </fieldset>
                @endforeach
            </fieldset>
        @endif
        
        {{-- Is it marketing template --}}
        @if($template->category == 'MARKETING')
            @if(getVendorSettings('marketing_messages_onboarding_status') == 'ONBOARDED' or (config('__misc.lw_internal_debug') and getVendorSettings('marketing_messages_onboarding_status') == 'PENDING_VALID_PAYMENT_METHOD'))
                <div class="mt-4">
                    <x-lw.checkbox id="lwSendMessageViaMarketingMessageAPI" :offValue="0" data-lw-plugin="lwSwitchery" :label="__tr('Use Marketing Message API')" name="send_message_via_marketing_message_api"/>

                </div>
            @endif
        @endif
        {{-- Is it marketing template --}}
    </div>
    {{-- Message Preview --}}
    <div class="col-sm-12 col-md-8 col-lg-6">
        <fieldset class="w-100 lw-position-block">
            <legend>{{ __tr('Message Preview') }}</legend>
            <div class="card">
                <div class="card-body">
                    @else
                    <div class="col-12">
                    @endif
                    @include('whatsapp-service.template-preview-partial', [
            'bodyComponentText' => $bodyComponentText,
            'parameters' => $bodyParameters,
            'subjectType' => 'body',
            'templateComponents' => $templateComponents,
            'carouselTemplateData' => $carouselTemplateData
            ])
 </div>
@if(!$onlyTemplatePreview)
            </div>
            <div class="alert alert-light mt-5">
                <strong>{{  __tr('Please note:') }}</strong>
               {!! __tr('Words like @{{1}}, @{{abc}} etc are dynamic variables and will be replaced based on your selections.') !!}
            </div>
            @endif
            
            @php
                $vendorPlanDetails = vendorPlanDetails('api_access', 0, getVendorId());
            @endphp
            @if($vendorPlanDetails['is_limit_available'] and in_array($pageType, ['send-template-message', 'create-new-campaign']))
            @include('whatsapp-service.api-request-preview', [
                'bodyParameters' => $bodyParameters,
                'headerParameters' => $headerParameters,
                'buttonParameters' => $buttonParameters,
                'headerFormat' => $headerFormat,
                'template' => $template,
                'templateType' => $templateType,
                'carouselTemplateData' => $carouselTemplateData,
                'pageType' => $pageType
            ])
            @endif
        </fieldset>
    </div>
</div>