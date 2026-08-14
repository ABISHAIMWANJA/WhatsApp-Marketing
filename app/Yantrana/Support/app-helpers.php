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


use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Laravel\Cashier\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Events\VendorChannelBroadcast;
use App\Yantrana\Components\Auth\Models\AuthModel;
use App\Yantrana\Components\Vendor\Models\VendorModel;
use App\Yantrana\Components\Vendor\Models\VendorUserModel;
use App\Yantrana\Components\Page\Repositories\PageRepository;
use App\Yantrana\Support\Country\Repositories\CountryRepository;
use App\Yantrana\Components\Vendor\Repositories\VendorRepository;
use App\Yantrana\Components\User\Repositories\ActivityLogRepository;
use App\Yantrana\Components\Subscription\Models\ManualSubscriptionModel;
use App\Yantrana\Components\Subscription\Support\SubscriptionPlanDetails;
use App\Yantrana\Components\Contact\Repositories\ContactCustomFieldRepository;
use App\Yantrana\Components\Subscription\Repositories\ManualSubscriptionRepository;
use App\Yantrana\Components\WhatsAppService\Repositories\WhatsAppMessageLogRepository;
use App\Yantrana\Components\UserDevice\Repositories\UserDeviceRepository;
use Google\Client as GoogleClient;

if (! function_exists('getUserAuthInfo')) {
    /**
     * get the authenticated user info
     *
     * @param  int|string  $itemOrStatusCode
     * @return mixed
     */
    function getUserAuthInfo($itemOrStatusCode = null)
    {
        $userAuthInfo = [
            'authorized' => false,
            'reaction_code' => 9, // Not Authenticated
        ];

        if (Auth::check()) {
            $userAuthInfo = viaFlashCache('user_auth_info', function () use (&$itemOrStatusCode) {
                $user = AuthModel::with('role', 'vendor')->find(Auth::id());
                $vendorUser = null;
                $vendorId = $user->vendors__id ?? null;
                $vendorUid = $user->vendor->_uid ?? null;
                $vendorStatus = $user->vendor->status ?? null;
                if ($user->user_roles__id == 3) {
                    $vendorUser = VendorUserModel::where('users__id', $user->_id)->first();
                    $vendorId = $vendorUser->vendors__id;
                    $vendor = VendorModel::where('_id', $vendorId)->first();
                    $vendorUid = $vendor->_uid;
                    $vendorStatus = $vendor->status;
                }
                return [
                    'authorized' => true,
                    'reaction_code' => (! is_string($itemOrStatusCode) and ! empty($itemOrStatusCode))
                        ? $itemOrStatusCode : 10, // 10 is Authenticated
                    'id' => $user->_id,
                    'uuid' => $user->_uid,
                    'role_id' => $user->role->_id,
                    'role_title' => $user->role->title ?? '',
                    'vendor_id' => $vendorId,
                    'vendor_created_at' => $user->created_at ?? null,
                    'vendor_uid' => $vendorUid,
                    'vendor_status' => $vendorStatus,
                    'personnel' => $user->_id,
                    'status' => $user->status,
                    'timezone' => $user->timezone ?? config('app.timezone'),
                    'country_id' => $user->countries__id,
                    'permissions' => $vendorUser?->__data['permissions'],
                    'profile' => [
                        'username' => $user->username ?? '',
                        'full_name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                        'first_name' => $user->first_name ?? '',
                        'last_name' => $user->last_name ?? '',
                        'email' => $user->email ?? '',
                        'mobile_number' => $user->mobile_number ?? '',
                    ],
                ];
            });
        }

        if (is_string($itemOrStatusCode)) {
            return array_get($userAuthInfo, $itemOrStatusCode, null);
        }

        return $userAuthInfo;
    }
}

if (! function_exists('isLoggedIn')) {
    /**
     * Check if user logged in or not
     *
     * @return bool
     */
    function isLoggedIn()
    {
        return Auth::check();
    }
}

if (! function_exists('getVendorId')) {
    /**
     * Get Vendor Id
     *
     * @return bool
     */
    function getVendorId()
    {
        return getUserAuthInfo('vendor_id');
    }
}

if (! function_exists('getVendorUid')) {
    /**
     * Get Vendor Uid
     *
     * @return string
     */
    function getVendorUid()
    {
        return getUserAuthInfo('vendor_uid') ?? getPublicVendorUid();
    }
}

if (! function_exists('getPublicVendorId')) {
    /**
     * Get Vendor Id
     *
     * @return int|null
     */
    function getPublicVendorId($vendorIdOrUid = null)
    {
        return viaFlashCache('public_vendor_id_' . $vendorIdOrUid, function () use (&$vendorIdOrUid) {
            $vendorRepo = new VendorRepository();
            if ($vendorIdOrUid) {
                $identifiedVendor = $vendorRepo->fetchIt($vendorIdOrUid);
            } else {
                $identifiedVendor = $vendorRepo->fetchBySlug(getPublicVendorSlug());
            }
            if (__isEmpty($identifiedVendor)) {
                return null;
            }

            return $identifiedVendor->_id;
        });
    }
}

if (! function_exists('getPublicVendorUid')) {
    /**
     * Get Vendor Id
     *
     * @return bool
     */
    function getPublicVendorUid($publicVendorId = null)
    {
        $publicVendorId = $publicVendorId ?: getPublicVendorId();
        if (! $publicVendorId) {
            return null;
        }

        return viaFlashCache('public_vendor_uid_' . $publicVendorId, function () use (&$publicVendorId) {
            $vendorRepo = new VendorRepository();
            $getVendor = $vendorRepo->fetchIt($publicVendorId);
            if (__isEmpty($getVendor)) {
                return null;
            }
            return $getVendor->_uid;
        });
    }
}

if (! function_exists('getPublicVendorSlug')) {
    /**
     * Get Vendor Slug
     *
     * @return string
     */
    function getPublicVendorSlug()
    {
        $vendorSlug = request()->route('vendorSlug');
        // As in AppServiceProvider route is not identified
        // we are trying to access the url segment to identify the vendor
        if (! $vendorSlug) {
            $segment = request()->segment(1);
            if (Str::startsWith($segment, '@')) {
                $vendorSlug = substr($segment, 1);
            }
        }

        return $vendorSlug;
    }
}

/**
 * cleanPath
 *
 * @param  string  $str
 * @return string
 *-------------------------------------------------------- */
if (! function_exists('cleanPath')) {
    function cleanPath($str, $startWith = '')
    {
        $str = preg_replace('#/+#', '/', $str);
        $str = trim($str, '/');

        return $startWith . $str;
    }
}

if (! function_exists('hasCentralAccess')) {
    /**
     * Check if logged in user is super admin
     *
     * @return bool
     */
    function hasCentralAccess()
    {
        return getUserAuthInfo('role_id') === 1;
    }
}

if (! function_exists('validateVendorAccess')) {

    /**
     * Protect action based on permissions
     *
     * @param string|array $permission
     * @return \Illuminate\Auth\Access\Response
     */
    function validateVendorAccess(string|array $permissions, $nestedPermission = null)
    {
        $hasAccess = false;
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                $hasAccess = hasVendorAccess($permission, $nestedPermission);
                if ($hasAccess) {
                    break;
                }
            }
        } else {
            $hasAccess = hasVendorAccess($permissions, $nestedPermission);
        }
        return \Illuminate\Support\Facades\Gate::allowIf($hasAccess);
    }
}

if (! function_exists('hasVendorAccess')) {
    /**
     * Check if user is Vendor Admin
     *
     * @return bool
     */
    function hasVendorAccess($permission = null, $nestedPermission = null)
    {
        // if vendor admin
        if ((getUserAuthInfo('role_id') === 2)) {
            return true;
        }
        // if vendor user then needs to check permissions
        if (hasVendorUserAccess() and $permission) {
            $mainPermission = getUserAuthInfo("permissions.$permission") === 'allow';
            if ($mainPermission and $nestedPermission) {
                $subPermission = getUserAuthInfo("permissions.$permission@$nestedPermission");
                return ((__isEmpty($subPermission)) or ($subPermission === 'allow'));
            }
            return $mainPermission;
        }
        return false;
    }
}

if (! function_exists('hasVendorUserAccess')) {
    /**
     * Check if user is Vendor User Access
     *
     * @return bool
     */
    function hasVendorUserAccess()
    {
        return (getUserAuthInfo('role_id') === 3);
    }
}

if (! function_exists('isVendorAdmin')) {
    /**
     * Check if has vendor admin access
     *
     * @return bool
     */
    function isVendorAdmin($vendorId)
    {
        return hasVendorAccess() and (getVendorId() === $vendorId);
    }
}

