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


namespace App\Yantrana\Services\PushBroadcast;

/**
 * Service Provider for PushBroadcast
 *-------------------------------------------------------- */

use Illuminate\Support\ServiceProvider;

class PushBroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Register 'pushbroadcast' instance container to our PushBroadcast object
        $this->app->singleton('pushbroadcast', function ($app) {
            return new \App\Yantrana\Services\PushBroadcast\PushBroadcast();
        });

        // Register Alias
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias(
                'PushBroadcast',
                \App\Yantrana\Services\PushBroadcast\PushBroadcastFacade::class
            );
        });
    }
}
