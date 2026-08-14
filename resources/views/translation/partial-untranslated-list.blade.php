<?php $lineCount = 1;
  $hasNoTranslationData = false; ?>
@foreach($translations as $translationsItemKey => $translationsItem)
@if(!$translationsItem->isTranslated())
@php
$hasNoTranslationData = true;
@endphp
<div class="card mb-4">
    <div class="card-header lw-original-text-line">
        <?= $translationsItem->getOriginal() ?>
    </div>
    <div class="card-body">
        <form class="row lw-ajax-form lw-form" method="post" action="<?= route('manage.translations.update', [
             'languageType' => 'untranslated']) ?>"
            data-show-processing="true">
            <div class="input-group mb-3">
                <?php if ($translationsItem->getPlural()) : ?>
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <?= __tr('Singular') ?>
                    </div>
                </div>
                <?php endif; ?>
                <input type="text" class="form-control" name="message_str" id="<?= $translationsItemKey ?>"
                    value="<?= $translationsItem->getTranslation() ?>">
                <input type="hidden" name="message_id" value="<?= $translationsItem->getOriginal(); ?>">
                <input type="hidden" name="message_for_translate"
                    value="<?= $translationsItem->getOriginal(); ?>">
                <input type="hidden" name="id" value="<?= $translationsItemKey ?>">
                <input type="hidden" name="language_id" value="<?= $languageId ?>">
                <input type="hidden" name="old_message_str" value="<?= $translationsItem->getTranslation() ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary text-white lw-auto-translate-action" type="button"
                        title="<?= __tr('Google Auto Translate') ?>"><i class="fa fa-language"></i>
                        <?= __tr('Auto Translate') ?>
                    </button>
                    <button class="btn btn-dark lw-save-translation lw-ajax-form-submit-action" type="button">
                        <?= __tr('Save') ?>
                    </button>
                </div>
            </div>
        </form>
        <?php if ($translationsItem->getPlural()) : ?>
        <form class="row lw-ajax-form lw-form" method="post" action="<?= route('manage.translations.update', [
             'languageType' => 'untranslated']) ?>"
            data-show-processing="true">
            <label for="<?= $translationsItemKey ?>">
                <?= $translationsItem->getPlural() ?>
            </label>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <?= __tr('Plural') ?>
                    </div>
                </div>
                <input type="text" class="form-control" name="message_str_plural"
                    id="<?= $translationsItemKey ?> Plural"
                    value="<?= $translationsItem->getPluralTranslations(2)[0] ?>">
                <input type="hidden" name="message_id" value="<?= $translationsItem->getOriginal() ?>">
                <input type="hidden" name="message_for_translate"
                    value="<?= $translationsItem->getPlural(); ?>">
                <input type="hidden" name="is_plural" value="true">
                <input type="hidden" name="id" value="<?= $translationsItemKey ?> Plural">
                <input type="hidden" name="language_id" value="<?= $languageId ?>">
                <input type="hidden" name="old_message_str_plural"
                    value="<?= $translationsItem->getPluralTranslations(2)[0] ?>">
                <div class="input-group-append">
                    <button class="btn border lw-auto-translate-action" type="button"
                        title="<?= __tr('Google Auto Translate') ?>"><i class="fa fa-language"></i>
                        <?= __tr('Auto Translate') ?>
                    </button>
                    <button class="btn btn-dark lw-save-translation lw-ajax-form-submit-action" type="button">
                        <?= __tr('Save') ?>
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
        <?php $lineCount++; ?>

    </div>
</div>
@endIf
@endforeach
@if ((!$hasNoTranslationData))
<div class="card mb-4">
    <div class="card-body text-center">
        <?= __tr('There are no untranslated string available.') ?>
    </div>
</div>
@endIf