if (! function_exists('isVendorShop')) {
    /**
     * Check if user is on Vendor Shop
     *
     * @return bool
     */
    function isVendorShop()
    {
        return getPublicVendorId();
    }
}

/*
      * Convert date with setting time zone
      *
      * @param string $rawDate
      *@param int $vendorId
      * @return date
      *-------------------------------------------------------- */

if (! function_exists('appTimezone')) {
    function appTimezone($rawDate, $vendorId = null, $appTimezone = null)
    {
        if (is_numeric($rawDate)) {
            $carbonDate = Carbon::createFromTimestamp($rawDate);
        } else {
            $carbonDate = Carbon::parse($rawDate);
        }

        if (!$appTimezone) {
            $appTimezone = getVendorSettings('timezone', null, null, $vendorId);
            if (! $appTimezone) {
                $appTimezone = getAppSettings('timezone');
            }
        }
        if (! __isEmpty($appTimezone)) {
            $carbonDate->timezone = $appTimezone;
        }

        return $carbonDate;
    }
}

if (! function_exists('getTimezonesArray')) {
    function getTimezonesArray()
    {
        $timezoneCollection = [];
        $timezoneList = timezone_identifiers_list();
        foreach ($timezoneList as $timezone) {
            $timezoneCollection[] = [
                'value' => $timezone,
                'text' => $timezone,
            ];
        }

        return $timezoneCollection;
    }
}

/**
 * Get formatted date from passed raw date using timezone
 *
 * @param  string  $rawDateTime
 * @param  string  $format
 *
 * @help https://www.php.net/manual/en/datetime.format.php
 *
 * @return date
 *-------------------------------------------------------- */
if (! function_exists('formatDate')) {
    function formatDate($rawDateTime, $format = 'l jS F Y', $vendorId = null, $timezone = null)
    {
        $date = appTimezone($rawDateTime, $vendorId, $timezone);

        return __tr($date->translatedFormat($format));
    }
}

if (! function_exists('formatDateTime')) {
    /**
     * Get formatted Date Time based on vendor id and timezone
     *
     * @param mixed $rawDateTime
     * @param string|null $format
     * @param integer|null $vendorId
     * @param string|null $timezone
     * @return string
     */
    function formatDateTime($rawDateTime, $format = null, $vendorId = null, $timezone = null)
    {
        if (! $format) {
            $format = 'l jS F Y g:i:s a';
        }

        return formatDate($rawDateTime, $format, $vendorId, $timezone);
    }
}

/**
 * Get formatted date from passed raw date using timezone
 *
 * @param  string  $rawDateTime
 * @param  string  $format
 * @return date
 *-------------------------------------------------------- */
if (! function_exists('formatDiffForHumans')) {
    function formatDiffForHumans($rawDateTime, $parts = 1, $vendorId = null)
    {
        $date = appTimezone($rawDateTime, $vendorId);

        return $date->diffForHumans(null, null, false, $parts);
    }
}

/**
 * Get the technical items from tech items
 *
 * @param  string  $key
 * @param  mixed  $requireKeys
 * @return mixed
 *-------------------------------------------------------- */
if (! function_exists('configItem')) {
    function configItem($key, $requireKeys = null)
    {
        if (! __isEmpty($requireKeys) and ! is_array($requireKeys)) {
            return config('__tech.' . $key . '.' . $requireKeys);
        }

        return array_get(config('__tech'), $key);
    }
}

/**
 * Set the app settings
 *
 * @param  string  $name
 * @return void
 *---------------------------------------------------------------- */
if (! function_exists('setAppSettings')) {
    function setAppSettings($pageType, $inputData, $ignoreOtherFields = false)
    {
        return app()->make(\App\Yantrana\Components\Configuration\ConfigurationEngine::class)->processConfigurationsStore($pageType, $inputData, $ignoreOtherFields);
    }
}
/**
 * Set the vendor settings
 *
 * @param  string  $name
 * @return void
 *---------------------------------------------------------------- */
if (! function_exists('setVendorSettings')) {
    function setVendorSettings($pageType, $inputData, $vendorId = null)
    {
        return app()->make(\App\Yantrana\Components\Vendor\VendorSettingsEngine::class)->updateProcess($pageType, $inputData, $vendorId);
    }
}
/**
 * get setting items
 *
 * @param  string  $name
 * @return void
 *---------------------------------------------------------------- */
if (! function_exists('getAppSettings')) {
    function getAppSettings($itemName, $itemKeys = null)
    {
        if ($itemKeys) {
            return Arr::get(getAppSettings($itemName), $itemKeys);
        }

        $appSettings = [];
        $exceptItems = config('__settings.autoload_exceptions', []);
        if (!empty($exceptItems) and $itemName and in_array($itemName, $exceptItems)) {
            $configurationSetting = \App\Yantrana\Components\Configuration\Models\ConfigurationModel::where('name', $itemName)->select('name', 'value', 'data_type')->first();
            if (!__isEmpty($configurationSetting)) {
                $appSettings[$configurationSetting->name] = $configurationSetting->value;
            }
            $storeConfiguration = $appSettings;
        } else {
            $storeConfiguration = viaFlashCache('app_setting_all', function () use (&$appSettings, &$exceptItems) {
                $configurationSettings = \App\Yantrana\Components\Configuration\Models\ConfigurationModel::select('name', 'value', 'data_type');
                if (!empty($exceptItems)) {
                    $configurationSettings->whereNotIn('name', $exceptItems);
                }
                $configurationSettings = $configurationSettings->get();
                // check if configuration settings exists in db
                if (! __isEmpty($configurationSettings)) {
                    foreach ($configurationSettings as $configurationSetting) {
                        $appSettings[$configurationSetting->name] = $configurationSetting->value;
                    }
                }
                unset($configurationSettings);
                return $appSettings;
            });
        }
        // Fetch default setting
        // Vinod- 06 JAN 2024 - this is weird but there was issues for addons app items so we have to check if configuration is cached or not
        // if not then we will fetch default settings from config file
        // otherwise we will fetch it from flash via cache
        if (!app()->configurationIsCached()) {
            $defaultSettings = config('__settings.items');
        } else {
            $defaultSettings = viaFlashCache('app_settings_items_default', function () {
                return config('__settings.items');
            });
        }
        // check if default setting is empty
        if (__isEmpty($defaultSettings)) {
            return null;
        }
        // Loop over default items for finding item default value
        foreach ($defaultSettings as $defaultSetting) {
            // Check if item name exists in default settings
            if (array_key_exists($itemName, $defaultSetting)) {
                $thisSettingItem = $defaultSetting[$itemName]['default'];
                // check if requested item exists in store configuration array
                if (array_key_exists($itemName, $storeConfiguration)) {
                    switch ($defaultSetting[$itemName]['data_type']) {
                        case 1:
                            $thisSettingItem = (string) $storeConfiguration[$itemName];
                            break;
                        case 2:
                            $thisSettingItem = (bool) $storeConfiguration[$itemName];
                            break;
                        case 3:
                            $thisSettingItem = (int) $storeConfiguration[$itemName];
                            break;
                        case 4:
                            if (array_get($defaultSetting, $itemName . '.hide_value', false)) {
                                try {
                                    $thisSettingItem = decrypt($storeConfiguration[$itemName]);
                                } catch (\Exception $e) {
                                    $thisSettingItem = json_encode([]);
                                }
                            } else {
                                $thisSettingItem = $storeConfiguration[$itemName];
                            }
                            $thisSettingItem = json_decode($thisSettingItem, true);
                            break;
                        case 6:
                            $thisSettingItem = (float) $storeConfiguration[$itemName];
                            break;
                        default:
                            $thisSettingItem = $storeConfiguration[$itemName];
                            break;
                    }
                }
                if ($thisSettingItem and array_get($defaultSetting, $itemName . '.hide_value', false) and (is_string($thisSettingItem) or is_numeric($thisSettingItem))) {
                    try {
                        $thisSettingItem = decrypt($thisSettingItem);
                    } catch (\Exception $e) {
                        __clog("Hint: You may have changed API_KEY and the value may be encrypted using old API_KEY, so the encrypted value provided as it is for $itemName");
                    }
                }

                // Return default value
                return $thisSettingItem;
            }
        }

        // Check if request for logo image url
        if ($itemName == 'logo_image_url') {
            $logoName = getAppSettings('logo_name');
            $logoNameInConfig = configItem('logo_name');
            if ($logoName == $logoNameInConfig) {
                return asset('imgs/' . $logoNameInConfig);
            }
            $logoFilePath = getPathByKey('logo') . '/' . $logoName;
            $logoImageUrl = getMediaUrl($logoFilePath);
            return $logoImageUrl;
        }
        // Check if request for small logo image url
        if ($itemName == 'small_logo_image_url') {
            $smallLogoName = getAppSettings('small_logo_name');
            $smallLogoNameInConfig = configItem('small_logo_name');
            if (!$smallLogoName) {
                return asset('imgs/' . $smallLogoNameInConfig);
            }
            $smallLogoFilePath = getPathByKey('small_logo') . '/' . $smallLogoName;
            $smallLogoImageUrl = getMediaUrl($smallLogoFilePath);
            return $smallLogoImageUrl;
        }

        // Check if request for favicon url
        if ($itemName == 'favicon_image_url') {
            $faviconName = getAppSettings('favicon_name');
            $faviconNameInConfig = configItem('favicon_name');
            if ($faviconName == $faviconNameInConfig) {
                return asset('imgs/' . $faviconNameInConfig);
            }
            $faviconFilePath = getPathByKey('favicon') . '/' . $faviconName;
            $faviconImageUrl = getMediaUrl($faviconFilePath);
            if (! $faviconImageUrl) {
                $faviconImageUrl = asset('imgs/' . configItem('favicon_name'));
            }

            return $faviconImageUrl;
        }
        // Check if request for dark theme logo image url
        if ($itemName == 'dark_theme_logo_image_url') {
            $logoName = getAppSettings('dark_theme_logo_name');
            $logoNameInConfig = configItem('dark_theme_logo_name');
            if ($logoName == $logoNameInConfig) {
                return asset('imgs/' . $logoNameInConfig);
            }
            $logoFilePath = getPathByKey('dark_theme_logo') . '/' . $logoName;
            $logoImageUrl = getMediaUrl($logoFilePath);
            return $logoImageUrl;
        }
        // Check if request for dark theme small logo image url
        if ($itemName == 'dark_theme_small_logo_image_url') {
            $smallLogoName = getAppSettings('dark_theme_small_logo_name');
            $smallLogoNameInConfig = configItem('dark_theme_small_logo_name');
            if (!$smallLogoName) {
                return asset('imgs/' . $smallLogoNameInConfig);
            }
            $smallLogoFilePath = getPathByKey('dark_theme_small_logo') . '/' . $smallLogoName;
            $smallLogoImageUrl = getMediaUrl($smallLogoFilePath);
            return $smallLogoImageUrl;
        }

        // Check if request for dark theme favicon url
        if ($itemName == 'dark_theme_favicon_image_url') {
            $faviconName = getAppSettings('dark_theme_favicon_name');
            $faviconNameInConfig = configItem('dark_theme_favicon_name');
            if ($faviconName == $faviconNameInConfig) {
                return asset('imgs/' . $faviconNameInConfig);
            }
            $faviconFilePath = getPathByKey('dark_theme_favicon') . '/' . $faviconName;
            $faviconImageUrl = getMediaUrl($faviconFilePath);
            if (! $faviconImageUrl) {
                $faviconImageUrl = asset('imgs/' . configItem('favicon_name'));
            }

            return $faviconImageUrl;
        }

        return null;
    }
}

