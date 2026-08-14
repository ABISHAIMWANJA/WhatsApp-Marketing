<?php
// app/Console/Commands/ProcessWhatsAppWebhooks.php
namespace App\Console\Commands;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\HttpFoundation\HeaderBag;
use App\Yantrana\Components\WhatsAppService\WhatsAppServiceEngine;
use App\Yantrana\Components\WhatsAppService\Models\WhatsAppWebhookModel;

class ProcessWhatsAppWebhooks extends Command
{
    protected $signature = 'whatsapp:webhooks:process {--webhooksCount=100}';
    protected $description = 'Process pending webhooks';
    public function handle()
    {
        $webhooksCount = $this->option('webhooksCount') ?: 100;
        WhatsAppWebhookModel::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('attempted_at')
                ->orWhere('attempted_at', '<', now()->subMinutes(5)); // only records not attempted in last 5 minutes
            })
            ->latest()
            ->limit($webhooksCount)
            ->get()
            ->each(function ($webhook) {
                try {
                    // if attempted more than 25 minutes ago, delete it as it may had 5 attempts already
                    if ($webhook->attempted_at and $webhook->attempted_at->gt(
                        $webhook->created_at->copy()->addMinutes(25)
                    )) {
                        $webhook->delete();
                    } else {
                        $request = new Request(
                            query: [],
                            request: $webhook->payload,
                            attributes: [],
                            cookies: [],
                            files: [],
                            server: [],
                            content: json_encode($webhook->payload)
                        );
                        $request->headers = new HeaderBag($webhook->headers);
                        app()->make(WhatsAppServiceEngine::class)->processWebhookRequest($request, $webhook->vendors__id);
                        $webhook->delete();
                    }
                } catch (\Throwable $e) {
                    $errorMessage = trim($e->getMessage());
                    //__logDebug($errorMessage);
                    // throw $e;
                    if (str_starts_with($errorMessage, 'Unsupported')) {
                        $webhook->delete();
                    } else {
                        $webhook->update([
                            'status' => 'pending',
                            'attempted_at' => now(),
                        ]);
                    }
                }
            });
    }
}
