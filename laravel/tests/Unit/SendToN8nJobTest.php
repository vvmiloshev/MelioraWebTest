<?php

namespace Tests\Unit;

use App\Jobs\SendToN8nJob;
use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendToN8nJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Block any real HTTP and start from a clean slate for each test
        Http::preventStrayRequests();
        // DO NOT set a default fake here, each test will define its own mapping
    }

    /** @test */
    public function it_calls_n8n_webhook_with_expected_payload()
    {
        $task = AdScriptTask::factory()->create([
            'reference_script'    => 'Old copy',
            'outcome_description' => 'New tone',
            'status'              => 'pending',
        ]);

        // Arrange config and exact URL
        config()->set('services.n8n.base_url', 'https://example.com/webhook/');
        config()->set('services.n8n.webhook_path', 'ad-script-agent');
        config()->set('services.n8n.token', 'test-token');

        $url = 'https://example.com/webhook/ad-script-agent';

        // Fake 200 for the exact URL
        Http::fake([
            $url => Http::response(['ok' => true], 200),
        ]);

        (new SendToN8nJob($task->id))->handle();

        Http::assertSent(function ($request) use ($task, $url) {
            $data = $request->data();
            return $request->url() === $url
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && ($data['task_id'] ?? null) === $task->id
                && ($data['reference_script'] ?? null) === $task->reference_script
                && ($data['outcome_description'] ?? null) === $task->outcome_description;
        });
    }

    /** @test */
    public function it_sets_failed_status_on_http_error()
    {
        $task = AdScriptTask::factory()->create(['status' => 'pending']);

        // Arrange config and exact URL
        config()->set('services.n8n.base_url', 'https://example.com/webhook/');
        config()->set('services.n8n.webhook_path', 'ad-script-agent');
        config()->set('services.n8n.token', null); // token not needed here

        $url = 'https://example.com/webhook/ad-script-agent';

        // Fake 500 for the exact URL (CRITICAL)
        Http::fake([
            $url => Http::response('boom', 500),
        ]);

        (new SendToN8nJob($task->id))->handle();

        $task->refresh();
        $this->assertEquals('failed', $task->status);
        $this->assertNotNull($task->error_details);
    }
}