/**
 * get setting items
 *
 * @param  string  $name
 * @return void
 *---------------------------------------------------------------- */
if (! function_exists('getVendorSettings')) {
    function getVendorSettings($itemName, $itemKeys = null, $otherItem = null, $forVendorIdOrUid = null)
    {
        if ($itemKeys) {
            return Arr::get(getVendorSettings($itemName), $itemKeys);
        }

        $appSettings = [];
        if ($forVendorIdOrUid) {
            $vendorFound = viaFlashCache('vendor_for_vendor_id_or_uid_' . $forVendorIdOrUid, function () use (&$forVendorIdOrUid) {
                $vendorRepo = new VendorRepository();
                $findVendor = $vendorRepo->fetchIt($forVendorIdOrUid);
                if (__isEmpty($findVendor)) {
                    return null;
                }
                return [
                    'vendorId' => $findVendor->_id,
                    'vendorUid' => $findVendor->_uid,
                ];
            });
            if (__isEmpty($vendorFound)) {
                return null;
            }
            $vendorId = $vendorFound['vendorId'];
            $vendorUid = $vendorFound['vendorUid'];
        } else {
            $vendorId = getVendorId() ?? getPublicVendorId();
            $vendorUid = getVendorUid() ?? getPublicVendorUid();
        }
        // Fetch default setting
        // Vinod- 06 JAN 2024 - this is weird but there was issues for addons app items so we have to check if configuration is cached or not
        // if not then we will fetch default settings from config file
        // otherwise we will fetch it from flash via cache
        if (!app()->configurationIsCached()) {
            $defaultSettings = config('__vendor-settings.items');
        } else {
            $defaultSettings = viaFlashCache('app_vendor_settings_items_default', function () {
                return config('__vendor-settings.items');
            });
        }

        // check if default setting is empty
        if (__isEmpty($defaultSettings)) {
            return null;
        }
        // check if any other item requested
        $mainItemKey = null;
        if ($otherItem) {
            $filtered = Arr::where($defaultSettings, function ($value, $key) use ($itemName, &$mainItemKey) {
                return Arr::where($value, function ($internalValue, $internalKey) use ($itemName, $key, &$mainItemKey) {
                    if ($internalKey === $itemName) {
                        $mainItemKey = $key;
                        return true;
                    }
                    return false;
                });
            });

            return Arr::get($filtered, $mainItemKey . '.' . $itemName . '.' . $otherItem);
        }
        if (! $vendorId) {
            return null;
        }
        $exceptItems = config('__vendor-settings.autoload_exceptions', []);
        if (!empty($exceptItems) and $itemName and in_array($itemName, $exceptItems)) {
            $configurationSetting = \App\Yantrana\Components\Vendor\Models\VendorSettingsModel::where('vendors__id', $vendorId)->where('name', $itemName)->select('name', 'value', 'data_type')->first();
            if (!__isEmpty($configurationSetting)) {
                $appSettings[$configurationSetting->name] = $configurationSetting->value;
            }
            $storeConfiguration = $appSettings;
        } else {
            $storeConfiguration = viaFlashCache('vendor_setting_all_' . $vendorId, function () use (&$appSettings, &$vendorId, &$itemName, &$exceptItems) {
                $configurationSettings = \App\Yantrana\Components\Vendor\Models\VendorSettingsModel::where('vendors__id', $vendorId)->select('name', 'value', 'data_type');
                if (!empty($exceptItems)) {
                    $configurationSettings->whereNotIn('name', $exceptItems);
                }
                $configurationSettings = $configurationSettings->get();
                // check if configuration settings exists in db
                if (! __isEmpty($configurationSettings)) {
                    foreach ($configurationSettings as $configurationSetting) {
                        $appSettings[$configurationSetting->name] = $configurationSetting->value;
                    }
                }
                // Update Vendor Data
                $vendorData = \App\Yantrana\Components\Vendor\Models\VendorModel::where('_id', $vendorId)->select('logo_image', 'slug', 'title', 'favicon')->first();
                $appSettings['logo_image'] = $vendorData->logo_image;
                $appSettings['favicon_name'] = $vendorData->favicon;
                $appSettings['slug'] = $vendorData->slug;
                $appSettings['title'] = $vendorData->title;

                unset($configurationSettings);

                return $appSettings;
            });
        }

        if ($itemName === 'country_code') {
            $countryCode = getVendorSettings('country');
            if ($countryCode) {
                return viaFlashCache('vendor_setting_country_' . $vendorId, function () use ($appSettings, $vendorId) {
                    $countryRepository = new CountryRepository();
                    return $countryRepository->onlyColumns(['iso_code'])->fetchIt(getVendorSettings('country'))->iso_code;
                });
            }

            return null;
        }

        // Loop over default items for finding item default value
        foreach ($defaultSettings as $defaultSetting) {
            // Check if item name exists in default settings
            if (array_key_exists($itemName, $defaultSetting)) {
                $thisSettingItem = $defaultSetting[$itemName]['default'];
                // check if requested item exists in store configuration array
                if (array_key_exists($itemName, $storeConfiguration)) {
                    switch ($defaultSetting[$itemName]['data_type']) {
                        case 1:
                            $thisSettingItem = (string) $storeConfiguration[$itemName];
                            break;
                        case 2:
                            $thisSettingItem = (bool) $storeConfiguration[$itemName];
                            break;
                        case 3:
                            $thisSettingItem = (int) $storeConfiguration[$itemName];
                            break;
                        case 4:
                            if (array_get($defaultSetting, $itemName . '.hide_value', false)) {
                                try {
                                    $thisSettingItem = decrypt($storeConfiguration[$itemName]);
                                } catch (\Exception $e) {
                                    $thisSettingItem = json_encode([]);
                                }
                            } else {
                                $thisSettingItem = $storeConfiguration[$itemName];
                            }
                            $thisSettingItem = json_decode($thisSettingItem, true);
                            break;
                        case 6:
                            $thisSettingItem = (float) $storeConfiguration[$itemName];
                            break;
                        default:
                            $thisSettingItem = $storeConfiguration[$itemName];
                            break;
                    }
                }
                if ($thisSettingItem and array_get($defaultSetting, $itemName . '.hide_value', false) and (is_string($thisSettingItem) or is_numeric($thisSettingItem))) {
                    try {
                        $thisSettingItem = decrypt($thisSettingItem);
                    } catch (\Exception $e) {
                        __clog("Hint: You may have changed API_KEY and the value may be encrypted using old API_KEY, so the encrypted value provided as it is for $itemName");
                    }
                }
                // Return default value
                return $thisSettingItem;
            }
        }
        // Check if request for logo image url
        if ($itemName == 'logo_image_url') {
            $logoName = $storeConfiguration['logo_image'];
            $logoFilePath = getPathByKey('vendor_logo', [
                '{_uid}' => $vendorUid,
            ]) . '/' . $logoName;
            $logoImageUrl = getMediaUrl($logoFilePath);
            if (! $logoImageUrl) {
                $logoImageUrl = '';
            }

            return $logoImageUrl;
        }


        // Check if request for favicon url
        if ($itemName == 'favicon_image_url') {
            $faviconName = getVendorSettings('favicon_name');
            $faviconFilePath = getPathByKey('vendor_favicon', [
                '{_uid}' => $vendorUid,
            ]) . '/' . $faviconName;
            $faviconImageUrl = getMediaUrl($faviconFilePath);
            if (! $faviconImageUrl) {
                $faviconImageUrl = asset('imgs/' . configItem('favicon_name'));
            }

            return $faviconImageUrl;
        }

        if (array_key_exists($itemName, $storeConfiguration)) {
            return $storeConfiguration[$itemName];
        }

        return null;
    }
}

