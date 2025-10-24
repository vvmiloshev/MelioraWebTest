<?php

namespace App\Jobs;

use App\Models\AdScriptTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendToN8nJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $taskId;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function handle(): void
    {
        $task = AdScriptTask::findOrFail($this->taskId);

        $task->update(['status' => 'processing']);

        $url   = config('services.n8n.webhook_url');
        $token = config('services.n8n.bearer_token');

        // Сглоби URL от APP_URL, за да избегнем проблеми с именувани маршрути
        $base = rtrim(config('app.url'), '/'); // трябва да е http://host.docker.internal:8000
        $callbackUrl = "{$base}/api/ad-scripts/{$task->id}/result";

        $payload = [
            'task_id'             => $task->id,
            'reference_script'    => $task->reference_script,
            'outcome_description' => $task->outcome_description,
            'callback_url'        => $callbackUrl,
            'callback_token'      => config('services.callbacks.bearer_token'),
        ];

        $resp = Http::withToken($token)->timeout(60)->post($url, $payload);

        if (! $resp->successful()) {
            $task->update([
                'status' => 'failed',
                'error'  => "HTTP {$resp->status()}: {$resp->body()}",
            ]);
        }
    }


    public function failed(\Throwable $e): void
    {
        if ($task = AdScriptTask::find($this->taskId)) {
            $task->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
