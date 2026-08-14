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


namespace App\Yantrana\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Yantrana\Base\BaseController;
use App\Yantrana\Base\BaseRequestTwo;
use Illuminate\Support\Facades\Response;
use App\Yantrana\Components\Configuration\Requests\ConfigurationRequest;

class AddonBaseController extends BaseController
{
    /**
     * Addon Namespace
     *
     * @var string
     */
    protected $addonNamespace = "AddonNamespace";
    /**
     * Show Addon Settings Page
     *
     * @return view
     */
    public function showSettings()
    {
        validateVendorAccess('administrative');
        return $this->addonView('settings');
    }

    /**
     * Get Addon Base Path
     *
     * @param string $path
     * @return string
     */
    function addonBasePath($path = '') {
       return base_path('addons'. DIRECTORY_SEPARATOR . $this->addonNamespace. DIRECTORY_SEPARATOR . $path);
    }
    /**
     * Serve addon assets.
     *
     * @param Request $request
     * @param string $path
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function assetServe(BaseRequestTwo $request, $path)
    {
        $file = $this->addonBasePath('assets' . DIRECTORY_SEPARATOR . $path);
        if (!File::exists($file)) {
            abort(404, 'Asset not found.');
        }
        $mimeType = File::mimeType($file);
        return Response::make(File::get($file), 200, ['Content-Type' => $mimeType]);
    }

    /**
     * Activate Addon View
     *
     * @return view
     */
    public function setupView()
    {
        return $this->addonView('setup', [
            'addonMetadata' => $this->addonMetadata(),
            'addon' => $this->addonNamespace,
            'addonLicInfo' => function ($item) {
                return  $this->getAddonLicInfo($item);
            },
        ]);
    }

    /**
     * Base Addon View method
     *
     * @param string $viewName
     * @param array $parameters
     * @return view
     */
    public function addonView($viewName, $parameters = [])
    {
        return view("{$this->addonNamespace}::$viewName", $parameters);
    }

    /**
     * Addon Metadata
     *
     * @return array
     */
    public function addonMetadata()
    {
        return require $this->addonBasePath('/config/metadata.php');
    }

    /**
     * Get addon lic info
     *
     * @param string $item
     * @return mixed
     */
    public function getAddonLicInfo($item = null)
    {
        return getAppSettings('lwAddon' . $this->addonNamespace, $item);
    }

    /**
     * Process addon reg
     *
     * @param  array  $inputData
     * @return mixed
     *---------------------------------------------------------------- */
    public function processAddonActivation(ConfigurationRequest $request)
    {
        if (!app('tapasaSwakshari')()) {
            return $this->responseAction(
                $this->processResponse(21, [], [], true),
                $this->redirectTo('a'.'d'.'do'.'n.'. $this->addonNamespace.'.s'.'et'.'up_v'.'ie'.'w', [], [
                    'F'.'ai'.'le'.'d t'.'o'.' va'.'l'.'id'.'at'.'e M'.'a'.'in p'.'ro'.'du'.'c'.'t l'.'i'.'ce'.'n'.'se.',
                    'error',
                ])
            );
        }
        $inputData = $request->all();
        $processReaction = setAppSettings('lwAddon'. $this->addonNamespace, [
            'lwAddon'. $this->addonNamespace => [
                'registration_id' => array_get($inputData, 'registration_id', ''),
                'email' => array_get($inputData, 'your_email', ''),
                'licence' => array_get($inputData, 'licence_type', ''),
                'supported_until' => array_get($inputData, 'supported_until', ''),
                'registered_at' => now(),
                'signature' => sha1(
                    array_get($_SERVER, 'HTTP_HOST', '').
                        array_get($inputData, 'registration_id', '') . '1.0+'
                ),
            ],
        ]);

        return $this->responseAction($this->processResponse($processReaction, [], [], true));
    }

    /**
     * Process addon reg remove
     *
     *
     * @return response
     *---------------------------------------------------------------- */
    public function processAddonDeactivation(ConfigurationRequest $request)
    {
        // remote removal
        $existingRegistrationId = $this->getAddonLicInfo('registration_id');
        if (!$request->isMethod('post') and $existingRegistrationId and (!$request->registration_id or ($existingRegistrationId != $request->registration_id))) {
            abort(404, __tr('Invalid Request'));
        }

        try {
            // Initialize a cURL session
            $curl = curl_init();
            // Define the URL where you want to send the POST request
            $url = config('lwSystem.app_update_url') . "/api/app-update/deactivate-license"; // Replace with the actual URL
            // Define the POST fields, including the 'registration_id' parameter
            $postData = [
                'registration_id' => $existingRegistrationId, // Replace with the actual registration ID
            ];
            // Set the Origin header
            $headers = [
                'Origin: ' . array_get($_SERVER, 'HTTP_ORIGIN', ''), // Replace with your actual origin
            ];
            // Set cURL options
            curl_setopt($curl, CURLOPT_URL, $url); // Set the URL
            curl_setopt($curl, CURLOPT_POST, true); // Specify the request method as POST
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData)); // Attach the POST fields
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); // Attach the Origin header
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
            // Execute the cURL request
            $response = curl_exec($curl);
            // Check for errors
            if ($response === false) {
                $error = curl_error($curl);
                // echo "cURL Error: $error";
            } else {
                // Handle the response as needed
                // echo "Response: $response";
                // __logDebug($response);
            }
            // Close the cURL session
            curl_close($curl);
        } catch (\Throwable $th) {
            //throw $th;
        }

        $processReaction = setAppSettings('lwAddon'. $this->addonNamespace, [
            'lwAddon'. $this->addonNamespace => [
                'registration_id' => '',
                'email' => '',
                'licence' => '',
                'registered_at' => now(),
                'signature' => '',
            ],
        ]);

        return $this->responseAction($this->processResponse($processReaction, [], [], true));
    }
}