/**
 * Get Media Path
 *
 * @param  string  $name
 * @return void
 *---------------------------------------------------------------- */
if (! function_exists('getMediaUrl')) {
    function getMediaUrl($storagePath, $filename = '', $options = [])
    {
        $options = array_merge([
            'force_public_local_disk' => false,
        ], $options);
        // Check if already URL is given then return URL
        if (starts_with($filename, ['http://', 'https://'])) {
            return $filename;
        }
        // check if filename not exists
        if ($filename) {
            $separator = '/';
            if (substr($storagePath, -1) == '/') {
                $separator = '';
            }
            $storagePath .= $separator . $filename;
        }
        if ($options['force_public_local_disk'] === true) {
            $currentFileSystemDriver = 'public-media-storage';
        } else {
            $currentFileSystemDriver = config('filesystems.default', 'public-media-storage');
        }
        $storagePath = cleanPath($storagePath);
        // check if current file system driver is public
        if ($currentFileSystemDriver == 'public-media-storage') {
            $assetUrl = asset($storagePath);
            if (config('app.debug')) {
                return config('__misc.ngrok_url') ? strtr($assetUrl, [
                    request()->isSecure() ? secure_asset('/') : asset('/') => config('__misc.ngrok_url'),
                ]) : $assetUrl;
            }

            return $assetUrl;
        } else {
            $currentDisc = YesFileStorage::on($currentFileSystemDriver);
            // check if file is exists
            if (config("filesystems.disks.$currentFileSystemDriver.full_url")) {
                return config("filesystems.disks.$currentFileSystemDriver.full_url", asset('/')) . $storagePath;
            }
            return config("filesystems.disks.$currentFileSystemDriver.url", asset('/')) . $storagePath;
        }

        return null;
    }
}

/**
 * Get restriction for media
 *
 * @param  string  $activity
 * @return void.
 *-------------------------------------------------------- */
if (! function_exists('getMediaRestriction')) {
    function getMediaRestriction($mediaType, $encoded = true)
    {
        $mediaConfiguration = config('yes-file-storage.element_config');
        $allowedExtension = array_get($mediaConfiguration, $mediaType, null);
        // Check if allowed extension exists
        if (! __isEmpty($allowedExtension)) {
            $mediaRestriction = array_get($allowedExtension, 'restrictions.allowedFileTypes');
            if ($encoded) {
                return json_encode($mediaRestriction);
            }

            return $mediaRestriction;
        }

        return false;
    }
}

/**
 * Generate currency array.
 *
 * @param  string  $pageType
 * @param  array  $inputData
 * @return array
 *---------------------------------------------------------------- */
