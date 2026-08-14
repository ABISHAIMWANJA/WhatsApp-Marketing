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


namespace App\Yantrana\Services\YesFileStorage;

/**
 * Service Provider for YesFileStorage
 *-------------------------------------------------------- */

use Illuminate\Support\ServiceProvider;

class YesFileStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/yes-file-storage.php' => config_path('yes-file-storage.php'),
        ], 'yesfilestorage');

        // required YesFileStorage helpers & directives
        require __DIR__.'/support/helpers.php';
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        // Register 'YesFileStorage' instance container to our YesFileStorage object
        $this->app->singleton('yesfilestorage', function ($app) {
            return new \App\Yantrana\Services\YesFileStorage\YesFileStorage();
        });

        // Register Alias
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias(
                'YesFileStorage',
                \App\Yantrana\Services\YesFileStorage\YesFileStorageFacade::class
            );
        });
    }
}
