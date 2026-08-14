<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

/**
* TranslationController.php - Controller file
*
* This file is part of the Translation component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Translation\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Translation\TranslationEngine;
use App\Yantrana\Components\Translation\Requests\LanguageAddRequest;
use App\Yantrana\Components\Translation\Requests\LanguageUpdateRequest;
use App\Yantrana\Components\Translation\Requests\TranslationUpdateRequest;
use App\Yantrana\Support\CommonClearPostRequest;

class TranslationController extends BaseController
{
    /**
     * @var  TranslationEngine $translationEngine - Translation Engine
     */
    protected $translationEngine;

    /**
     * Constructor
     *
     * @param  TranslationEngine $translationEngine - Translation Engine
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function __construct(TranslationEngine $translationEngine)
    {
        $this->translationEngine = $translationEngine;
    }

    /**
     * lists Translate
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function languages()
    {
        $processReaction = $this->translationEngine->languages();

        return $this->loadManageView(
            'translation.languages_list',
            $processReaction['data']
        );
    }

    /**
     * Store Language
     *
     * @param object LanguageAddRequest $request
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function storeLanguage(LanguageAddRequest $request)
    {
        $processReaction = $this->translationEngine->processStoreLanguage($request->all());

        return $this->processResponse($processReaction, [], [  'show_message' => true], true);
    }

    /**
     * Store Language
     *
     * @param object LanguageUpdateRequest $request
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function updateLanguage(LanguageUpdateRequest $request)
    {
        $processReaction = $this->translationEngine->processUpdateLanguage($request->all());

        return $this->processResponse($processReaction, [], [  'show_message' => true], true);
    }

    /**
     * Store Language
     *
     * @param object LanguageAddRequest $request
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function deleteLanguage(CommonClearPostRequest $request, $languageId)
    {
        $processReaction = $this->translationEngine->processDeleteLanguage($languageId);

        return $this->processResponse($processReaction, [], [  'show_message' => true], true);
    }

    /**
     * lists Translate
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function lists($languageId ,$languageType = null)
    {
      
        $processReaction = $this->translationEngine->lists($languageId);
         //manage untranslated tabs
         $gotoPage = 'untranslated';
         if(!$languageType  or ($languageType == 'untranslated')) {
             $gotoPage = 'untranslated';
         }
     
        return $this->loadManageView(
            'translation.list',
            [
                'translations' => $processReaction['data']['translations'],
                'languageId' => $languageId,
             
                'languageInfo' => $processReaction['data']['languageInfo']
            ]
        );
    }

    /**
     * Scan for strings
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function scan($languageId, $preventReload = false)
    {
        $processReaction = $this->translationEngine->scan($languageId);

        if ($preventReload) {
            return $processReaction;
        }

        //check reaction code equal to 1
        if ($processReaction['reaction_code'] === 1) {
            return $this->responseAction(
                $this->processResponse($processReaction, [], [], true),
                $this->redirectTo('manage.translations.lists', [
                    'languageId' => $languageId,'languageType' => 'translated'
                ])
            );
        } else {
            return $this->responseAction(
                $this->processResponse($processReaction, [], [], true)
            );
        }
    }

    /**
     * Update Translate
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function update(TranslationUpdateRequest $request,$languageType=null)
    {
        // $languageId= $request['language_id'];
        return $this->processResponse(
                    $this->translationEngine->update($request->all()),
                    [],
                    [  'show_message' => true],
                    true
            );/*
        return $this->responseAction(
             $this->processResponse(
                $this->translationEngine->update($request->all()),
                [],
                [  'show_message' => true],
                true
            ),
            $this->redirectTo('manage.translations.lists', [ 
                'languageId' => $languageId, 'languageType' => $languageType
            ])
        ); */
    }

    /**
     * export
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function export($languageId)
    {
        return $this->translationEngine->exportToExcel($languageId);
    }

    /**
     * export
     *
     * @return  void
     *-----------------------------------------------------------------------*/

    public function import(CommonClearPostRequest $request, $languageId)
    {
        $processReaction = $this->translationEngine->importExcel($request->all(), $languageId);

        return $this->processResponse($processReaction, [], [], true);
    }

    public function translatePoFiles(CommonClearPostRequest $request, $serviceId)
    {
        $processReaction = $this->translationEngine->processTranslatePoFiles($serviceId);

        return $this->processResponse($processReaction, [], [
            'show_message' => true
        ], true);
    }

    public function translatePoFile(CommonClearPostRequest $request, $serviceId, $languageId)
    {
        $processReaction = $this->translationEngine->processTranslatePoFile($serviceId, $languageId);
        return $this->processResponse($processReaction, [], [
            'show_message' => true
        ], true);
    }
}