if (! function_exists('combineArray')) {
    function combineArray(&$defaultArray, &$dbArray)
    {
        $merged = $defaultArray;

        foreach ($dbArray as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = combineArray($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}

/**
 * return formatted price
 *
 * @param  float  $amount
 * @return float
 *---------------------------------------------------------------- */
if (! function_exists('formatAmount')) {
    function formatAmount($amount = null, $currencyCode = false, $currencySymbol = false, $options = [])
    {
        if ($currencyCode === true) {
            $currencyCode = ' ' . getCurrency();
        } elseif ($currencyCode === false) {
            $currencyCode = '';
        } else {
            $currencyCode = ' ' . $currencyCode;
        }

        if ($currencySymbol === true) {
            $currencySymbol = getCurrencySymbol();
        } elseif ($currencySymbol === false) {
            $currencySymbol = '';
        }

        $formattedCurrency = html_entity_decode($currencySymbol) . number_format((float) $amount, 2) . $currencyCode;

        return __tr($formattedCurrency);
    }
}

if (! function_exists('formatVendorAmount')) {
    /**
     * Format currency for vendor
     *
     * @param  number  $amount
     * @return string
     */
    function formatVendorAmount($amount, $forVendorIdOrUid = null)
    {
        return formatAmount($amount, getVendorSettings('currency', null, null, $forVendorIdOrUid), getVendorSettings('currency_symbol', null, null, $forVendorIdOrUid));
    }
}

/**
 * get set currency
 *
 * @return string
 *---------------------------------------------------------------- */
if (! function_exists('getCurrency')) {
    function getCurrency()
    {
        return html_entity_decode(getAppSettings('currency_value'));
    }
}

if (! function_exists('getCurrencySymbol')) {
    /**
     * get set currency Symbol
     *
     * @return string
     *---------------------------------------------------------------- */
    function getCurrencySymbol()
    {
        return html_entity_decode(getAppSettings('currency_symbol'));
    }
}

if (! function_exists('getVendorCurrencySymbol')) {
    /**
     * get set currency Symbol
     *
     * @return string
     *---------------------------------------------------------------- */
    function getVendorCurrencySymbol()
    {
        return html_entity_decode(getVendorSettings('currency_symbol'));
    }
}

if (! function_exists('getVendorCurrency')) {
    /**
     * get set currency Symbol
     *
     * @return string
     *---------------------------------------------------------------- */
    function getVendorCurrency()
    {
        return getVendorSettings('currency');
    }
}

if (! function_exists('getConfigPaidPlans')) {
    /**
     * Get the paid plans from config. use . for nesting
     *
     * @param  string|int  $plansItem
     * @return mixed
     */
    function getConfigPaidPlans($plansItem = null)
    {
        if ($plansItem) {
            return getConfigPlans('paid.' . $plansItem);
        }

        return getConfigPlans('paid');
    }
}

if (! function_exists('getConfigFreePlan')) {
    /**
     * Get the paid plans from config. use . for nesting
     *
     * @param  string|int  $plansItem
     * @return mixed
     */
    function getConfigFreePlan($plansItem = null)
    {
        if ($plansItem) {
            return getConfigPlans('free.' . $plansItem);
        }

        return getConfigPlans('free');
    }
}

if (! function_exists('getPaidPlans')) {
    /**
     * Get the paid plans from config. use . for nesting
     *
     * @param  string|int  $plansItem
     * @return mixed
     */
    function getPaidPlans($plansItem = null)
    {
        $configPaidPlans = getConfigPaidPlans();
        $settingsPlans = getAppSettings('subscription_plans');
        $storedPaidPlan = array_get($settingsPlans, 'paid', []);
        foreach ($storedPaidPlan as $extendedPlanKey => $extendedPlanValue) {
            foreach ($extendedPlanValue['charges'] as $extendedPlansChargesKey => $extendedPlansFeatureValue) {
                if (isset($storedPaidPlan[$extendedPlanKey]['charges'][$extendedPlansChargesKey]['title'])) {
                    unset($storedPaidPlan[$extendedPlanKey]['charges'][$extendedPlansChargesKey]['title']);
                }
            }
        }

        $extendedPlans = arrayExtend($configPaidPlans, $storedPaidPlan);
        $extendedPlans = array_filter($extendedPlans, function ($item) use ($configPaidPlans) {
            if (isset($item['id']) and in_array($item['id'], array_keys($configPaidPlans))) {
                return true;
            }
        });
        // crosscheck features
        foreach ($extendedPlans as $extendedPlanKey => $extendedPlanValue) {
            foreach ($extendedPlanValue['features'] as $extendedPlansFeatureKey => $extendedPlansFeatureValue) {
                if (! array_key_exists($extendedPlansFeatureKey, $configPaidPlans[$extendedPlanKey]['features'])) {
                    unset($extendedPlans[$extendedPlanKey]['features'][$extendedPlansFeatureKey]);
                }
            }
        }
        if ($plansItem) {
            return array_get($extendedPlans, $plansItem);
        }

        return $extendedPlans;
    }
}

if (! function_exists('getConfigPlans')) {
    /**
     * Get the paid plans from config. use . for nesting
     *
     * @param  string|int  $plansItem
     * @return mixed
     */
    function getConfigPlans($plansItem = null)
    {
        if ($plansItem) {
            return config('lw-plans.' . $plansItem);
        }

        return config('lw-plans');
    }
}

if (! function_exists('getPlans')) {
    /**
     * Get the paid plans from config. use . for nesting
     *
     * @param  string|int  $plansItem
     * @return mixed
     */
    function getPlans($plansItem = null)
    {
        $configPaidPlans = getConfigPlans();
        $settingsPlans = getAppSettings('subscription_plans');
        $extendedPlans = arrayExtend($configPaidPlans, $settingsPlans);
        if ($plansItem) {
            return array_get($extendedPlans, $plansItem);
        }

        return $extendedPlans;
    }
}
if (! function_exists('getFreePlan')) {
    /**
     * Get the paid plans from config. use . for nesting
     *
     * @param  string|int  $plansItem
     * @return mixed
     */
    function getFreePlan($plansItem = null)
    {
        $configFreePlan = getConfigFreePlan();
        $settingsPlans = getAppSettings('subscription_plans');
        $extendedItem = arrayExtend($configFreePlan, array_get($settingsPlans, 'free', []));
        foreach ($extendedItem['features'] as $extendedItemFeatureKey => $extendedItemFeatureValue) {
            if (! array_key_exists($extendedItemFeatureKey, $configFreePlan['features'])) {
                unset($extendedItem['features'][$extendedItemFeatureKey]);
            }
        }

        if ($plansItem) {
            return array_get($extendedItem, $plansItem);
        }

        return $extendedItem;
    }
}

if (! function_exists('getVendorCurrentActiveSubscription')) {
    /**
     * Get current active subscription
     *
     * @return null|Eloquent
     */
    function getVendorCurrentActiveSubscription($vendorId)
    {
        return viaFlashCache('current_user_active_subscription_vendor_' . $vendorId, function () use (&$vendorId) {
            $stripeSubscription =  Subscription::query()->where('vendor_model__id', $vendorId)->active()->first();
            if (__isEmpty($stripeSubscription)) {
                $stripeSubscription = ManualSubscriptionModel::query()
                    ->where('vendors__id', $vendorId)
                    ->where('status', 'active')
                    ->latest()   // defaults to ORDER BY created_at DESC
                    ->first();
            }
            return $stripeSubscription;
        });
    }
}

if (! function_exists('getVendorCurrentAutoCreatedSubscription')) {
    /**
     * Get current active subscription
     *
     * @return null|Eloquent
     */
    function getVendorCurrentAutoCreatedSubscription($vendorId = null, $status = 'initiate')
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        return ManualSubscriptionModel::where([
            'vendors__id' => $vendorId,
            'status' => $status,
            'is_auto_recurring' => 1
        ])->latest()->first();
    }
}

if (! function_exists('vendorPlanDetails')) {
    /**
     * Get the subscription details of the vendor
     *
     * @param  string  $feature
     * @param  int|string  $currentUsage
     * @param  int  $vendor  - Vendor id
     * @return object
     */
    function vendorPlanDetails($feature = null, $currentUsage = null, $vendor = null, $options = [])
    {
        $options = array_merge([
            'plan_id' => null,
            'expiry_check' => true,
        ], $options);
        $featureLimitCount = 0;
        $limitDuration = '';
        $featureDescription = '';
        $detailsContainer = [
            'has_active_plan' => false,
            'plan_type' => null,
            'is_limit_available' => false,
            'feature' => $feature,
            'current_usage' => $currentUsage,
            'message' => __tr('Available'),
            'plan_feature_limit' => 0,
            'subscription_type' => 'free',
            'frequency' => null,
            'ends_at' => null,
            'plan_id' => $options['plan_id'],
            'plan_key' => null,
        ];
        $isAvailable = 1;

        if (! $vendor) {
            $vendor = getVendorId();
        }

        $subscription = getVendorCurrentActiveSubscription($vendor);
        if (__isEmpty($subscription) and !$options['plan_id']) {
            $getFreePlan = getFreePlan();
            if (! __isEmpty($getFreePlan) and $getFreePlan['enabled']) {
                $featureLimitCount = (int) getFreePlan("features.$feature.limit");
                $limitDuration = getFreePlan("features.$feature.limit_duration");
                $featureDescription = getFreePlan("features.$feature.description");
                $detailsContainer['has_active_plan'] = true;
                $detailsContainer['plan_type'] = 'free';
                $detailsContainer['plan_title'] = getFreePlan("title");
            } else {
                $isAvailable = 0;
            }
        } else {
            $detailsContainer['subscription_type'] = 'auto';
            $detailsContainer['has_active_plan'] = true;
            $planId = $subscription->type ?? null;
            if ($subscription->plan_id ?? null) {
                $planId = $subscription->plan_id;
                $detailsContainer['subscription_type'] = 'manual';
            }
            if ($options['plan_id']) {
                $planId = $options['plan_id'];
            }
            $detailsContainer['frequency'] = null;
            $featureLimitCount = (int) getPaidPlans("{$planId}.features.$feature.limit");
            $limitDuration = getPaidPlans("{$planId}.features.$feature.limit_duration");
            $featureDescription = getPaidPlans("{$planId}.features.$feature.description");
            $detailsContainer['plan_type'] = 'paid';
            $detailsContainer['plan_title'] = getPaidPlans("{$planId}.title");
            $planCharges = getPaidPlans("{$planId}.charges");

            if (!__isEmpty($planCharges)) {
                foreach ($planCharges as $chargesKey => $chargesValue) {
                    if ($chargesValue['price_id'] == ($subscription->stripe_price ?? null)) {
                        $detailsContainer['frequency'] = $chargesKey;
                        break;
                    }
                }
            }
        }
        if ($detailsContainer['has_active_plan'] === false) {
            $detailsContainer['message'] = __tr('There is no active plan, please check your subscription');
        }
        // available if set as unlimited
        if ($featureLimitCount === -1) {
            $isAvailable = 1;
            $detailsContainer['message'] = __tr('Available Unlimited');
        }
        // may over usages
        elseif ($currentUsage > $featureLimitCount) {
            $isAvailable = -1;
            if ($detailsContainer['has_active_plan'] === true) {
                $detailsContainer['message'] = __tr('You used up your __limitDuration__ plan allowed __resourceLimit__ limit, please upgrade your plan.', [
                    '__resourceLimit__' => $featureDescription,
                    '__limitDuration__' => $limitDuration,
                ]);
            }
        }
        if ($subscription->charges_frequency ?? null) {
            $detailsContainer['frequency'] = $subscription->charges_frequency ?? null;
        }

        $detailsContainer['is_limit_available'] = (int) $isAvailable > 0 ? true : false;
        $detailsContainer['plan_feature_limit'] = $featureLimitCount;
        $detailsContainer['ends_at'] = $subscription->ends_at ?? null;
        $detailsContainer['is_expired'] = $detailsContainer['ends_at'] ? ($detailsContainer['ends_at'] < now()) : null;
        $detailsContainer['is_expiring'] = (($detailsContainer['plan_type'] != 'free') and !$detailsContainer['is_expired'] and ($detailsContainer['ends_at'] ? ($detailsContainer['ends_at'] < now()->addDays(7)) : null));
        $detailsContainer['plan_id'] = $options['plan_id'] ?? $subscription->type ?? $subscription->plan_id ??  null;
        $detailsContainer['plan_key'] = $detailsContainer['plan_id'] . '___' . $detailsContainer['frequency'];
        // restrict everything if plan has expired
        if (($detailsContainer['plan_type'] != 'free') and ($options['expiry_check'] and $detailsContainer['is_expired'])) {
            $detailsContainer['is_limit_available'] = false;
            $detailsContainer['has_active_plan'] = false;
            $detailsContainer['message'] = __tr('Your subscription plan has been expired, Please renew your subscription plan.');
        }
        return new SubscriptionPlanDetails($detailsContainer);
    }
}

/**
 * activate sidebar link by alias
 *
 * @param  string  $alias
 *-----------------------------------------------------------------------*/
if (! function_exists('markAsActiveLink')) {
    function markAsActiveLink($alias)
    {
        if (Route::getCurrentRoute()->getName() == $alias) {
            return ' active ';
        }
    }
}

/**
 * Auth Uid
 */
if (! function_exists('authUID')) {
    function authUID()
    {
        if (Auth::check()) {
            return Auth::user()->_uid;
        }

        return false;
    }
}

/**
 * Auth id
 */
if (! function_exists('getUserID')) {
    function getUserID()
    {
        $user = Auth::user();
        if (! __isEmpty($user)) {
            return $user->_id;
        }

        return null;
    }
}

/**
 * Auth uid
 */
if (! function_exists('getUserUID')) {
    function getUserUID()
    {
        if (Auth::check()) {
            return Auth::user()->_uid;
        }

        return false;
    }
}

/**
 * Auth uid
 */
if (! function_exists('formSwitchValue')) {
    /**
     * Switchery Value
     *
     * @param  string|int  $value
     * @return int
     */
    function formSwitchValue(&$value)
    {
        return (isset($value) and $value and ($value == 'on')) ? 1 : 0;
    }

    if (! function_exists('compareAmount')) {
        /**
         * Compare the amount
         * Note: don't know why the float casting is not working with comparison properly
         * so thats why casted as string for the comparison
         *
         * @return bool
         *---------------------------------------------------------------- */
        function compareAmount($amount, $otherAmount)
        {
            return (string) $amount == (string) $otherAmount;
        }
    }
}

/**
 * Get active translation languages
 *
 * @return array.
 *-------------------------------------------------------- */
if (! function_exists('getActiveTranslationLanguages')) {
    function getActiveTranslationLanguages()
    {
        $translationLanguages = getAppSettings('translation_languages');
        if (__isEmpty($translationLanguages)) {
            $translationLanguages = [];
        }
        /* $translationLanguages['en'] = [
            'id' => 'en',
            'name' => __tr('English'),
            'is_rtl' => false,
            'status' => true
        ]; */
        $translationLanguages[config('__tech.default_translation_language.id', 'en')] = configItem('default_translation_language');

        return array_where($translationLanguages, function ($languageItem) {
            if ($languageItem['status'] !== false) {
                return $languageItem;
            }
        });
    }
}

/**
 * Add activity log entry
 *
 * @param  string  $activity
 * @param  array  $data
 * @return mixed.
 *-------------------------------------------------------- */
if (! function_exists('activityLog')) {
    function activityLog($activity, $data = [])
    {
        $activityRepo = new ActivityLogRepository();

        return $activityRepo->storeIt([
            'user_id' => getUserID(),
            'user_role_id' => getUserAuthInfo('role_id'),
            'vendor_id' => getVendorId(),
            'activity' => [
                'message' => $activity,
                'data' => json_encode($data),
            ],
        ]);
    }
}

/**
 * Get demo mode for Demo of site
 *
 * @return bool.
 *-------------------------------------------------------- */
if (! function_exists('getContactDataMaps')) {
    function getContactDataMaps()
    {
        $contactCustomFieldRepository = new ContactCustomFieldRepository();
        $vendorContactCustomFields = $contactCustomFieldRepository->fetchItAll([
            'vendors__id' => getVendorId(),
        ], [
            '_id',
            'input_name',
        ]);
        return array_merge(configItem('contact_data_mapping'), Arr::mapWithKeys($vendorContactCustomFields->toArray(), function (array $item, int $key) {
            return ['contact_custom_field_' . $item['_id'] => $item['input_name']];
        }));
    }
}

/**
 * Country code
 *
 * @return bool
 *---------------------------------------------------------------- */
if (! function_exists('getCountryPhoneCodes')) {
    function getCountryPhoneCodes($indexBy = '_id')
    {
        return \App\Yantrana\Support\Country\Models\Country::select(
            'name',
            'phone_code',
            '_id'
        )->whereNot('phone_code', 0)->whereNotNull('phone_code')->get()->keyBy($indexBy)->toArray();
    }
}
/**
 * Get country by name
 *
 * @return bool
 *---------------------------------------------------------------- */
if (! function_exists('getCountryIdByName')) {
    function getCountryIdByName($name = null)
    {
        if (!$name) {
            return null;
        }
        return \App\Yantrana\Support\Country\Models\Country::select(
            '_id',
            'name',
        )->whereNot('phone_code', 0)->whereNotNull('phone_code')->where('name', $name)->first()?->_id;
    }
}
/**
 * Get country by name
 *
 * @return int
 *---------------------------------------------------------------- */
if (! function_exists('findRequestedCountryId')) {
    function findRequestedCountryId($name)
    {
        return \App\Yantrana\Support\Country\Models\Country::select(
            '_id',
            'name',
        )->whereAny([
            'name',
            'iso_code',
            'name_capitalized',
            'phone_code',
        ], 'LIKE', $name)->whereNot('phone_code', 0)->whereNotNull('phone_code')->first()?->_id ?? null;
    }
}

if (! function_exists('updateModelsViaVendorBroadcast')) {
    /**
     * Prepare and send data for client models update
     *
     * @param string $vendorUid
     * @param array $data
     * @return array|null
     */
    function updateModelsViaVendorBroadcast(string $vendorUid, array $data)
    {
        return event(new VendorChannelBroadcast($vendorUid, [
            'eventModelUpdate' => $data
        ]));
    }
}
if (! function_exists('reloadViewViaVendorBroadcast')) {
    /**
     * Prepare and send data for client models update
     *
     * @param string $vendorUid
     * @param array $data
     * @return array|null
     */
    function reloadViewViaVendorBroadcast(string $vendorUid)
    {
        return event(new VendorChannelBroadcast($vendorUid, [
            'reload' => true
        ]));
    }
}
if (! function_exists('getViaSharedUrl')) {
    /**
     * Get the url via Ngrok shared url
     *
     * @param string $webhookUrl

     * @return string|null
     */
    function getViaSharedUrl(string $webhookUrl)
    {
        if (config('app.debug')) {
            $webhookUrl = config('__misc.ngrok_url') ? strtr($webhookUrl, [
                (request()->isSecure() ? secure_url('/') : url('/')) . '/' => config('__misc.ngrok_url'),
            ]) : $webhookUrl;
        }
        return $webhookUrl;
    }
}
if (! function_exists('maskForDemo')) {
    /**
     * Mask the items for demo as requested
     *
     * @param string|int|float|null $item
     * @param string $itemType
     * @return array|null
     */
    function maskForDemo(string|int|float|null $item, string $itemType = 'phone', $isDemoMode = null)
    {
        $isDemoMode = ($isDemoMode === null) ? isDemo() : $isDemoMode;
        return $isDemoMode ? '-- ' . __tr('Masked for Demo') . ' --' : $item;
    }
}


if (! function_exists('isDemoVendorAccount')) {
    /**
     * Check if logged in account is for demo
     *
     * @return boolean
     */
    function isDemoVendorAccount()
    {
        return config('laraware.demo_account_id') == getVendorId();
    }
}
if (! function_exists('isThisDemoVendorAccountAccess')) {
    /**
     * Check if logged in account is for demo
     *
     * @return boolean
     */
    function isThisDemoVendorAccountAccess()
    {
        return (isDemo() and isDemoVendorAccount() and hasVendorAccess());
    }
}


if (! function_exists('isWhatsAppBusinessAccountReady')) {
    /**
     * Check if whatsapp business account setup and ready to use
     *
     * @return boolean
     */
    function isWhatsAppBusinessAccountReady($vendorIdOrUid = null)
    {
        if (!$vendorIdOrUid) {
            $vendorIdOrUid = getVendorId();
        }
        return getVendorSettings('facebook_app_id', null, null, $vendorIdOrUid)
            and getVendorSettings('whatsapp_access_token', null, null, $vendorIdOrUid)
            and getVendorSettings('whatsapp_business_account_id', null, null, $vendorIdOrUid)
            and getVendorSettings('current_phone_number_number', null, null, $vendorIdOrUid)
            and getVendorSettings('current_phone_number_id', null, null, $vendorIdOrUid)
            and getVendorSettings('webhook_verified_at', null, null, $vendorIdOrUid)
            and !getVendorSettings(
                'whatsapp_access_token_expired',
                null,
                null,
                $vendorIdOrUid
            );
    }
}

if (! function_exists('hasVendorPermission')) {
    /**
     * Check if logged in user is vendor admin or has permission
     *
     * @return boolean
     */
    function hasVendorPermission($permission)
    {
        return hasVendorAccess() ?: getUserAuthInfo('permissions');
    }
}
if (! function_exists('getListOfPermissions')) {
    /**
     * Check if logged in user is vendor admin or has permission
     *
     * @return array
     */
    function getListOfPermissions()
    {
        return require(app_path('Yantrana/Components/User/Support/permissions.php'));
    }
}
if (! function_exists('isValidUrl')) {
    /**
     * Check if the given string is a valid URL.
     *
     * @param  string  $url
     * @return bool
     */
    function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
if (! function_exists('createUpiLink')) {
    /**
     * Check if the given string is a valid URL.
     *
     * @param  string  $url
     * @return bool
     */
    function createUpiLink($upiId, $payeeName, $amount, $transactionRef, $transactionNote = "")
    {
        $upiLink = "upi://pay?";
        $upiLink .= "pa=" . urlencode($upiId);
        $upiLink .= "&pn=" . urlencode($payeeName);
        $upiLink .= "&tr=" . urlencode($transactionRef);
        $upiLink .= "&tn=" . urlencode($transactionNote);
        $upiLink .= "&am=" . urlencode($amount);
        $upiLink .= "&cu=INR";
        return $upiLink;
    }
}
if (! function_exists('getPendingSubscriptionCount')) {
    /**
     * Get the count of the pending requests for the manual subscription
     *
     * @return int
     */
    function getPendingSubscriptionCount()
    {
        return viaFlashCache('pending_subscriptions_count', function () {
            if (!hasCentralAccess()) {
                return 0;
            }
            $manualSubscriptionRepository = new ManualSubscriptionRepository();
            return $manualSubscriptionRepository->countIt([
                'status' => 'pending',
            ]);
        });
    }
}
if (! function_exists('ignoreFacebookApiError')) {
    /**
     * Set the config item so we can prevent abort for the facebook error
     *
     * @return boolean
     */
    function ignoreFacebookApiError($state = null)
    {
        if ($state === null) {
            return config('app.ignore_facebook_api_error');
        }
        return config([
            'app.ignore_facebook_api_error' => $state
        ]);
    }
}
if (! function_exists('fromPhoneNumberIdForRequest')) {
    /**
     * Set the phone number id for current request
     *
     * @return int|string
     */
    function fromPhoneNumberIdForRequest($phoneNumberId = null)
    {
        if ($phoneNumberId === null) {
            return config('app.from_phone_number_id');
        }
        return config([
            'app.from_phone_number_id' => $phoneNumberId
        ]);
    }
}
if (! function_exists('cleanDisplayPhoneNumber')) {
    /**
     * Clear display phone number
     *
     * @return string
     */
    function cleanDisplayPhoneNumber($phoneNumber)
    {
        $result = $phoneNumber ? preg_replace('/\D/', '', $phoneNumber) : null;
        if ($result) {
            return ltrim($result, '0');
        }
        return $result;
    }
}

if (! function_exists('formatWhatsAppText')) {
    /**
     * Format WhatsApp Message
     * @param string $text
     *
     * @return string
     */
    function formatWhatsAppText($text)
    {
        // Bold: Wrap text marked with * in <strong> tags
        $text = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', $text);

        // Italics: Wrap text marked with _ in <em> tags
        $text = preg_replace('/\_(.*?)\_/', '<em>$1</em>', $text);

        // Strikethrough: Wrap text marked with ~ in <del> tags
        $text = preg_replace('/\~(.*?)\~/', '<del>$1</del>', $text);

        // Monospace: Wrap text marked with ``` in <code> tags
        // Use preg_quote to escape backticks for the pattern
        $backtickPattern = preg_quote('```', '/');
        $text = preg_replace("/{$backtickPattern}(.*?){$backtickPattern}/s", '<code>$1</code>', $text);

        // Single backtick: Replace with <span> tags
        $text = preg_replace('/`(.*?)`/', '<span class="badge badge-light">$1</span>', $text);

        // Convert URLs to clickable links, YouTube
        $text = preg_replace_callback(
            '/(https?:\/\/[^\s]+)/',
            function ($matches) {
                $url = $matches[0];
                // YouTube URL
                if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w\-]+)/', $url, $youtubeMatches)) {
                    return '<iframe width="100%" height="300" src="https://www.youtube.com/embed/' . $youtubeMatches[1] . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></br></br>' . '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                } else {
                    return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                }
            },
            $text
        );
        // Convert email addresses to mailto links
        $text = preg_replace(
            '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/',
            '<a href="mailto:$1">$1</a>',
            $text
        );

        return $text;
    }
}
/**
 * Get the technical items from tech items
 *
 * @param  string  $key
 * @param  mixed  $requireKeys
 * @return mixed
 *-------------------------------------------------------- */
