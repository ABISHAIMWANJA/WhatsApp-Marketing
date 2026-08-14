<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Yantrana\Components\Media\MediaEngine;

class ProcessDeleteVendorTempMedia extends Command
{
    protected $signature = 'vendor-temp-media:delete:process';
    protected $description = 'Delete all vendors temp media.';
    public function handle()
    {
        // Check if automatic deletion of vendor temp media is enabled, if not then exit the command.
        if(!getAppSettings('enable_automatic_delete_vendor_temp_media')) {
            return Command::SUCCESS;
        }
        try {
            $mediaEngine = new MediaEngine();
            // Delete all vendors temp media
            $mediaEngine->deleteAllVendorTempMedia();
        } catch (\Exception $e) {
            __logDebug($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
