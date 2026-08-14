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

use App\Yantrana\__Laraware\Core\CoreController;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class BaseController extends CoreController
{
    /**
     * Send response to client
     *
     *
     * @return array
     *-------------------------------------------------------------------------- */
    public function responseAction($processResponse, $typeResponse = [])
    {
        $originalData = $processResponse->getData();
        $originalData->response_action = array_merge([
            'type' => null, // redirect, replace, append, prepend
            'target' => null, // replacement element identifier or redirect url
            'content' => null,
            'url' => null,
        ], $typeResponse);
        $processResponse->setData($originalData);

        return $processResponse;
    }

    /**
     * Replace view preparation
     *
     *
     * @return array
     *-------------------------------------------------------------------------- */
    public function replaceView($viewName, $data = [], $targetElement = '#pageContent')
    {
        return [
            'type' => 'replace', // redirect, replace, append, prepend
            'target' => $targetElement, // replacement element identifier or redirect url
            'content' => view($viewName, $data)->render(),
        ];
    }

    /**
     * Replace view content preparation
     *
     *
     * @return array
     *-------------------------------------------------------------------------- */
    public function replaceContent($content, $targetElement = '#pageContent')
    {
        return [
            'type' => 'replace', // redirect, replace, append, prepend
            'target' => $targetElement, // replacement element identifier or redirect url
            'content' => $content,
        ];
    }

    /**
     * Redirect user
     *
     * @param  string  $routeOrUrl
     * @param  array  $parameters
     * @param  string|array  $message  - 22 APR 2021
     * @return array
     */
    public function redirectTo($routeOrUrl, $parameters = [], $message = '')
    {
        if (! $parameters) {
            $parameters = [];
        }

        if ($message and is_array($message)) {
            session()->flash('alertMessage', Arr::get($message, '0'));
            session()->flash('alertMessageType', Arr::get($message, '1', 'info'));
        } elseif ($message) {
            session()->flash('alertMessage', $message);
        }

        return [
            'type' => 'redirect', // redirect, replace, append, prepend
            'url' => Str::startsWith($routeOrUrl, 'http') ? $routeOrUrl : route($routeOrUrl, $parameters),
        ];
    }

    /**
     * Replace view preparation
     *
     *
     * @return array
     *-------------------------------------------------------------------------- */
    public function loadManageView($viewName, $data = [])
    {
        return $this->loadView($viewName, $data);
    }
    /**
     * API Responses
     *
     * @param EngineResponse|array $processReaction
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function processApiResponse($processReaction, $data = []): \Illuminate\Http\JsonResponse
    {
        return processExternalApiResponse($processReaction, $data);
    }
}
