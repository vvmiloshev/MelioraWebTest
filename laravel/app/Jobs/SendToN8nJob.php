<?php

namespace App\Jobs;

use App\Models\AdScriptTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendToN8nJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public int $taskId;

    /**
     * NOTE: Keep the constructor lean; only pass identifiers, not whole models.
     * This improves serialization stability and avoids stale model state.
     */
    public function __construct(int $taskId)
    {
        // Store only the id; the model will be re-hydrated in handle().
        $this->taskId = $taskId;
    }

    /**
     * @return void
     *
     * INFO: The job is responsible for sending the payload to n8n.
     * - On success: do not change status here; the status will be updated by the callback endpoint.
     * - On error: mark the task as failed and persist error details to help debugging.
     */
    public function handle(): void
    {
        // Fetch the task fresh from the DB.
        $task = AdScriptTask::findOrFail($this->taskId);

        // Build the payload expected by n8n.
        // TIP: Keep the contract stable and documented in README, with example requests.
        $payload = [
            'task_id'            => $task->id,
            'reference_script'   => $task->reference_script,
            'outcome_description'=> $task->outcome_description,
            // Optionally include a correlation id for tracing across systems.
            'correlation_id'     => 'task-' . $task->id,
        ];

        // Read service config from config/services.php
        // SUGGESTION: Validate these at boot time or fail fast here with a clear log.
        $baseUrl      = rtrim(config('services.n8n.base_url'), '/');
        $webhookPath  = ltrim(config('services.n8n.webhook_path', '/webhook/ad-script-agent'), '/');
        $token        = config('services.n8n.token'); // if you use token-based auth
        $timeoutSec   = (int) (config('services.n8n.timeout', 20));

        // Defensive checks for critical config.
        if (!$baseUrl) {
            throw new \RuntimeException('Missing services.n8n.base_url configuration.');
        }

        // Prepare HTTP client with sane defaults.
        $client = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeoutSec)
            // Retry only on connection/5xx errors; not on 4xx.
            // NOTE: jitter (100 ms) helps avoid thundering herd if many jobs retry together.
            ->retry(3, 200, function ($exception, $request) {
                // Retry only when it's safe to do so (connection issues, 5xx).
                if (method_exists($exception, 'getCode')) {
                    $code = (int) $exception->getCode();
                    return $code >= 500 || $code === 0;
                }
                return true;
            });

        // Optional: add bearer token if n8n is protected; otherwise remove this header.
        if ($token) {
            $client = $client->withToken($token);
        }

        // OPTIONAL: Correlation header for observability across systems.
        $client = $client->withHeaders([
            'X-Request-ID' => 'task-' . $task->id,
        ]);

        // Build the final URL.
        $url = '/' . $webhookPath;

        // Fire the request.
        // IMPORTANT: Keep this call idempotent on the n8n side by using task_id as a unique key.
        $response = $client->post($url, $payload);


        // Handle non-2xx responses as failures so the operator can inspect them.
        if ($response->failed()) {
            // Persist error and mark task as failed so the UI can reflect the issue.
            $body = $response->body();
            $message = sprintf(
                'n8n responded with HTTP %d. Body: %s',
                $response->status(),
                mb_strimwidth($body, 0, 2000, '…') // guard against huge bodies
            );

            // Update the task with error info. Do NOT overwrite completed states if callback already arrived.
            if ($task->status !== 'completed') {
                $task->update([
                    'status'        => 'failed',
                    'error_details' => $message,
                ]);
            }

            // Throwing will trigger the framework's retry/failure pipeline.
            throw new \RuntimeException($message);
        }

        // SUCCESS PATH:
        // Do not change task status here; the n8n workflow should call back our API
        // to finalize the task and provide the generated content.
    }

    /**
     * The job failed permanently (after all retries).
     * Persist a helpful error message without leaking sensitive secrets.
     */
    public function failed(\Throwable $e): void
    {
        // Find the task; it may have been deleted or completed by a late callback.
        if ($task = AdScriptTask::find($this->taskId)) {
            // Avoid overwriting a completed state if callback already succeeded.
            if ($task->status !== 'completed') {
                $task->update([
                    'status'        => 'failed',
                    // NOTE: Truncate message to a safe length to avoid oversized rows.
                    'error_details' => mb_strimwidth($e->getMessage(), 0, 2000, '…'),
                ]);
            }
        }
    }
}
