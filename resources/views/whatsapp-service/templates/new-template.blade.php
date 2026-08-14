@extends('layouts.app', ['title' => __tr('Create New Template')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Create New Template'),
'description' => '',
'class' => 'col-lg-7'
])
<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="float-right">
                <a class="lw-btn btn btn-secondary" href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">{{
                    __tr('Back to Templates') }}</a>
                    <a href="https://business.facebook.com/business/help/2055875911147364" target="_blank" class="btn btn-default">{{  __tr('Help') }}</a>
            </div>
        </div>
    </div>
    <div x-ref="template-data" id="lwNewTemplateData" class="col-12" x-data="{
    templateType: 'header',
    carousel_body_text: '',
    carouselBodyTextVariables: [],
    carouselTemplateContainer: [
        {
            headerType: '',
            bodyText: '',
            bodyTextVariables: [],
            cardButtons: {
                totalAllowedButtons:2,
                totalButtonsUsed:0,
                buttonUsesByTypes:{
                    QUICK_REPLY:0,
                    QUICK_REPLY_LIMIT:1,
                    URL:0,
                    URL_BUTTON_LIMIT:1,
                    PHONE_NUMBER:0,
                    PHONE_NUMBER_LIMIT:1
                },
                data:[],
                buttonModels: []
            }
        }
    ],
    totalAllowCards: 10,
    totalUsedCards: 1,

    headerType:'',
    header_text_body:'',
    footer_text_body:'',
    text_body:'',
    enableHeaderVariableExample:false,
    newBodyTextInputFields:[],
    buttonModels:{},
    templateName: '',
    error: '',
    customButtons:{
        totalAllowedButtons:10,
        totalButtonsUsed:0,
        buttonUsesByTypes:{
            URL_BUTTON:0,
            URL_BUTTON_LIMIT:2,
            COPY_CODE:0,
            COPY_CODE_LIMIT:1,
            VOICE_CALL:0,
            VOICE_CALL_LIMIT:1,
            PHONE_NUMBER:0,
            PHONE_NUMBER_LIMIT:1
        },
        totalUrlButtonUsed:0,
        data:{},
    }, addWhatsAppButtonOption : function(buttonType) {
        let uniqueBtnId = _.uniqueId();
        this.customButtons.data[uniqueBtnId] = {
            buttonType : buttonType,
            buttonIndex : uniqueBtnId
        };
        this.customButtons.totalButtonsUsed++;
        if((buttonType == 'URL_BUTTON') || (buttonType == 'DYNAMIC_URL_BUTTON')) {
            this.customButtons.buttonUsesByTypes['URL_BUTTON']++;
        } else if((buttonType == 'COPY_CODE')) {
            this.customButtons.buttonUsesByTypes['COPY_CODE']++;
        } else if((buttonType == 'VOICE_CALL')) {
            this.customButtons.buttonUsesByTypes['VOICE_CALL']++;
        } else if((buttonType == 'PHONE_NUMBER')) {
            this.customButtons.buttonUsesByTypes['PHONE_NUMBER']++;
        }
    }, deleteWhatsAppButtonOption : function(buttonIndex) {
        let buttonType = this.customButtons.data[buttonIndex]['buttonType'];
        if((buttonType == 'URL_BUTTON') || (buttonType == 'DYNAMIC_URL_BUTTON')) {
            this.customButtons.buttonUsesByTypes['URL_BUTTON']--;
        } else if((buttonType == 'COPY_CODE')) {
            this.customButtons.buttonUsesByTypes['COPY_CODE']-- ;
        } else if((buttonType == 'VOICE_CALL')) {
            this.customButtons.buttonUsesByTypes['VOICE_CALL']-- ;
        } else if((buttonType == 'PHONE_NUMBER')) {
            this.customButtons.buttonUsesByTypes['PHONE_NUMBER']-- ;
        }
        delete this.customButtons.data[buttonIndex];
        delete this.buttonModels[buttonIndex];
        this.customButtons.totalButtonsUsed--;
    }, sanitizeInput() {
        // Remove invalid characters on paste or fast typing
        this.templateName = this.templateName.replace(/[^a-z0-9_]/g, '');
    }, isValidKey(e) {
        const key = e.key;

        const allowedKeys = [
            'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'
        ];

        const isLetter = key >= 'a' && key <= 'z';
        const isNumber = key >= '0' && key <= '9';
        const isUnderscore = key === '_';
        const isAllowed = isLetter || isNumber || isUnderscore || allowedKeys.includes(key);

        if (!isAllowed) {
            e.preventDefault();
        }
    }, addNewCard : function() {
        this.carouselTemplateContainer.push({
            headerType: 'image',
            bodyText: '',
            bodyTextVariables: [],
            cardButtons: {
                totalAllowedButtons:2,
                totalButtonsUsed:0,
                buttonUsesByTypes:{
                    QUICK_REPLY:0,
                    QUICK_REPLY_LIMIT:1,
                    URL:0,
                    URL_BUTTON_LIMIT:1,
                    PHONE_NUMBER:0,
                    PHONE_NUMBER_LIMIT:1
                },
                totalUrlButtonUsed:0,
                data:[],
                buttonModels: []
            }
        });
        this.totalUsedCards++;

        if (this.carouselTemplateContainer.length > 1)  {
            let templateBtnData = this.carouselTemplateContainer[0]['cardButtons']['data'];
            _.forEach(this.carouselTemplateContainer, function(item, key) {
                if (key >= 1) {
                    item.cardButtons.data = templateBtnData;
                }            
            });
        }
        
        _.defer(function() {
            window.lwPluginsInit();
        });
    }, deleteCard: function(index) {
        this.carouselTemplateContainer.splice(index, 1);
        this.totalUsedCards--;
    }, loadPlugin: function() {
        window.lwPluginsInit();
    }, addNewVariable: function(targetId, templateType) {
        addNewPlaceholder(targetId, templateType);
        this.$nextTick(() => {
            this.$refs.lwCardBodyTextarea.dispatchEvent(new Event('input'));
        });
    }, addCardButtonOptions: function(index, buttonType) {
        
        let templateButtonData = {
            buttonType : buttonType,
            buttonIndex : _.uniqueId()
        };
        this.carouselTemplateContainer[index]['cardButtons']['data'].push(templateButtonData);
        let cardBtnData = this.carouselTemplateContainer[index]['cardButtons']['data'];
        
        _.forEach(this.carouselTemplateContainer, function(item, key) {
            if (key >= 1) {
                item.cardButtons.data = cardBtnData;
            }            
        });

        this.totalButtonsUsed++;
        if (_.has(this.carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes'], buttonType)) {
            this.carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes'][buttonType]++;
            this.carouselTemplateContainer[index]['cardButtons']['totalButtonsUsed']++;
        }
    }, deleteCardButtonOption: function(index, buttonIndex, buttonType) {
        this.carouselTemplateContainer[index]['cardButtons']['data'].splice(buttonIndex, 1);
        this.carouselTemplateContainer[index]['cardButtons']['buttonModels'].splice(buttonIndex, 1);
        let afterDeleteBtnData = this.carouselTemplateContainer[index]['cardButtons']['data'];
        
        _.forEach(this.carouselTemplateContainer, function(item, key) {
            if (key >= 1) {
                item.cardButtons.data = afterDeleteBtnData;
                item.cardButtons.buttonModels.splice(buttonIndex, 1);
            }            
        });
        this.carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes'][buttonType]--;
        this.carouselTemplateContainer[index]['cardButtons']['totalButtonsUsed']--;
    }, changeTemplateType: function(templateType) {
        var categorySelectizeInstance = $('#lwSelectCategoryField')[0].selectize;
        if (templateType == 'carousel') {
            this.carousel_body_text = '';
            this.carouselBodyTextVariables = [];
            this.carouselTemplateContainer = [
                {
                    headerType: 'image',
                    bodyText: '',
                    bodyTextVariables: [],
                    cardButtons: {
                        totalAllowedButtons:2,
                        totalButtonsUsed:0,
                        buttonUsesByTypes:{
                            QUICK_REPLY:0,
                            QUICK_REPLY_LIMIT:1,
                            URL:0,
                            URL_BUTTON_LIMIT:1,
                            PHONE_NUMBER:0,
                            PHONE_NUMBER_LIMIT:1
                        },
                        data:[],
                        buttonModels: []
                    }
                }
            ];
            this.totalAllowCards = 10;
            this.totalUsedCards = 1;
            this.loadPlugin();
            categorySelectizeInstance.removeOption('UTILITY');
            categorySelectizeInstance.setValue('MARKETING', true);
        } else if (templateType == 'header') {
            this.headerType = '';
            this.header_text_body = '';
            this.footer_text_body = '';
            this.text_body = '';
            this.enableHeaderVariableExample = false;
            this.newBodyTextInputFields = [];
            this.buttonModels = {};
            this.templateName =  '';
            this.error =  '';
            this.customButtons = {
                totalAllowedButtons:10,
                totalButtonsUsed:0,
                buttonUsesByTypes:{
                    URL_BUTTON:0,
                    URL_BUTTON_LIMIT:2,
                    COPY_CODE:0,
                    COPY_CODE_LIMIT:1,
                    VOICE_CALL:0,
                    VOICE_CALL_LIMIT:1,
                    PHONE_NUMBER:0,
                    PHONE_NUMBER_LIMIT:1
                },
                totalUrlButtonUsed:0,
                data:{},
            };
            
            categorySelectizeInstance.addOption({ value: 'UTILITY', text: 'UTILITY' });
            categorySelectizeInstance.refreshOptions(false);
        }
    }
    }">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <x-lw.form id="lwNewTemplateCreationForm"
                            :action="route('vendor.whatsapp_service.templates.write.create')">
                            <!-- Template Name -->
                            <x-lw.input-field type="text" id="lwTemplateNameField" data-form-group-class=""
                                :label="__tr('Template Name')" name="template_name" x-model="templateName" 
                                @input="sanitizeInput();" @keydown="isValidKey($event)" required="true"/>
                            <span class="form-text text-muted mt-3 text-sm"><a target="_blank"
                                    href="https://developers.facebook.com/docs/whatsapp/message-templates/guidelines/">{{
                                    __tr('Template Formatting Help') }}</a></span>
                            <!-- /Template Name -->
                            
                            <!-- Template Language -->
                            <label for="lwSelectLanguage"><?= __tr('Template Language Code') ?></label>
                            <select id="lwSelectLanguage" placeholder="<?= __tr('Select Template Language ...') ?>" name="language_code" required>
                                @if(!__isEmpty($languages))
                                    <option value="">{{ __tr('Select Template Language ...') }}</option>
                                    @foreach($languages as $key => $language)
                                    <option value="<?= $language['code'] ?>" required><?= $language['language'] ?> (<?= $language['code'] ?>)</option>
                                    @endforeach
                                @endif
                            </select>

                            <div class="alert alert-secondary my-3">
                                {{  __tr('While Authentication and Flow templates are supported for sending however you need to create/edit those templates on Meta.') }} <a class="lw-btn btn btn-sm btn-light float-right" target="_blank" href="https://business.facebook.com/wa/manage/message-templates/?waba_id={{ getVendorSettings('whatsapp_business_account_id') }}" > {{ __tr('Manage Templates on Meta') }} <i class="fas fa-external-link-alt"></i></a>
                            </div>                             
                            <!-- /Template Language -->

                            <x-lw.input-field type="selectize" data-lw-plugin="lwSelectize" id="lwSelectCategoryField"
                                data-form-group-class="" data-selected=" " :label="__tr('Category')" name="category">
                                <x-slot name="selectOptions">
                                    <option value="MARKETING">{{ __tr('MARKETING') }}</option>
                                    <option value="UTILITY">{{ __tr('UTILITY') }}</option>
                                    {{-- <option value="AUTHENTICATION">{{ __tr('AUTHENTICATION') }}</option> --}}
                                </x-slot>
                            </x-lw.input-field>
                            
                            <!-- Select Template Type -->
                            <fieldset>
                                <legend>{{ __tr('Choose Template Type') }}</legend>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="template_type" x-model="templateType" @change="changeTemplateType(templateType)" id="lwHeaderRadio" value="header">
                                    <label class="form-check-label" for="lwHeaderRadio">{{ __tr('Header') }}</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="template_type" x-model="templateType" @change="changeTemplateType(templateType)" id="lwCarouselRadio" value="carousel">
                                    <label class="form-check-label" for="lwCarouselRadio">{{ __tr('Carousel') }}</label>
                                </div>
                            </fieldset>
                            <!-- /Select Template Type -->
                            
                            <!-- Carousel Template Start from here -->
                            <div x-show="templateType == 'carousel'">
                                <fieldset>
                                    <legend>{{ __tr('Body') }}</legend>
                                    <small>{{ __tr('Enter the text for your message in the language you\'ve selected.')
                                        }}</small>
                                    <!-- Top Body Text of Carousel Template -->
                                    <div class="form-group">
                                        <label for="lwCarouselTemplateBody">{{ __tr('Body Text') }}</label>
                                        <textarea name="carousel_template_body" id="lwCarouselTemplateBody" class="form-control" x-model="carousel_body_text" rows="10"></textarea>
                                    </div>
                                    <!-- Top Body Text of Carousel Template -->
                                    <!-- Bold Italic etc buttons -->
                                    <div class="form-group text-right">
                                        <button id="lwCarouselBoldBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-bold"></i></button>
                                        <button id="lwCarouselItalicBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-italic"></i></button>
                                        <button id="lwCarouselStrikeThroughBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-strikethrough"></i></button>
                                        <button id="lwCarouselCodeBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-code"></i></button>
                                        <button id="lwCarouselAddPlaceHolder" class="btn btn-dark btn-sm" type="button"> <i
                                                class="fa fa-plus"></i> {{ __tr('Add Variables') }}</button>
                                    </div>
                                    <!-- Bold Italic etc buttons -->
                                    <!-- Dynamic Variables -->
                                    <div>
                                        <template x-if="_.size(carouselBodyTextVariables)">
                                            <div>
                                                <h4>{{ __tr('Samples Text') }}</h4>
                                                <template x-for="(item, index) in carouselBodyTextVariables" :key="index">
                                                    <div class="form-group">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">
                                                                    <span x-text="item.text_variable"></span>
                                                                </span>
                                                            </div>
                                                            <input type="text" class="form-control"
                                                                x-bind:name="'example_body_fields[' + index + ']'"
                                                                required="required" />
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <!-- Dynamic Variables -->
                                </fieldset>
                                
                                <!-- Cards Fieldset start here -->
                                <fieldset>
                                    <legend>{{ __tr('Cards') }}</legend>
                                    <!-- Card 1 Fieldset start here -->
                                    <template x-for="(carouselTemplate, index) in carouselTemplateContainer" :key="index">
                                        <div>
                                            <fieldset>
                                                <legend x-text="index + 1"></legend>

                                                <div x-show="index >= 1">
                                                    <button @click.prevent="deleteCard(index)" class="btn btn-link float-right p-1" type="button"><i class="fa fa-times text-danger"></i></button>
                                                </div>

                                                <!-- Header Type -->
                                                <div class="mb-3">
                                                    <label class="form-label d-block fw-bold">{{ __tr('Header Type') }}</label>

                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" :name="`carousel_templates[${index}][header_type]`" x-bind:id="'lwCardImageTypeRadio'+index" value="image" x-model="carouselTemplateContainer[index]['headerType']" @change="loadPlugin()">
                                                        <label class="form-check-label" x-bind:for="'lwCardImageTypeRadio'+index">{{ __tr('Image') }}</label>
                                                    </div>

                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio":name="`carousel_templates[${index}][header_type]`"  x-bind:id="'lwCardVideoTypeRadio'+index" value="video" x-model="carouselTemplateContainer[index]['headerType']" @change="loadPlugin()">
                                                        <label class="form-check-label" x-bind:for="'lwCardVideoTypeRadio'+index">{{ __tr('Video') }}</label>
                                                    </div>
                                                </div>
                                                <!-- /Header Type -->

                                                <!-- Header Type Options -->
                                                <div class="my-3">
                                                    {{-- image --}}
                                                    <div x-show="carouselTemplateContainer[index]['headerType'] == 'image'" class="form-group col-sm-12">
                                                        <input x-bind:id="'lwCardImageMediaFilepond'+index" type="file" data-allow-revert="true"
                                                            data-label-idle="{{ __tr('Select Image') }}" class="" data-lw-plugin="lwUploader"
                                                            data-instant-upload="true"
                                                            data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>"
                                                            x-bind:data-file-input-element="'#lwCardMediaFileName'+index"
                                                            data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' data-lw-plugin="lwUploader"/>
                                                    </div>
                                                    {{-- /image --}}
                                                    {{-- video --}}
                                                    <div x-show="carouselTemplateContainer[index]['headerType'] == 'video'" class="form-group col-sm-12">
                                                        <input x-bind:id="'lwCardVideoMediaFilepond'+index" type="file" data-allow-revert="true"
                                                            data-label-idle="{{ __tr('Select Video') }}" class=""
                                                            data-instant-upload="true" data-lw-plugin="lwUploader"
                                                            data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>"
                                                            x-bind:data-file-input-element="'#lwCardMediaFileName'+index"
                                                            data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' data-lw-plugin="lwUploader"/>
                                                    </div>
                                                    {{-- /video --}}
                                                    <input x-bind:id="'lwCardMediaFileName'+index" type="hidden" value="" :name="`carousel_templates[${index}][uploaded_media_file_name]`" />
                                                </div>
                                                <!-- /Header Type Options -->
                                                <!-- Card Body for -->
                                                <div class="form-group">
                                                    <label x-bind:for="'lwCarouselCardBody' + index">{{ __tr('Body Text') }}</label>
                                                    <textarea x-bind:name="`carousel_templates[${index}][carousel_card_body]`" :id="'lwCarouselCardBody' + index" class="form-control" x-model="carouselTemplateContainer[index]['bodyText']" rows="10" @input="updatePlaceholders(carouselTemplateContainer[index]['bodyText'], 'lwCarouselCardBody' + index, {index: index, type: 'carouselCard'})" x-ref="lwCardBodyTextarea"></textarea>
                                                </div>
                                                <!-- Card Body for -->
                                                <!-- Bold, Italic etc buttons for -->
                                                <div class="form-group text-right">
                                                    <button x-bind:id="'lwCarouselCardBoldBtn'+index" class="btn btn-light btn-sm" type="button" @click="wrapWithItem('*', 'lwCarouselCardBody'+index, { index: index, type: 'carouselCard' })"> <i
                                                            class="fa fa-bold"></i></button>
                                                    <button x-bind:id="'lwCarouselCardItalicBtn'+index" class="btn btn-light btn-sm" type="button" @click="wrapWithItem('_', 'lwCarouselCardBody'+index, { index: index, type: 'carouselCard' })"> <i
                                                            class="fa fa-italic"></i></button>
                                                    <button x-bind:id="'lwCarouselCardStrikeThroughBtn'+index" class="btn btn-light btn-sm" type="button"> <i
                                                            class="fa fa-strikethrough" @click="wrapWithItem('~', 'lwCarouselCardBody'+index, { index: index, type: 'carouselCard' })"></i></button>
                                                    <button x-bind:id="'lwCarouselCardCodeBtn'+index" class="btn btn-light btn-sm" type="button" @click="wrapWithItem('```', 'lwCarouselCardBody'+index, { index: index, type: 'carouselCard' })"> <i
                                                            class="fa fa-code"></i></button>
                                                    <button x-bind:id="'lwCarouselCardAddPlaceHolder'+index" class="btn btn-dark btn-sm" type="button" @click="addNewVariable('lwCarouselCardBody'+index, {index: index, type: 'carouselCard'})"> <i
                                                            class="fa fa-plus"></i> {{ __tr('Add Variables') }}</button>
                                                </div>
                                                <!-- Bold, Italic etc buttons for -->
                                                <!-- Dynamic variable text boxes -->
                                                <template x-if="_.size(carouselTemplateContainer[index]['bodyTextVariables'])">
                                                    <div>
                                                    <h4>{{ __tr('Samples Text') }}</h4>
                                                    <template x-for="(value, variableIndex) in carouselTemplateContainer[index]['bodyTextVariables']" :key="variableIndex">
                                                        <div class="form-group">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">
                                                                        <span x-text="value.text_variable"></span>
                                                                    </span>
                                                                </div>
                                                                <input type="text" class="form-control"
                                                                    x-bind:name="`carousel_templates[${index}][body_example_fields][${variableIndex}]`"
                                                                    required="required" />
                                                            </div>
                                                        </div>
                                                    </template>
                                                    </div>
                                                </template>
                                                <!-- Dynamic variable text boxes -->
                                                <!-- Card 1 Dynamic Buttons fieldset start here -->
                                                <fieldset>
                                                    <legend>{{ __tr('Buttons') }} <small>{{ __tr('(At least 1 button required, maximum 2)') }}</small></legend>
                                                    <div class="mb-4 ">
                                                        <h3 class="text-muted" x-show="index == 0">{{ __tr('Create buttons that let customers respond to your
                                                            message or take action.')
                                                            }}</h3>

                                                        <h3 class="text-muted" x-show="index >= 1">{{ __tr('All cards contain the same number of buttons arranged in the same sequence.')
                                                            }}</h3>
                                                    </div>
                                                    <div class="lw-buttons-container">
                                                        <div>
                                                            <template x-for="(customButton, buttonIndex) in carouselTemplateContainer[0].cardButtons.data" :key="buttonIndex">
                                                                <div class="card shadow-none mb-2">
                                                                    <h3 class="card-header p-0 pt-2 px-2">
                                                                        <template x-if="customButton.buttonType == 'QUICK_REPLY'">
                                                                            <span>{{ __tr('Quick Reply Button') }}</span>
                                                                        </template>
                                                                        <template x-if="customButton.buttonType == 'PHONE_NUMBER'">
                                                                            <span>{{ __tr('Phone Number Button') }}</span>
                                                                        </template>
                                                                        <template x-if="customButton.buttonType == 'URL'">
                                                                            <span>{{ __tr('URL Button') }}</span>
                                                                        </template>
                                                                        {{-- delete button --}}
                                                                        <div x-show="index == 0">
                                                                            <button
                                                                            @click.prevent="deleteCardButtonOption(index, buttonIndex, customButton.buttonType)"
                                                                            class="btn btn-link float-right p-1" type="button"><i class="fa fa-times text-danger"></i></button>
                                                                        </div>
                                                                    </h3>
                                                                    <div class="card-body">
                                                                        <input type="hidden"
                                                                            x-bind:name="`carousel_templates[${index}][message_buttons][${buttonIndex}][type]`"
                                                                            x-bind:value="customButton.buttonType">
                                                                        <template
                                                                            x-if="_.includes(['QUICK_REPLY', 'URL'], customButton.buttonType)">
                                                                            <x-lw.input-field x-bind:id="customButton.buttonIndex"
                                                                                type="text" data-form-group-class="mt-4"
                                                                                :label="__tr('Button Text')"
                                                                                x-model="carouselTemplateContainer[index]['cardButtons']['buttonModels'][buttonIndex]"
                                                                                x-bind:name="`carousel_templates[${index}][message_buttons][${buttonIndex}][text]`">
                                                                                <x-slot name="prepend">
                                                                                    <span class="input-group-text"><i class="fa fa-font"></i></span>
                                                                                </x-slot>
                                                                            </x-lw.input-field>
                                                                        </template>
                                                                        <template x-if="customButton.buttonType == 'PHONE_NUMBER'">
                                                                            <div>
                                                                                <x-lw.input-field x-bind:id="customButton.buttonIndex"
                                                                                    type="text" data-form-group-class="mt-4"
                                                                                    :label="__tr('Button Text')"
                                                                                    x-model="carouselTemplateContainer[index]['cardButtons']['buttonModels'][buttonIndex]"
                                                                                    x-bind:name="`carousel_templates[${index}][message_buttons][${buttonIndex}][text]`">
                                                                                    <x-slot name="prepend">
                                                                                        <span class="input-group-text"><i class="fa fa-font"></i></span>
                                                                                    </x-slot>
                                                                                </x-lw.input-field>

                                                                                <x-lw.input-field x-bind:id="customButton.buttonIndex"
                                                                                    type="number" data-form-group-class=""
                                                                                    :label="__tr('Phone Number')"
                                                                                    x-bind:name="`carousel_templates[${index}][message_buttons][${buttonIndex}][phone_number]`">
                                                                                    <x-slot name="prepend">
                                                                                        <span class="input-group-text"><i
                                                                                                class="fa fa-phone-alt"></i></span>
                                                                                    </x-slot>
                                                                                </x-lw.input-field>
                                                                            </div>
                                                                        </template>
                                                                        <template x-if="customButton.buttonType == 'URL'">
                                                                            <div>
                                                                                <x-lw.input-field x-bind:id="customButton.buttonIndex"
                                                                                    type="url" data-form-group-class="mt-4"
                                                                                    :label="__tr('Website URL')"
                                                                                    x-bind:name="`carousel_templates[${index}][message_buttons][${buttonIndex}][url]`">
                                                                                    <x-slot name="prepend">
                                                                                        <span class="input-group-text"><i class="fa fa-link"></i></span>
                                                                                    </x-slot>
                                                                                    <x-slot name="append">
                                                                                        <span class="input-group-text">@{{1}}</span>
                                                                                    </x-slot>
                                                                                </x-lw.input-field>

                                                                                <x-lw.input-field x-bind:id="customButton.buttonIndex"
                                                                                    type="text" data-form-group-class="mt-4"
                                                                                    :label="__tr('Example')"
                                                                                    x-bind:name="`carousel_templates[${index}][message_buttons][${buttonIndex}][example]`">
                                                                                    <x-slot name="append">
                                                                                        <span class="input-group-text">@{{1}}</span>
                                                                                    </x-slot>
                                                                                </x-lw.input-field>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                            <input type="hidden"
                                                                x-bind:name="`carousel_templates[${index}][message_buttons_count]`"
                                                                x-bind:value="carouselTemplateContainer[0].cardButtons.data.length == 0 ? null : carouselTemplateContainer[0].cardButtons.data.length">
                                                        </div>
                                                        <div class="mt-4" x-show="index == 0">
                                                            <button
                                                                :disabled="(carouselTemplateContainer[index]['cardButtons']['totalAllowedButtons'] == carouselTemplateContainer[index]['cardButtons']['totalButtonsUsed']) || (carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes']['QUICK_REPLY'] == carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes']['QUICK_REPLY_LIMIT'])"
                                                                class="btn btn-dark btn-sm" type="button"
                                                                @click.prevent="addCardButtonOptions(index, 'QUICK_REPLY')"><i
                                                                    class="fa fa-reply"></i> {{ __tr('Quick Reply Button') }}</button>
                                                            <button
                                                                :disabled="(carouselTemplateContainer[index]['cardButtons']['totalAllowedButtons'] == carouselTemplateContainer[index]['cardButtons']['totalButtonsUsed']) || (carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes']['PHONE_NUMBER'] == carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes']['PHONE_NUMBER_LIMIT'])"
                                                                class="btn btn-dark btn-sm" type="button"
                                                                @click.prevent="addCardButtonOptions(index, 'PHONE_NUMBER')"><i
                                                                    class="fa fa-phone-alt"></i> {{ __tr('Phone Number Button') }}</button>                                                
                                                            <button
                                                                :disabled="(carouselTemplateContainer[index]['cardButtons']['totalAllowedButtons'] == carouselTemplateContainer[index]['cardButtons']['totalButtonsUsed']) || (carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes']['URL'] == carouselTemplateContainer[index]['cardButtons']['buttonUsesByTypes']['URL_LIMIT'])"
                                                                class="btn btn-dark btn-sm" type="button"
                                                                @click.prevent="addCardButtonOptions(index, 'URL')"> <i
                                                                    class="fa fa-link"></i> {{ __tr('URL Button') }}</button>
                                                            <template
                                                                x-if="carouselTemplateContainer[index]['cardButtons']['totalAllowedButtons'] == carouselTemplateContainer[index]['cardButtons']['totalButtonsUsed']">
                                                                <div class="alert alert-danger mt-4">
                                                                    {{ __tr('You have reached maximum buttons allowed by Meta for carousel template') }}
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </fieldset>
                                                <!-- Card 1 Dynamic Buttons fieldset end here -->
                                            </fieldset>
                                        </div>
                                    </template>
                                    <!-- Card 1 Fieldset end here -->
                                    <div class="form-group text-right" x-show="totalAllowCards != totalUsedCards">
                                        <button id="lwAddNewCardButton" class="btn btn-dark btn-sm" type="button" @click="addNewCard()"><i class="fa fa-plus"></i> {{ __tr('Add Card') }}</button>
                                    </div>
                                    <template x-if="totalAllowCards == totalUsedCards">
                                        <div class="alert alert-danger mt-4">
                                            {{ __tr('You have reached maximum cards allowed by Meta for carousel template') }}
                                        </div>
                                    </template>
                                </fieldset>
                                <!-- Cards Fieldset end here -->
                            </div>
                            <!-- Carousel Template end from here -->
                            <!-- Header Template Start from here -->
                            <div x-show="templateType == 'header'">
                                <fieldset>
                                    <input id="lwMediaFileName" type="hidden" value="" name="uploaded_media_file_name" />
                                    <legend>{{ __tr('Header') }} <small>{{ __tr('(Optional)') }}</small></legend>
                                    <x-lw.input-field x-model="headerType" type="selectize" id="lwMediaHeaderType"
                                        data-form-group-class="" data-selected=" " :label="__tr('Header Type')"
                                        name="media_header_type">
                                        <x-slot name="selectOptions">
                                            <option value="0">{{ __tr('None') }}</option>
                                            <optgroup label="{{ __tr('Text') }}">
                                                <option value="text">{{ __tr('Text') }}</option>
                                            </optgroup>
                                            <optgroup label="{{ __tr('Media') }}">
                                                <option value="image">{{ __tr('Image') }}</option>
                                                <option value="video">{{ __tr('Video') }}</option>
                                                <option value="document">{{ __tr('Document') }}</option>
                                                <option value="location">{{ __tr('Location') }}</option>
                                            </optgroup>
                                        </x-slot>
                                    </x-lw.input-field>
                                    <div class="my-3">
                                        {{-- text --}}
                                        <div x-show="headerType == 'text'" class="form-group col-sm-12">
                                            <x-lw.input-field type="text" id="lwHeaderTextBody" data-form-group-class=""
                                                :label="__tr('Header Text')" x-model="header_text_body" name="header_text_body" />
                                            <div class="form-group text-right">
                                                <button :disabled="enableHeaderVariableExample" id="lwAddSinglePlaceHolder" class="btn btn-dark btn-sm" type="button">
                                                    <i class="fa fa-plus"></i> {{ __tr('Add Variable') }}</button>
                                            </div>
                                            <template x-if="enableHeaderVariableExample">
                                                <x-lw.input-field type="text" id="lwHeaderTextBodyExample"
                                                    data-form-group-class="" :label="__tr('Header Text Variable Example')"
                                                    name="example_header_fields" />
                                            </template>
                                        </div>
                                        {{-- document --}}
                                        <div x-show="headerType == 'document'" class="form-group col-sm-12">
                                            <input id="lwDocumentMediaFilepond" type="file" data-allow-revert="true"
                                                data-label-idle="{{ __tr('Select Document') }}" class="lw-file-uploader"
                                                data-instant-upload="true"
                                                data-action="<?= route('media.upload_temp_media', 'whatsapp_document') ?>"
                                                id="lwDocumentField" data-file-input-element="#lwMediaFileName"
                                                data-allowed-media='<?= getMediaRestriction('whatsapp_document') ?>' />
                                        </div>
                                        {{-- image --}}
                                        <div x-show="headerType == 'image'" class="form-group col-sm-12">
                                            <input id="lwImageMediaFilepond" type="file" data-allow-revert="true"
                                                data-label-idle="{{ __tr('Select Image') }}" class="lw-file-uploader"
                                                data-instant-upload="true"
                                                data-action="<?= route('media.upload_temp_media', 'whatsapp_image') ?>"
                                                id="lwImageField" data-file-input-element="#lwMediaFileName"
                                                data-allowed-media='<?= getMediaRestriction('whatsapp_image') ?>' />
                                        </div>
                                        {{-- video --}}
                                        <div x-show="headerType == 'video'" class="form-group col-sm-12">
                                            <input id="lwVideoMediaFilepond" type="file" data-allow-revert="true"
                                                data-label-idle="{{ __tr('Select Video') }}" class="lw-file-uploader"
                                                data-instant-upload="true"
                                                data-action="<?= route('media.upload_temp_media', 'whatsapp_video') ?>"
                                                id="lwVideoField" data-file-input-element="#lwMediaFileName"
                                                data-allowed-media='<?= getMediaRestriction('whatsapp_video') ?>' />
                                        </div>
                                    </div>
                                </fieldset>
                                <fieldset>
                                    <legend>{{ __tr('Body') }}</legend>
                                    <small>{{ __tr('Enter the text for your message in the language you\'ve selected.')
                                        }}</small>
                                    <div class="form-group">
                                        <label for="lwTemplateBody">{{ __tr('Body Text') }}</label>
                                        <textarea name="template_body" id="lwTemplateBody" class="form-control" x-model="text_body" rows="10"></textarea>
                                    </div>
                                    <div class="form-group text-right">
                                        <button id="lwBoldBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-bold"></i></button>
                                        <button id="lwItalicBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-italic"></i></button>
                                        <button id="lwStrikeThroughBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-strikethrough"></i></button>
                                        <button id="lwCodeBtn" class="btn btn-light btn-sm" type="button"> <i
                                                class="fa fa-code"></i></button>
                                        <button id="lwAddPlaceHolder" class="btn btn-dark btn-sm" type="button"> <i
                                                class="fa fa-plus"></i> {{ __tr('Add Variables') }}</button>
                                    </div>
                                    <div>
                                        <template x-if="_.size(newBodyTextInputFields)">
                                            <div>
                                                <h4>{{ __tr('Samples Text') }}</h4>
                                                <template x-for="(item, index) in newBodyTextInputFields" :key="index">
                                                    <div class="form-group">
                                                        <div class="input-group">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">
                                                                    <span x-text="item.text_variable"></span>
                                                                </span>
                                                            </div>
                                                            <input type="text" class="form-control"
                                                                x-bind:name="'example_body_fields[' + index + ']'"
                                                                required="required" />
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </fieldset>
                                <x-lw.input-field type="text" id="lwTemplateFooter" data-form-group-class=""
                                    :label="__tr('Footer (Optional)')" name="template_footer" x-model="footer_text_body" 
                                    :helpText="__tr('Add a short line of text to the bottom of your message template.')" />
                                <fieldset>
                                    <legend>{{ __tr('Buttons') }} <small>{{ __tr('(Optional)') }}</small></legend>
                                    <div class="mb-4 ">
                                        <h3 class="text-muted">{{ __tr('Create buttons that let customers respond to your
                                            message or take action.')
                                            }}</h3>
                                    </div>
                                    <div class="lw-buttons-container">
                                        <div>
                                            <template x-for="customButtonData in customButtons.data">
                                                <div class="card shadow-none mb-2">
                                                    <h3 class="card-header p-0 pt-2 px-2">
                                                        <template x-if="customButtonData.buttonType == 'QUICK_REPLY'">
                                                            <span>{{ __tr('Quick Reply Button') }}</span>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'PHONE_NUMBER'">
                                                            <span>{{ __tr('Phone Number Button') }}</span>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'URL_BUTTON'">
                                                            <span>{{ __tr('URL Button') }}</span>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'DYNAMIC_URL_BUTTON'">
                                                            <span>{{ __tr('Dynamic URL Button') }}</span>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'VOICE_CALL'">
                                                            <span>{{ __tr('WhatsApp Call Button') }}</span>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'COPY_CODE'">
                                                            <span>{{ __tr('Coupon Code Copy Button') }}</span>
                                                        </template>
                                                        {{-- delete button --}}
                                                        <button
                                                            @click.prevent="deleteWhatsAppButtonOption(customButtonData.buttonIndex)"
                                                            class="btn btn-link float-right p-1" type="button"><i class="fa fa-times text-danger"></i></button>
                                                    </h3>
                                                    <div class="card-body">
                                                        <input type="hidden"
                                                            x-bind:name="'message_buttons['+customButtonData.buttonIndex+'][type]'"
                                                            x-bind:value="customButtonData.buttonType">
                                                        <template
                                                            x-if="_.includes(['QUICK_REPLY','PHONE_NUMBER', 'URL_BUTTON', 'VOICE_CALL','DYNAMIC_URL_BUTTON'], customButtonData.buttonType)">
                                                            <x-lw.input-field x-bind:id="customButtonData.buttonIndex"
                                                                type="text" data-form-group-class="mt-4"
                                                                :label="__tr('Button Text')"
                                                                x-model="buttonModels[customButtonData.buttonIndex]"
                                                                x-bind:name="'message_buttons['+customButtonData.buttonIndex+'][text]'">
                                                                <x-slot name="prepend">
                                                                    <span class="input-group-text"><i
                                                                            class="fa fa-font"></i></span>
                                                                </x-slot>
                                                            </x-lw.input-field>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'PHONE_NUMBER'">
                                                            <x-lw.input-field x-bind:id="customButtonData.buttonIndex"
                                                                type="number" data-form-group-class=""
                                                                :label="__tr('Phone Number')"
                                                                x-bind:name="'message_buttons['+customButtonData.buttonIndex+'][phone_number]'">
                                                                <x-slot name="prepend">
                                                                    <span class="input-group-text"><i
                                                                            class="fa fa-phone-alt"></i></span>
                                                                </x-slot>
                                                            </x-lw.input-field>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'URL_BUTTON'">
                                                            <x-lw.input-field x-bind:id="customButtonData.buttonIndex"
                                                                type="url" data-form-group-class="mt-4"
                                                                :label="__tr('Website URL')"
                                                                x-bind:name="'message_buttons['+customButtonData.buttonIndex+'][url]'">
                                                                <x-slot name="prepend">
                                                                    <span class="input-group-text"><i
                                                                            class="fa fa-link"></i></span>
                                                                </x-slot>
                                                            </x-lw.input-field>
                                                        </template>
                                                        <template x-if="customButtonData.buttonType == 'DYNAMIC_URL_BUTTON'">
                                                            <x-lw.input-field x-bind:id="customButtonData.buttonIndex"
                                                                type="url" data-form-group-class="mt-4"
                                                                :label="__tr('Website URL')"
                                                                x-bind:name="'message_buttons['+customButtonData.buttonIndex+'][url]'">
                                                                <x-slot name="prepend">
                                                                    <span class="input-group-text"><i
                                                                            class="fa fa-link"></i></span>
                                                                </x-slot>
                                                                <x-slot name="append">
                                                                    <span class="input-group-text">@{{1}}</span>
                                                                </x-slot>
                                                            </x-lw.input-field>
                                                        </template>
                                                        <template
                                                            x-if="_.includes(['COPY_CODE', 'DYNAMIC_URL_BUTTON'],customButtonData.buttonType)">
                                                            <x-lw.input-field x-bind:id="customButtonData.buttonIndex"
                                                                type="text" data-form-group-class="mt-4"
                                                                :label="__tr('Example')"
                                                                x-bind:name="'message_buttons['+customButtonData.buttonIndex+'][example]'" />
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="mt-4">
                                            <button
                                                :disabled="customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons"
                                                class="btn btn-dark btn-sm" type="button"
                                                @click.prevent="addWhatsAppButtonOption('QUICK_REPLY')"><i
                                                    class="fa fa-reply"></i> {{ __tr('Quick Reply Button') }}</button>
                                            <button
                                                :disabled="(customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons) || (customButtons.buttonUsesByTypes.PHONE_NUMBER >= customButtons.buttonUsesByTypes.PHONE_NUMBER_LIMIT)"
                                                class="btn btn-dark btn-sm" type="button"
                                                @click.prevent="addWhatsAppButtonOption('PHONE_NUMBER')"><i
                                                    class="fa fa-phone-alt"></i> {{ __tr('Phone Number Button') }}</button>
                                        {{--   <button
                                                :disabled="(customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons) || (customButtons.buttonUsesByTypes.VOICE_CALL >= customButtons.buttonUsesByTypes.VOICE_CALL_LIMIT)"
                                                class="btn btn-dark btn-sm" type="button"
                                                @click.prevent="addWhatsAppButtonOption('VOICE_CALL')"><i
                                                    class="fab fa-whatsapp"></i> {{ __tr('WhatsApp Call Button') }}</button> --}}
                                            <button
                                                :disabled="(customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons) || (customButtons.buttonUsesByTypes.COPY_CODE >= customButtons.buttonUsesByTypes.COPY_CODE_LIMIT)"
                                                class="btn btn-dark btn-sm" type="button"
                                                @click.prevent="addWhatsAppButtonOption('COPY_CODE')"><i
                                                    class="fa fa-clipboard"></i> {{ __tr('Copy Code Button') }}</button>
                                            <button
                                                :disabled="(customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons) || (customButtons.buttonUsesByTypes.URL_BUTTON >= customButtons.buttonUsesByTypes.URL_BUTTON_LIMIT)"
                                                class="btn btn-dark btn-sm" type="button"
                                                @click.prevent="addWhatsAppButtonOption('URL_BUTTON')"> <i
                                                    class="fa fa-link"></i> {{ __tr('URL Button') }}</button>
                                            <button
                                                :disabled="(customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons) || (customButtons.buttonUsesByTypes.URL_BUTTON >= customButtons.buttonUsesByTypes.URL_BUTTON_LIMIT)"
                                                class="btn btn-dark btn-sm" type="button"
                                                @click.prevent="addWhatsAppButtonOption('DYNAMIC_URL_BUTTON')"> <i
                                                    class="fa fa-link"></i> {{ __tr('Dynamic URL Button') }}</button>
                                            <template
                                                x-if="customButtons.totalButtonsUsed >= customButtons.totalAllowedButtons">
                                                <div class="alert alert-danger mt-4">
                                                    {{ __tr('You have reached maximum buttons allowed by Meta for template') }}
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">{{ __tr('Submit') }}</button>
                            </div>
                        </x-lw.form>
                    </div>
                    <div class="col-md-1"></div>
                    <div class="col-md-4">
                        <div class="lw-whatsapp-template-create-preview">
                            <h3>{{  __tr('Template Preview') }}</h3>
                            <div class="lw-whatsapp-preview-container" x-show="templateType == 'header'">
                                <img class="lw-whatsapp-preview-bg" src="{{ asset('imgs/wa-message-bg.png') }}" alt="">
                                <div class="lw-whatsapp-preview">
                                    <div class="card ">
                                        <div x-show="headerType && (headerType != 'text')" class="lw-whatsapp-header-placeholder">
                                            <i x-show="headerType == 'video'" class="fa fa-5x fa-play-circle text-white"></i>
                                            <i x-show="headerType == 'image'" class="fa fa-5x fa-image text-white"></i>
                                            <i x-show="headerType == 'location'" class="fa fa-5x fa-map-marker-alt text-white"></i>
                                            <i x-show="headerType == 'document'" class="fa fa-5x fa-file-alt text-white"></i>
                                        </div>
                                        <div x-show="headerType == 'location'" class="lw-whatsapp-location-meta bg-secondary p-2">
                                            <small>@{{location_name}}</small><br>
                                            <small>@{{address}}</small>
                                        </div>
                                        <div x-show="headerType == 'text'" class="lw-whatsapp-body mb--3">
                                            <strong x-text="header_text_body"></strong>
                                            </div>
                                        <div class="lw-whatsapp-body lw-ws-pre-line" x-html="appFuncs.formatWhatsAppText(text_body)"></div>
                                        <div class="lw-whatsapp-footer text-muted" x-text="footer_text_body"></div>
                                        <div class="card-footer lw-whatsapp-buttons">
                                            <div class="list-group list-group-flush lw-whatsapp-buttons">
                                                    <template x-for="(customButtonData, index) in customButtons.data" :key="index">
                                                        <div>
                                                            <div class="list-group-item">
                                                                <template x-if="customButtonData.buttonType == 'QUICK_REPLY'">
                                                                    <i class="fa fa-reply"></i>
                                                                </template>
                                                                <template x-if="customButtonData.buttonType == 'PHONE_NUMBER'">
                                                                    <i class="fa fa-phone-alt"></i>
                                                                </template>
                                                                <template x-if="customButtonData.buttonType == 'URL_BUTTON'">
                                                                    <i class="fas fa-external-link-square-alt"></i>
                                                                </template>
                                                                <template x-if="customButtonData.buttonType == 'DYNAMIC_URL_BUTTON'">
                                                                    <i class="fas fa-external-link-square-alt"></i>
                                                                </template>
                                                                <template x-if="customButtonData.buttonType == 'VOICE_CALL'">
                                                                    <i class="fab fa-whatsapp"></i><i class="fa fa-phone-alt"></i>
                                                                </template>
                                                                <template x-if="customButtonData.buttonType == 'COPY_CODE'">
                                                                    <span><i class="fa fa-copy"></i> {{  __tr('Copy Code') }}</span>
                                                                </template>
                                                                <span x-text="buttonModels[customButtonData.buttonIndex]"></span>
                                                            </div>
                                                            <template x-if="index == 3">
                                                                <div class="list-group-item"><i class="fa fa-menu"></i> {{ __tr('See all options') }} <br><small class="text-orange">{{  __tr('More than 3 buttons will be shown in the list by clicking') }}</small></div>
                                                            </template>
                                                        </div>
                                                    </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lw-whatsapp-preview-container" x-show="templateType == 'carousel'">
                                <img class="lw-whatsapp-preview-bg" src="{{ asset('imgs/wa-message-bg.png') }}" alt="">
                                <div class="lw-whatsapp-preview">
                                    <div class="card">
                                        <div class="lw-whatsapp-body lw-ws-pre-line" x-html="appFuncs.formatWhatsAppText(carousel_body_text)"></div>
                                    </div>
                                    <div class="lw-carousel-wrapper">
                                        <button class="lw-carousel-arrow prev" onclick="scrollSlide(this, false)">‹</button>
                                        <div class="lw-carousel-container">
                                            <template x-for="(carouselTemplate, index) in carouselTemplateContainer" :key="index">
                                                <div class="lw-carousel-card">
                                                    <div class="lw-card-media">
                                                        <i x-show="carouselTemplate.headerType == 'image'" class="fa fa-5x fa-image text-white"></i>
                                                        <i x-show="carouselTemplate.headerType == 'video'" class="fa fa-5x fa-play-circle text-white"></i>
                                                    </div>
                                                    <div class="lw-carousel-card-body" x-show="carouselTemplate.bodyText != ''">
                                                        <div class="lw-card-desc lw-ws-pre-line" x-html="appFuncs.formatWhatsAppText(carouselTemplate.bodyText)"></div>
                                                    </div>
                                                    <div class="card-footer lw-whatsapp-buttons">
                                                        <div class="list-group list-group-flush lw-whatsapp-buttons">
                                                            <template x-for="(templateButton, buttonIndex) in carouselTemplate.cardButtons.data" :key="buttonIndex">
                                                                <div>
                                                                    <div class="list-group-item">
                                                                        <template x-if="templateButton.buttonType == 'QUICK_REPLY'">
                                                                            <i class="fa fa-reply"></i>
                                                                        </template>
                                                                        <template x-if="templateButton.buttonType == 'PHONE_NUMBER'">
                                                                            <i class="fa fa-phone-alt"></i>
                                                                        </template>
                                                                        <template x-if="templateButton.buttonType == 'URL'">
                                                                            <i class="fas fa-external-link-square-alt"></i>
                                                                        </template>
                                                                        <span x-text="carouselTemplateContainer[index]['cardButtons']['buttonModels'][buttonIndex]"></span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </template>
                                        </div>
                                        <button class="lw-carousel-arrow next" onclick="scrollSlide(this, true)">›</button>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()
@push('appScripts')
<?= __yesset([
            'dist/js/whatsapp-template.js',
        ],true,
) ?>

<script>
    (function($) {
    'use strict';
        $('#lwSelectLanguage').selectize({
            create: true,
            valueField: 'currency_code',
            labelField: 'code',
            searchField: ['currency_code', 'code']
        });
    })(jQuery);
</script>
@endpush