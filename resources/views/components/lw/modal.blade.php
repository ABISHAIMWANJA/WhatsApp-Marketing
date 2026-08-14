@props([
    'header' => '',
    'hasForm' => false,
    'modalSize' => 'modal-lg',
])
@php
$modalDialogClass = $attributes->get('modal-dialog-class') ?? '';
@endphp
<!-- Modal -->
<div x-data="{isModalExpanded:false, modalTitle: '{!! $header !!}'}" @set-modal-title.window="modalTitle = $event.detail" data-backdrop="static" tabindex="-1" aria-labelledby="{!! $header !!}" aria-hidden="true"
    {{ $attributes->merge(['class' => 'modal ' . ($hasForm ? 'lw-has-form' : '')]) }}>
    <div class="modal-dialog {{ $modalSize }} {{ $modalDialogClass }}" :style="isModalExpanded ? 'max-width: 90vw;height: 90vh;' : ''">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" x-text="modalTitle"></h3>
                <span class="d-none d-md-inline-block">
                    <div x-cloak class="btn-group" role="group" aria-label="{{ __tr('Modal Actions') }}">
                        <button @click="isModalExpanded = !isModalExpanded" type="button" class="btn btn-outline-light d-none d-md-inline-block" :title="isModalExpanded ? '{{ __tr('Shrink Dialog') }}' : '{{ __tr('Expand Dialog') }}'">
                        <i class="fas" :class="isModalExpanded ? 'fa-compress-arrows-alt' : 'fa-expand-arrows-alt'"></i>
                    </button>
                    <button type="button" class="btn btn-outline-light" data-dismiss="modal" title="{{ __tr('Close Dialog') }}" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                </span>
               <span class="d-inline-block d-md-none">
                 <button type="button" class="btn btn-outline-light" data-dismiss="modal" title="{{ __tr('Close Dialog') }}" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
               </span>
            </div>
            <div class="modal-body {{ $hasForm ? 'p-0' : '' }}">
                {{ $slot }}
            </div>
            @if (!$hasForm)
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __tr('Close') ?></button>
          {{ $footer ?? '' }}
        </div>
        @endif
      </div>
    </div>
  </div>
