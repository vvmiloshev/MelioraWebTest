<?php

namespace App\Livewire;

use App\Models\AdScriptTask;
use Livewire\Component;
use Livewire\WithPagination;

class AdScriptTasksTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    protected $queryString = ['search', 'status'];

    // Refresh every 5s as a simple real-time fallback
    protected $listeners = ['task-updated' => '$refresh'];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }

    public function render()
    {
        $q = AdScriptTask::query()
            ->when($this->search, fn($qq) => $qq->where(function($w){
                $w->where('reference_script', 'like', "%{$this->search}%")
                    ->orWhere('outcome_description', 'like', "%{$this->search}%");
            }))
            ->when($this->status, fn($qq) => $qq->where('status', $this->status))
            ->latest();

        return view('livewire.ad-script-tasks-table', [
            'tasks' => $q->paginate(10),
        ]);
    }
}