if (! function_exists('slugIt')) {
    function slugIt($title, $separator = '-')
    {
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';

        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return Str::slug(strtolower(trim($title, $separator)));
    }
}

if (! function_exists('getActivePages')) {
    /**
     * Get pageData
     *
     * @return mixed
     */
    function getActivePages()
    {

        $pageRepo = new PageRepository();
        return $pageRepo->fetchItAll([
            'status' => 1,
            'show_in_menu' => 1,
        ], [
            'title',
            'status',
            'slug',
            '_uid'
        ]);
    }
}

if (! function_exists('dispatchVendorWebhook')) {
    /**
     * Dispatch vendor Webhook
     *
     * @return mixed
     */
    function dispatchVendorWebhook($vendorId, $payload = [])
    {
        $payload = array_merge([
            'contact' => null,
            'message' => [
                'whatsapp_business_phone_number_id' => null,
                'whatsapp_message_id' => null,
                'replied_to_whatsapp_message_id' => null,
                'is_new_message' => null,
                'body' => null,
                'status' => null,
                'media' => null,
            ],
            'whatsapp_webhook_payload' => null,
        ], $payload);
        $vendorPlanDetails = vendorPlanDetails('api_access', 1, $vendorId);
        if ($vendorPlanDetails['is_limit_available'] and getVendorSettings('enable_vendor_webhook', null, null, $vendorId) and ($vendorWebhookEndpoint = getVendorSettings('vendor_webhook_endpoint', null, null, $vendorId))) {
            try {
                Http::post($vendorWebhookEndpoint, $payload); //->throw();
            } catch (\Throwable $th) {
                // __logDebug('Webhook error:');
                // __logDebug($th->getMessage());
            }
        }
    }
}
if (! function_exists('setRedirectAlertMessage')) {
    /**
     * Set alert redirect message
     *
     * @return void
     */
    function setRedirectAlertMessage($message, $messageType = 'info')
    {
        session()->flash('alertMessage', $message);
        session()->flash('alertMessageType', $messageType);
    }
}

