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


use Illuminate\Support\Str;

// dummy method to supress the errors
if (! function_exists('T_gettext')) {
    function T_gettext($string)
    {
        return $string;
    }
}

if (! function_exists('T_ngettext')) {
    function T_ngettext($string, $string2, $int)
    {
        return $string;
    }
}
if (! function_exists('__tr')) {
    /**
     * String translations for gettext
     */
    function __tr(?string $string, array $replaceValues = [], bool $escapeInputString = true): string
    {
        if ($string === null) {
            return '';
        }
        $originalString = $string;
        $string = T_gettext($string);
        $string = $escapeInputString ? e($string) : $string;
        // replace inverted commas as required
        $replaceValues['&#039;'] = "'";
        $replaceValues[T_gettext('&#039;')] = "'";
        $replaceValues[T_gettext('&quot;')] = '"';
        // Check if replaceValues exist
        if (! empty($replaceValues) and is_array($replaceValues)) {
            $string = strtr($string, $replaceValues);
        }
        $configLocale = config('app.locale');
        if (! in_array($configLocale, [
            'en',
            'en_US',
        ]) and extension_loaded('intl')) {
            // if numbers found in string change those also.
            $string = preg_replace_callback('!\d+!', function ($matches) use ($configLocale) {
                if (class_exists('NumberFormatter')) {
                    $numberFormatter = new NumberFormatter(
                        $configLocale ? $configLocale : 'en_US',
                        NumberFormatter::IGNORE
                    );

                    // to prevent removing leading zeros added this trick
                    return preg_replace_callback('!\d!', function ($subMatches) use ($numberFormatter) {
                        return $numberFormatter->format($subMatches[0]);
                    }, $matches[0]);
                }
            }, $string);
            unset($matches);
        }
        // check if there is blank string after processed
        // in such case we may need to use original string
        if (! $string) {
            // Check if replaceValues exist
            if (! empty($replaceValues) and is_array($replaceValues)) {
                $originalString = strtr($originalString, $replaceValues);
            }

            return $originalString;
        }

        return $string;
    }
}

if (! function_exists('__trn')) {
    /**
     * Translation for Plurals
     */
    function __trn(string $string, string $string2 = '', int $int = 1, array $replaceValues = []): string
    {
        $int = (int) $int;
        $string = e(T_ngettext($string, $string2, $int));
        // Check if replaceValues exist
        if (! empty($replaceValues) and is_array($replaceValues)) {
            $string = strtr($string, $replaceValues);
        }
        if (extension_loaded('intl')) {
            // if numbers found in string change those also.
            $string = preg_replace_callback('!\d+!', function ($matches) {
                if (class_exists('NumberFormatter')) {
                    $numberFormatter = new NumberFormatter(
                        app()->getLocale(),
                        NumberFormatter::IGNORE
                    );

                    return $numberFormatter->format($matches[0]);
                }
            }, $string);

            unset($matches);
        }

        return $string;
    }
}
