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
* MediaController.php - Controller file
*
* This file is part of the Media component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Components\Media\Controllers;

use App\Yantrana\Base\BaseController;
use App\Yantrana\Components\Configuration\ConfigurationEngine;
use App\Yantrana\Components\Media\MediaEngine;
use App\Yantrana\Components\Vendor\VendorSettingsEngine;
use Illuminate\Http\Request;

class MediaController extends BaseController
{
    /**
     * @var MediaEngine - Media Engine
     */
    protected $mediaEngine;

    /**
     * @var ConfigurationEngine - Configuration Engine
     */
    protected $configurationEngine;

    /**
     * @var VendorSettingsEngine - Vendor Engine
     */
    protected $vendorSettingsEngine;

    /**
     * Constructor
     *
     * @param  MediaEngine  $mediaEngine  - Media Engine
     * @return void
     *-----------------------------------------------------------------------*/
    public function __construct(
        MediaEngine $mediaEngine,
        ConfigurationEngine $configurationEngine,
        VendorSettingsEngine $vendorSettingsEngine
    ) {
        $this->mediaEngine = $mediaEngine;
        $this->configurationEngine = $configurationEngine;
        $this->vendorSettingsEngine = $vendorSettingsEngine;
    }

    /**
     * Upload Temp Media.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadTempMedia(Request $request, $uploadItem = 'all')
    {
        $processReaction = $this->mediaEngine
            ->processUploadTempMedia($request->all(), $uploadItem);

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload Temp Media.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadTempAddon(Request $request, $uploadItem = 'all')
    {
        if (isDemo()) {
            return $this->processResponse(22, [
                22 => __tr('Functionality is disabled in this demo.')
            ], [], true);
        }
        $processReaction = $this->mediaEngine
            ->processAddonUploadTemp($request->all(), 'addon_upload_file');

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload Logo.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadLogo(Request $request)
    {
        $processReaction = $this->mediaEngine
            ->processUploadLogo($request->all(), 'logo');
        // Check if file uploaded successfully
        if ($processReaction->success()) {
            $this->configurationEngine->processConfigurationsStore('general', [
                'logo_name' => $processReaction['data']['fileName'],
            ], true);
        }

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

     /**
     * Upload Dark Theme Logo.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadDarkThemeLogo(Request $request)
    {
        $processReaction = $this->mediaEngine
            ->processUploadDarkThemeLogo($request->all(), 'dark_theme_logo');
        
        // Check if file uploaded successfully
        if ($processReaction->success()) {
            $this->configurationEngine->processConfigurationsStore('general', [
                'dark_theme_logo_name' => $processReaction['data']['fileName'],
            ], true);
        }

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload Logo.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadFavicon(Request $request)
    {
        $processReaction = $this->mediaEngine
            ->processUploadFavicon($request->all(), 'favicon');

        // Check if file uploaded successfully
        if ($processReaction->success()) {
            $this->configurationEngine->processConfigurationsStore('general', [
                'favicon_name' => $processReaction['data']['fileName'],
            ], true);
        }

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload Dark Theme Favicon.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadDarkThemeFavicon(Request $request)
    {
        $processReaction = $this->mediaEngine
            ->processUploadDarkThemeFavicon($request->all(), 'dark_theme_favicon');

        // Check if file uploaded successfully
        if ($processReaction->success()) {
            $this->configurationEngine->processConfigurationsStore('general', [
                'dark_theme_favicon_name' => $processReaction['data']['fileName'],
            ], true);
        }

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload Logo.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function vendorUpload(Request $request, $uploadItem)
    {
        $allowedItems = [
            'vendor_logo' => 'logo_name',
            'vendor_small_logo' => 'small_logo_name',
            'vendor_favicon' => 'favicon_name',
        ];
        $processReaction = $this->mediaEngine
            ->processVendorUpload($request->all(), $uploadItem, $allowedItems);
        // Check if file uploaded successfully
        if ($processReaction->success()) {
            $this->vendorSettingsEngine->updateBasicSettingsProcess([
                $allowedItems[$uploadItem] => $processReaction['data']['fileName'],
            ]);

            return $this->processResponse($processReaction, [], [], true);
        }

        return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload  Small Logo.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadSmallLogo(Request $request)
    {
        $processReaction = $this->mediaEngine->processUploadSmallLogo($request->all(), 'small_logo');
    // Check if file uploaded successfully
    if ($processReaction->success()) {
        $this->configurationEngine->processConfigurationsStore('general', [
            'small_logo_name' => $processReaction['data']['fileName'],
        ], true);
    }

    return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * Upload Dark Theme Small Logo.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function uploadDarkThemeSmallLogo(Request $request)
    {
        $processReaction = $this->mediaEngine->processUploadDarkThemeSmallLogo($request->all(), 'dark_theme_small_logo');
    // Check if file uploaded successfully
    if ($processReaction->success()) {
        $this->configurationEngine->processConfigurationsStore('general', [
            'dark_theme_small_logo_name' => $processReaction['data']['fileName'],
        ], true);
    }

    return $this->processResponse($processReaction, [], [], true, $processReaction->success() ? 200 : 406);
    }

    /**
     * list of files and media
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function getMediaAndFileView()
    {
        $mediaFileSupportData = $this->mediaEngine->prepareMediaAndFileSupportData();

        return $this->loadView('files-media.list', $mediaFileSupportData);
    }

    /**
     * list of files and media
     *
     * @return json object
     *---------------------------------------------------------------- */
    public function getMediaAndFileDataTableList($vendorUid = 'all', $mediaType = 'all')
    {
        return $this->mediaEngine->prepareListOfMediaAndFiles($vendorUid, $mediaType);
    }

    /**
     * Delete media and file.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function deleteMediaAndFile(Request $request)
    {
        $inputData = $request->all();

        $processReaction = $this->mediaEngine
            ->processDeleteFile($inputData['path'], $inputData['filename']);

        return $this->processResponse(1, [], [], true);
    }

    /**
     * Delete bulk media and file.
     *
     * @param object Request $request
     * @return json object
     *---------------------------------------------------------------- */
    public function deleteBulkMediaAndFile(Request $request)
    {
        $processReaction = $this->mediaEngine
            ->processDeleteAllFiles($request->all());

        return $this->processResponse(1, [], [], true);
    }
}
