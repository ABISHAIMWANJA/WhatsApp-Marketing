<?php
namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessDeleteWhatsappMessages extends Command
{
    protected $signature = 'whatsapp-message:delete:process';
    protected $description = 'Delete all WhatsApp messages that are older than specified days from settings.';
    public function handle()
    {
        $enableAutomaticDeletion = getAppSettings('enable_automatic_message_deletion');
        // If automatic deletion is not enabled, exit the command.
        if(!$enableAutomaticDeletion) {
            return Command::SUCCESS;
        }
        $deleteMsgBeforeDays = getAppSettings('delete_whatsapp_message_days');
        $deleteMsgBeforeDate = Carbon::now()->subDays($deleteMsgBeforeDays);

        try {
            do {
                $affected = DB::table('whatsapp_message_logs')
                    ->where('created_at', '<', $deleteMsgBeforeDate)
                    ->whereNull('is_system_message')
                    ->whereNotNull('__data')
                    ->limit(1000)
                    ->update([
                        '__data' => null,
                        'message' => null
                    ]);

                usleep(200000); // throttle to reduce I/O pressure
            } while ($affected > 0);
        } catch (\Exception $e) {
            __logDebug($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
