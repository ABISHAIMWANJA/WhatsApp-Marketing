@if(!__isEmpty($templateComponents))
<div class="lw-whatsapp-preview-message-container">
    <div class="lw-whatsapp-preview">
        <div class="card">
            <div class="lw-whatsapp-body lw-ws-pre-line">
                @php
                    $exampleHeaderItems = [
                    "\n" => '<br>',
                    ];
                @endphp
                @if(isset($templateComponents[0]['example']['body_text']))
                    @php
                        $bodyTextItems = $templateComponents[0]['example']['body_text'];
                        $bodyTextExampleIndex = 1;
                        foreach ($bodyTextItems[0] as $bodyTextItem) {
                            $exampleHeaderItems["{{{$bodyTextExampleIndex}}}"] = $bodyTextItem;
                            $bodyTextExampleIndex++;
                        }
                    @endphp
                @endif
                {{ strtr(data_get($templateComponents, '0.text'), $exampleHeaderItems) }}
            </div>
        </div>
        <div class="lw-carousel-wrapper">
            <button class="lw-carousel-arrow prev" onclick="scrollSlide(this, false)">‹</button>
            <div class="lw-carousel-container">
                @if(isset($templateComponents[1]['cards']))
                    @foreach($templateComponents[1]['cards'] as $cardIndex => $cardItem)
                        <div class="lw-carousel-card">
                            <div class="lw-card-media">
                                @if($cardItem['components'][0]['format'] == 'IMAGE')
                                    {{-- <i class="fa fa-5x fa-image text-white"></i> --}}
                                    @if($templateComponentValues[1]['cards'][$cardIndex]['components'][0]['parameters'][0]['type'] == 'image')
                                        <?php $headerType = data_get($templateComponentValues, '1.cards.'.$cardIndex.'.components.0.parameters.0.type');  ?>
                                        <a class="lw-wa-message-document-link" target="_blank" href="{{ data_get($templateComponentValues, '1.cards.'.$cardIndex.'.components.0.parameters.0.'.$headerType.'.link') }}"><img class="lw-whatsapp-header-image" src="{{ data_get($templateComponentValues, '1.cards.'.$cardIndex.'.components.0.parameters.0.'.$headerType.'.link') }}" alt=""></a>
                                    @endif
                                    
                                @endif
                                @if($cardItem['components'][0]['format'] == 'VIDEO')
                                    @if($templateComponentValues[1]['cards'][$cardIndex]['components'][0]['parameters'][0]['type'] == 'video')
                                        <?php $headerType = data_get($templateComponentValues, '1.cards.'.$cardIndex.'.components.0.parameters.0.type');  ?>
                                        <video class="lw-whatsapp-header-video" controls src="{{ data_get($templateComponentValues, '1.cards.'.$cardIndex.'.components.0.parameters.0.'.$headerType.'.link') }}"></video>
                                    @endif
                                @endif
                            </div>
                            @if($cardItem['components'][1]['type'] == 'BODY')
                                <div class="lw-carousel-card-body">
                                    <div class="lw-card-desc lw-ws-pre-line">
                                        @php
                                            $exampleBodyItems = [
                                            "\n" => '<br>',
                                            ];
                                        @endphp
                                        @if(isset($cardItem['components'][1]['example']['body_text']))
                                            @php
                                                $bodyTextItems = $cardItem['components'][1]['example']['body_text'];
                                                $bodyTextExampleIndex = 1;
                                                foreach ($bodyTextItems[0] as $bodyTextItem) {
                                                    $exampleBodyItems["{{{$bodyTextExampleIndex}}}"] = $bodyTextItem;
                                                    $bodyTextExampleIndex++;
                                                }
                                            @endphp
                                        @endif
                                        {{ strtr($cardItem['components'][1]['text'], $exampleBodyItems) }}
                                    </div>
                                </div>
                            @endif
                            <div class="card-footer lw-whatsapp-buttons">
                                <div class="list-group list-group-flush lw-whatsapp-buttons">
                                    @if($cardItem['components'][2]['type'] == 'BUTTONS')
                                        @foreach($cardItem['components'][2]['buttons'] as $buttonIndex => $button)
                                        <div>
                                            <div class="list-group-item">
                                                @if($button['type'] == 'QUICK_REPLY')
                                                    <i class="fa fa-reply"></i>
                                                @endif
                                                @if($button['type'] == 'PHONE_NUMBER')
                                                    <i class="fa fa-phone-alt"></i>
                                                @endif
                                                @if($button['type'] == 'URL')
                                                    <i class="fas fa-external-link-square-alt"></i>
                                                @endif
                                                <span>{{ $button['text'] }}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <button class="lw-carousel-arrow next" onclick="scrollSlide(this, true)">›</button>
        </div>
    </div>
</div>
@endif