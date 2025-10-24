<?php

namespace Tests\Feature;

use App\Jobs\SendToN8nJob;
use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateAdScriptTaskTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_request_payload()
    {
        Queue::fake();

        $response = $this->postJson('/api/ad-scripts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference_script', 'outcome_description']);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('ad_script_tasks', 0);
    }

    /** @test */
    public function it_creates_pending_task_and_dispatches_job()
    {
        Queue::fake();

        $payload = [
            'reference_script'     => 'Buy one, get one free!',
            'outcome_description'  => 'More professional tone targeting SMBs',
        ];

        // Act
        $response = $this->postJson('/api/ad-scripts', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonStructure(['id', 'status']);

        $this->assertDatabaseHas('ad_script_tasks', [
            'reference_script'    => $payload['reference_script'],
            'outcome_description' => $payload['outcome_description'],
            'status'              => 'pending',
        ]);

        // Get the newly created ID
        $taskId = AdScriptTask::query()->value('id');

        // Assert that our job was dispatched with the correct taskId
        Queue::assertPushed(SendToN8nJob::class, function ($job) use ($taskId) {
            return $job->taskId === $taskId;
        });
    }
}