if (! function_exists('swaksharyipadtalni')) {
    function swaksharyipadtalni()
    {
        return (getAppSettings(base64_decode('cHJvZHVjdF9yZWdpc3RyYXRpb24='), base64_decode('cmVnaXN0cmF0aW9uX2lk')) and (sha1(array_get($_SERVER, base64_decode('SFRUUF9IT1NU'), '') . getAppSettings(base64_decode('cHJvZHVjdF9yZWdpc3RyYXRpb24='), base64_decode('cmVnaXN0cmF0aW9uX2lk')) . base64_decode('NC41Kw==')) === getAppSettings(base64_decode('cHJvZHVjdF9yZWdpc3RyYXRpb24='), base64_decode('c2lnbmF0dXJl'))));
    }
}
if (! function_exists('swaksharyipadtalniforadditionals')) {
    function swaksharyipadtalniforadditionals($item)
    {
        $forItem = 'l' . 'w' . 'A' . 'd' . 'd' . 'o' . 'n' . $item;
        return (getAppSettings($forItem, base64_decode('cmVnaXN0cmF0aW9uX2lk')) and (sha1(array_get($_SERVER, base64_decode('SFRUUF9IT1NU'), '') . getAppSettings($forItem, base64_decode('cmVnaXN0cmF0aW9uX2lk')) . base64_decode('MS4wKw==')) === getAppSettings($forItem, base64_decode('c2lnbmF0dXJl'))));
    }
}

