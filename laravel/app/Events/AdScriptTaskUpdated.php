<?php

namespace App\Events;

use App\Models\AdScriptTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AdScriptTaskUpdated implements ShouldBroadcast
{
    /**
     * @var AdScriptTask
     */
    private AdScriptTask $task;

    public function __construct(AdScriptTask $task) {
        $this->task = $task;
    }

    public function broadcastOn(): Channel
    {
        // Public channel for demo; switch to private if needed
        return new Channel('ad-script-tasks');
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'     => $this->task->id,
            'status' => $this->task->status,
        ];
    }
}
