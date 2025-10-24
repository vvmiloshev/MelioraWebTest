<?php

namespace Tests\Feature;

use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdScriptCallbackNewTest extends TestCase
{
    use RefreshDatabase;

    private string $cbToken = 'test-callback-token';

    protected function setUp(): void
    {
        parent::setUp();
        // set config value that controller checks
        config()->set('services.callbacks.bearer_token', $this->cbToken);
    }



    /** @test */
    public function it_updates_task_on_successful_callback()
    {
        $task = AdScriptTask::factory()->create(['status' => 'pending']);

        $payload = [
            'task_id'    => $task->id,
            'new_script' => 'A refined, concise ad copy for SMBs.',
            'analysis'   => 'Improved clarity and call-to-action.',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cbToken)
            ->postJson("/api/ad-scripts/{$task->id}/result", $payload);

        $response->assertOk();

        $this->assertDatabaseHas('ad_script_tasks', [
            'id'         => $task->id,
            'status'     => 'completed',
            'new_script' => $payload['new_script'],
            'analysis'   => $payload['analysis'],
        ]);
    }

    /** @test */
    public function it_fails_when_ids_mismatch()
    {
        $task = AdScriptTask::factory()->create(['status' => 'pending']);

        $payload = [
            'task_id'    => $task->id + 999, // mismatch on purpose (валидаторът ти е 'in:'.$id)
            'new_script' => 'X',
            'analysis'   => 'Y',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cbToken)
            ->postJson("/api/ad-scripts/{$task->id}/result", $payload);

        $response->assertStatus(422);

        $this->assertDatabaseHas('ad_script_tasks', [
            'id'     => $task->id,
            'status' => 'pending',
        ]);
    }
}