if (! function_exists('darkenColorValue')) {
    // Darken a hex color by a percentage (0-100)
    function darkenColorValue($hexColor, $percent = 50)
    {
        // Strip '#' if present
        $hexColor = str_replace('#', '', $hexColor);
        // Convert hex to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        // Calculate the darkening percentage
        $r = max(0, min(255, $r - ($r * ($percent / 100))));
        $g = max(0, min(255, $g - ($g * ($percent / 100))));
        $b = max(0, min(255, $b - ($b * ($percent / 100))));
        // Convert back to hex and return the darkened color
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}
/**
 * Check request coming from mobile app
 *
 * @return number.
 *-------------------------------------------------------- */
if (! function_exists('isMobileAppRequest')) {
    function isMobileAppRequest()
    {
        //Check request coming from mobile app
        return request()->header('Api-Request-Signature') === 'mobile-app-request';
    }
}

/**
 * sets the Authentication token (jwt)
 *
 * @return boolean.
 *-------------------------------------------------------- */
if (! function_exists('setAccessToken')) {
    function setAccessToken($token)
    {
        //set token
        config(['app.additional' => [
            'token_refreshed' => $token,
        ]]);
    }
}
/**
 * Check if AI bot available
 *
 * @return boolean.
 *-------------------------------------------------------- */
if (! function_exists('isAiBotAvailable')) {
    function isAiBotAvailable($vendorId = null)
    {
        if (!$vendorId) {
            $vendorId = getVendorId();
        }
        return (getVendorSettings('enable_flowise_ai_bot', null, null, $vendorId) and getVendorSettings('flowise_url', null, null, $vendorId)) or (getVendorSettings('enable_open_ai_bot', null, null, $vendorId) and getVendorSettings('open_ai_access_key', null, null, $vendorId));
    }
}

if (! function_exists('getDemoNumbersForTest')) {
    /**
     * Check if number is registered for demo
     *
     * @return void
     */
    function getDemoNumbersForTest($checkThisNumber = null, $returnString = false, $ignoreTestContact = false)
    {
        $demoAccountTestPhoneNumbers = [];
        if (!$ignoreTestContact) {
            $demoAccountTestPhoneNumbers = [
                config('__misc.demo_test_recipient_contact_number')
            ];
        }
        $collectedNumbers = [];
        if (isMobileAppRequest()) {
            $numbers = array_unique(array_filter(explode(',', request()->demo_phone_numbers)));
            if ($numbers) {
                foreach ($numbers as $phoneNumber) {
                    $numLength = strlen((string) $phoneNumber);
                    if (!is_numeric($phoneNumber) or $numLength < 9) {
                        continue;
                    }
                    $collectedNumbers[] = ltrim((string) ltrim((string) $phoneNumber, '+'), '0');
                }
            }
        }

        $demoAccountTestPhoneNumbers = array_unique(array_filter(array_merge($demoAccountTestPhoneNumbers, session('__demoAccountTestPhoneNumbers') ?: [], $collectedNumbers)));
        if ($checkThisNumber) {
            return in_array($checkThisNumber, $demoAccountTestPhoneNumbers);
        }
        if ($returnString) {
            return implode(',', $demoAccountTestPhoneNumbers);
        }
        return $demoAccountTestPhoneNumbers;
    }
}

if (! function_exists('whatsAppServiceEngine')) {
    /**
     * Get WhatsApp Service Engine Instance
     *
     * @return \App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine
     */
    function whatsAppServiceEngine()
    {
        return app()->make(\App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine::class);
    }
}

if (! function_exists('getUserAppTheme')) {
    /**
     * Get WhatsApp Service Engine Instance
     *
     * @return \App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine
     */
    function getUserAppTheme()
    {
        $themeOptions = configItem('theme_options');
        $appTheme = '';
        // Check if theme changes are allowed and if a custom theme exists in the session
        if (getAppSettings('allow_to_change_theme') and session()->has('users_current_app_theme')) {
            $appTheme = session('users_current_app_theme');
        }
        if (!(getAppSettings('allow_to_change_theme')) or ! $appTheme) {
            // Default theme from settings
            $appTheme = getAppSettings('current_app_theme');
        }
        return $appTheme;
    }
}
if (! function_exists('addOpacityToHex')) {
    /**
     * Add opacity to a 6-digit hex color.
     * $opacity is 0.0 to 1.0
     */
    function addOpacityToHex($hex = null, $opacity = 0)
    {
        if (!$hex) {
            return $hex;
        }
        // Remove leading #
        $hex = ltrim($hex, '#');

        // Validate length
        if (strlen($hex) !== 6) {
            return $hex;
            // throw new InvalidArgumentException("Hex color must be 6 characters.");
        }

        // Convert opacity to 0–255 then to 2-digit hex
        $alpha = round($opacity * 255);
        $alphaHex = str_pad(dechex($alpha), 2, '0', STR_PAD_LEFT);

        return "#{$hex}{$alphaHex}";
    }
}
if (! function_exists('logSystemVendorChatMessage')) {
    /**
     * Log System message in vendor chat
     *
     * @param eloquent $contact
     * @param string $action
     * @param string $dynamicTitle
     * @return eloquent|bool
     */
    function logSystemVendorChatMessage($contact, $action, $dynamicTitle)
    {
        return storeWhatsAppLogChatHistory([
            'status' => 'initialize',
            'contacts__id' => $contact->_id,
            'vendors__id' => $contact->vendors__id,
            'contact_wa_id' => $contact->wa_id,
            'is_system_message' => 1,
            'is_incoming_message' => 0,
            'messaged_at' => now(),
            '__data' => [
                'system_message_data' => [
                    'action' => $action,
                    'dynamicKey' => '__dynamicTitle__',
                    'dynamicValue' => $dynamicTitle
                ]
            ]
        ]);
    }
}

// WhatsAppMessageLogRepository
if (! function_exists('storeWhatsAppLogChatHistory')) {
    /**
     * Get WhatsApp Service Engine Instance
     *
     * @return \App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine
     */
    function storeWhatsAppLogChatHistory($inputData)
    {
        $whatsAppMessageLogRepository = new WhatsAppMessageLogRepository();

        return $whatsAppMessageLogRepository->storeIt($inputData);
    }
}

/**
 * Send FCM Single Device Notification
 *
 * @return void.
 *-------------------------------------------------------- */
if (! function_exists('sendFCMNotification')) {
    function sendFCMNotification($vendorId, $title, $body, $data = [])
    {
        $userDeviceRepository = new UserDeviceRepository();

        $deviceToken = $userDeviceRepository->fetchIt(['vendors__id' => $vendorId]);

        $firebaseResponses = [];
        // Check if user device token exists
        if (!__isEmpty($deviceToken)) {
            // Generate OAuth token from service account
            $accessToken = getAccessToken();

            $projectId = configItem('firebase_project_id');
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $deviceToken->device_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ],
            ];

            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            $cleanResponse = trim($response->body());
            $firebaseResponses[] = $firebaseResponse = json_decode($cleanResponse);

            if (isset($firebaseResponse->error)) {
                $error = $firebaseResponse->error->status ?? null;

                if (in_array($error, ['INVALID_ARGUMENT', 'NOT_FOUND', 'UNREGISTERED'])) {

                    // Delete invalid device token
                    $userDeviceRepository->deleteIt($deviceToken);
                }
            }
        }

        return $firebaseResponses;
    }
}

/**
 * Generate Access token for FCM Messaging
 *
 * @return void.
 *-------------------------------------------------------- */
if (! function_exists('getAccessToken')) {
    function getAccessToken()
    {
        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/service-account.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];
        return $accessToken;
    }
}

if (! function_exists('maskString')) {
    /**
     * Mask the items using permission
     *
     * @param string|int|float|null $item
     * @param string $itemType
     * @return array|null
     */
    function maskString(string|int|float|null $item, string $itemType = 'phone')
    {
        // Check if item exists
        if (__isEmpty($item) or getUserAuthInfo('role_id') === 2) {
            return $item;
        }

        // Check if masking for phone or email
        if ($itemType == 'phone' and hasVendorAccess('hide_contact_phone_numbers')) {
            return $item[0] . str_repeat('*', strlen($item) - 2) . $item[-1];
        } elseif ($itemType == 'email' and hasVendorAccess('hide_contact_emails')) {

            list($user, $domain) = explode('@', $item);

            // Mask username
            if (strlen($user) <= 2) {
                $maskedUser = substr($user, 0, 1) . '*';
            } else {
                $maskedUser = substr($user, 0, 1)
                    . str_repeat('*', strlen($user) - 2)
                    . substr($user, -1);
            }

            // Mask domain (everything except last char)
            $maskedDomain = str_repeat('*', strlen($domain) - 1) . substr($domain, -1);

            return $maskedUser . '@' . $maskedDomain;
        }

        return $item;
    }
}
