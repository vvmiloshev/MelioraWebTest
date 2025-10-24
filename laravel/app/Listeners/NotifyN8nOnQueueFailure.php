<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Listener that notifies the n8n workflow whenever a queued job fails permanently.
 *
 * PURPOSE:
 * - Helps n8n (or any external monitoring workflow) detect backend issues in real time.
 * - Should be registered in EventServiceProvider or auto-discovered.
 *
 * RECOMMENDATIONS:
 * - Send only safe, minimal context (job class, exception message).
 * - Use short timeouts and retry limits to prevent listener-induced failures.
 * - Avoid infinite failure loops (don’t dispatch another job inside this listener).
 */
class NotifyN8nOnQueueFailure
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function handle(JobFailed $event): void
    {
        // Extract useful job info.
        $jobName  = $event->job->resolveName();
        $exceptionMessage = $event->exception->getMessage();

        // Optional: include connection and queue name for observability.
        $context = [
            'job'       => $jobName,
            'connection'=> $event->connectionName,
            'queue'     => $event->job->getQueue(),
            'message'   => mb_strimwidth($exceptionMessage, 0, 1000, '…'), // truncate to safe length
        ];

        // Filter only relevant jobs (optional optimization).
        // For example, only report failures from SendToN8nJob:
        if (! str_contains($jobName, 'SendToN8nJob')) {
            return;
        }

        // Prepare webhook configuration.
        $baseUrl     = rtrim(config('services.n8n.base_url'), '/');
        $webhookPath = ltrim(config('services.n8n.failure_webhook', '/webhook/queue-failure'), '/');
        $url         = "{$baseUrl}/{$webhookPath}";
        $token       = config('services.n8n.token');

        // Avoid blocking the queue worker — set short timeout and no retries.
        try {
            $client = Http::acceptJson()
                ->asJson()
                ->timeout(5)
                ->retry(1, 100); // Retry once quickly if transient error.

            if ($token) {
                $client = $client->withToken($token);
            }

            // Send the notification payload.
            $response = $client->post($url, $context);

            // Log the attempt for debugging and traceability.
            Log::info('[NotifyN8nOnQueueFailure] Sent failure notice', [
                'url'     => $url,
                'status'  => $response->status(),
                'job'     => $jobName,
            ]);

            if ($response->failed()) {
                Log::warning('[NotifyN8nOnQueueFailure] n8n responded with error', [
                    'status'  => $response->status(),
                    'body'    => mb_strimwidth($response->body(), 0, 500, '…'),
                ]);
            }
        } catch (\Throwable $e) {
            // Never throw from a listener — just log locally.
            Log::error('[NotifyN8nOnQueueFailure] Exception during webhook call', [
                'job'     => $jobName,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
