<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Http;

class NotifyN8nOnQueueFailure
{
    public function handle(JobFailed $event): void
    {
        // Send a compact payload to n8n which will post to Slack
        $url = config('services.n8n.slack_failures') ?? env('N8N_SLACK_WEBHOOK_URL');
        if (! $url) return;

        $payload = [
            'connection' => $event->connectionName,
            'queue'      => optional($event->job)->getQueue(),
            'job'        => optional($event->job)->resolveName(),
            'exception'  => $event->exception->getMessage(),
        ];

        // Fire-and-forget; do not throw
        try { Http::timeout(5)->post($url, $payload); } catch (\Throwable $е) {}
    }
}
