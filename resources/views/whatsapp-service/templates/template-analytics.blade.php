@extends('layouts.app', ['title' => __tr('Template Analytics')])
@section('content')
@include('users.partials.header', [
'title' => __tr('Template Analytics'),
'description' => '',
'class' => 'col-lg-7'
])
<div class="container-fluid mt-lg--6">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="float-right">
                <a class="lw-btn btn btn-secondary" href="{{ route('vendor.whatsapp_service.templates.read.list_view') }}">{{
                    __tr('Back to Templates') }}</a>
                    <a href="https://developers.facebook.com/documentation/business-messaging/whatsapp/analytics/#template-analytics" target="_blank" class="btn btn-default">{{  __tr('Help') }}</a>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card" x-data="{ 
            messageCountData: [],
            isDataLoaded: false,
            analyticStartDate: '',
            analyticEndDate: '',
            analyticProductType: 'CLOUD_API',
            templateId: '',
            analyticsData: [],
            presetDuration: [],
            analyticDurationPreset: '',
            cursorAfter: '',
            loadMoreContent: false,
            defaultPreset: null,
            durationMessage: '',
            loadMoreAnalytics: function(cursorAfter) {
                var self = this;
                this.loadMoreContent = true;
                __DataRequest.post('{{ route('vendor.whatsapp_service.templates.write.analytics') }}', {
                    'analytics_start_date': this.analyticStartDate,
                    'analytics_end_date': this.analyticEndDate,
                    'analytics_product_type': this.analyticProductType,
                    'template_id': this.templateId,
                    'cursor_after': cursorAfter,
                    'total_send_count': this.messageCountData.totalSentCount,
                    'total_delivered_count': this.messageCountData.totalDeliveredCount,
                    'total_read_count': this.messageCountData.totalReadCount,
                    'total_replied_count': this.messageCountData.totalRepliedCount,
                }, function(responseData) {
                    self.loadMoreContent = false;
                });
            },
            changeDurationPreset: function(id) {
                const preset = this.presetDuration.find(p => p.id == id);
                this.analyticStartDate = preset.start;
                this.analyticEndDate = preset.end;
            }
        }">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <fieldset class="mb-4">
                            <legend>{{  __tr('Template Info') }}</legend>
                            <dl>
                                <div class="row">
                                    <div class="col-3">
                                        <dt>{{  __tr('Name') }}</dt>
                                        <dd>{{ $analyticData['whatsAppTemplateData']['name'] }}</dd>
                                    </div>

                                    <div class="col-3">
                                        <dt>{{  __tr('Language') }}</dt>
                                        <dd>{{ $analyticData['whatsAppTemplateData']['language'] }}</dd>
                                    </div>

                                    <div class="col-3">
                                        <dt>{{  __tr('Category') }}</dt>
                                        <dd>{{ $analyticData['whatsAppTemplateData']['category'] }}</dd>
                                    </div>

                                    <div class="col-3">
                                        <dt>{{  __tr('Status') }}</dt>
                                        <dd>
                                            @if ($analyticData['whatsAppTemplateData']['status'] == 'APPROVED')
                                            <i class="fa fa-check-circle fa-1x text-success"></i>
                                            @elseif ($analyticData['whatsAppTemplateData']['status'] == 'REJECTED')
                                            <i class="fa fa-times-circle fa-1x text-danger"></i>
                                            @elseif ($analyticData['whatsAppTemplateData']['status'] == 'PENDING')
                                            <i class="fa fa-clock fa-1x text-warning"></i>
                                            @endif
                                            {{ $analyticData['whatsAppTemplateData']['status'] }}
                                        </dd>
                                    </div>
                                </div>
                            </dl>
                            <hr>
                            <div class="row">
                                <div class="col-12 mb-4" x-show="durationMessage">
                                    <span x-html="durationMessage"></span>
                                </div>                                
                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <div class="card text-center shadow-sm">
                                        <div class="card-body">
                                            <div class="mb-2 text-primary">
                                                <i class="fas fa-paper-plane fa-2x"></i>
                                            </div>
                                            <h4 class="text-muted mb-1">{{ __tr('Total Sent') }}</h4>
                                            <h3 class="mb-0" x-text="__Utils.formatAsLocaleNumber(messageCountData.totalSentCount)"></h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <div class="card text-center shadow-sm">
                                        <div class="card-body">
                                            <div class="mb-2 text-success">
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                            <h4 class="text-muted mb-1">{{ __tr('Total Delivered') }}</h4>
                                            <h3 class="mb-0" x-text="__Utils.formatAsLocaleNumber(messageCountData.totalDeliveredCount)"></h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <div class="card text-center shadow-sm">
                                        <div class="card-body">
                                            <div class="mb-2 text-success">
                                                <i class="fas fa-check-double fa-2x"></i>
                                            </div>
                                            <h4 class="text-muted mb-1">{{ __tr('Total Read') }}</h4>
                                            <h3 class="mb-0">
                                                <span x-text="__Utils.formatAsLocaleNumber(messageCountData.totalReadCount)"></span>
                                                <template x-if="messageCountData.totalDeliveredCount > 0">
                                                    <span x-text="messageCountData.totalReadPercentage"></span>
                                                </template>
                                            </h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6 col-12 mb-3">
                                    <div class="card text-center shadow-sm">
                                        <div class="card-body">
                                            <div class="mb-2 text-success">
                                                <i class="fas fa-reply fa-2x"></i>
                                            </div>
                                            <h4 class="text-muted mb-1">{{ __tr('Total Replied') }}</h4>
                                            <h3 class="mb-0" x-text="__Utils.formatAsLocaleNumber(messageCountData.totalRepliedCount)"></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>                        
                        <x-lw.form id="lwTemplateAnalyticsForm" :action="route('vendor.whatsapp_service.templates.write.analytics')">
                            <div x-cloak>
                                <input type="hidden" name="template_id" x-bind:value="templateId">
                                <div class="mb-3 row">
                                    <div class="col-sm-2">
                                        <label for="lwAnalyticsProductType">{{ __tr('Duration Preset') }}</label>
                                        <select id="lwAnalyticsProductType" class="form-control custom-select" placeholder="<?= __tr('Duration Preset') ?>" x-model="analyticDurationPreset" name="analytics_duration_preset" @change="changeDurationPreset(analyticDurationPreset)">
                                            @foreach($analyticData['presetDuration'] as $presetDuration)
                                                <option value="{{ $presetDuration['id'] }}"><?= $presetDuration['name'] ?></option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-3">
                                        <label for="lwStartDateField">{{ __tr('Start Date') }}</label>
                                        <input type="date" name="analytics_start_date" class="form-control" x-model="analyticStartDate" id="lwStartDateField" value="" max="<?= date('Y-m-d') ?>" min="{{ now()->subDays(89)->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-sm-3">
                                        <label for="lwEndDateField">{{ __tr('End Date') }}</label>
                                        <input type="date" name="analytics_end_date" class="form-control" x-model="analyticEndDate" id="lwEndDateField" value="" max="<?= date('Y-m-d') ?>" min="{{ now()->subDays(89)->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-sm-2">
                                        <label for="lwAnalyticsProductType">{{ __tr('Product Type') }}</label>
                                        <select id="lwAnalyticsProductType" class="form-control" placeholder="<?= __tr('Product Type') ?>" x-model="analyticProductType" name="analytics_product_type">
                                            <option value="CLOUD_API"><?= __tr('CLOUD API') ?></option>
                                            <option value="MARKETING_MESSAGES_LITE_API"><?= __tr('MARKETING MESSAGES LITE API') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="submit" class="btn btn-primary lw-analytics-update-btn">{{ __tr('Update') }}</button>
                                    </div>
                                </div>

                                <div class="lw-analytic-datatable-fixed-height">
                                    <div id="lwTemplateAnalytics_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                                        <div class="row"></div>
                                        <div class="row dt-row mb--2">
                                            <div class="col-sm-12">
                                                <table class="table table-striped dataTable no-footer dtr-inline" id="lwTemplateAnalytics" aria-describedby="lwTemplateAnalytics_info" x-show="isDataLoaded">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" class="sorting_disabled"><strong>{{ __tr('Date') }}</strong></th>
                                                            <th aria-controls="lwTemplateAnalytics" scope="col"><strong>{{ __tr('Sent') }}</strong></th>
                                                            <th aria-controls="lwTemplateAnalytics" scope="col"><strong>{{ __tr('Delivered') }}</strong></th>
                                                            <th aria-controls="lwTemplateAnalytics" scope="col"><strong>{{ __tr('Read') }}</strong></th>
                                                            <th aria-controls="lwTemplateAnalytics" scope="col"><strong>{{ __tr('Replied') }}</strong></th>
                                                            <th aria-controls="lwTemplateAnalytics" scope="col"><strong>{{ __tr('Clicked') }}</strong></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <template x-for="item in analyticsData" :key="index">
                                                        <tr>
                                                            <th scope="row">
                                                                <span x-text="item.startDate"></span> - <span x-text="item.endDate"></span>
                                                            </th>
                                                            <td class="text-end" x-text="__Utils.formatAsLocaleNumber(item.sent)"></td>
                                                            <td class="text-end" x-text="__Utils.formatAsLocaleNumber(item.delivered)"></td>
                                                            <td class="text-end">
                                                                <span x-text="item.read"></span>
                                                                <template x-if="item.delivered > 0">
                                                                    <span x-text="item.readPercentage"></span>
                                                                </template>
                                                            </td>
                                                            <td class="text-end" x-text="__Utils.formatAsLocaleNumber(item.replied)"></td>
                                                            <td>
                                                                <template x-for="(clickItem, key) in item.clicked" :key="key">
                                                                    <div>
                                                                        <span x-text="clickItem.button_content"></span>: 
                                                                        <span x-text="__Utils.formatAsLocaleNumber(clickItem.count)"></span>
                                                                    </div>
                                                                </template>
                                                            </td>
                                                        </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                                <a href x-show="cursorAfter" class="lw-btn btn btn-sm btn-block btn-secondary" :class="loadMoreContent ? 'disabled' : ''" @click.prevent="loadMoreAnalytics(cursorAfter)">{{ __tr('Load More') }}</a>
                                            </div>  
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-lw.form>
                    </div>
                    <div class="col-md-4">
                        <div class="lw-whatsapp-template-create-preview">
                            <h3>{{  __tr('Template Preview') }}</h3>
                            {!! $analyticData['templatePreview'] !!}
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
@endpush