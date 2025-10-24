<div wire:poll.5s> {{-- Simple polling; can be replaced by Echo later --}}
    <div class="mb-4 flex gap-3">
        <input type="text" wire:model.debounce.400ms="search" placeholder="Search…" class="border p-2 rounded w-64">
        <select wire:model="status" class="border p-2 rounded">
            <option value="">All statuses</option>
            <option value="pending">pending</option>
            <option value="completed">completed</option>
            <option value="failed">failed</option>
        </select>
    </div>

    <table class="w-full border-collapse">
        <thead>
        <tr class="text-left border-b">
            <th class="p-2">ID</th>
            <th class="p-2">Status</th>
            <th class="p-2">Outcome</th>
            <th class="p-2">Created</th>
            <th class="p-2">Updated</th>
            <th class="p-2">Details</th>
        </tr>
        </thead>
        <tbody>
        @forelse($tasks as $t)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-2">{{ $t->id }}</td>
                <td class="p-2">
            <span class="px-2 py-1 rounded text-sm
              @if($t->status === 'completed') bg-green-100 text-green-700
              @elseif($t->status === 'failed') bg-red-100 text-red-700
              @else bg-yellow-100 text-yellow-700 @endif">
              {{ $t->status }}
            </span>
                </td>
                <td class="p-2 truncate max-w-[300px]">{{ $t->outcome_description }}</td>
                <td class="p-2">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                <td class="p-2">{{ $t->updated_at->format('Y-m-d H:i') }}</td>
                <td class="p-2">
                    <button onclick="document.getElementById('m{{ $t->id }}').showModal()" class="border px-2 py-1 rounded">View</button>
                    <dialog id="m{{ $t->id }}" class="p-0 rounded-md">
                        <div class="p-4 max-w-3xl">
                            <h3 class="font-semibold mb-2">Task #{{ $t->id }} details</h3>
                            <p class="text-sm mb-1"><strong>Reference:</strong></p>
                            <pre class="bg-gray-100 p-3 overflow-auto">{{ $t->reference_script }}</pre>
                            <p class="text-sm mt-3 mb-1"><strong>Outcome:</strong></p>
                            <pre class="bg-gray-100 p-3 overflow-auto">{{ $t->outcome_description }}</pre>
                            @if($t->new_script)
                                <p class="text-sm mt-3 mb-1"><strong>New Script:</strong></p>
                                <pre class="bg-green-50 p-3 overflow-auto">{{ $t->new_script }}</pre>
                            @endif
                            @if($t->analysis)
                                <p class="text-sm mt-3 mb-1"><strong>Analysis:</strong></p>
                                <pre class="bg-blue-50 p-3 overflow-auto">{{ $t->analysis }}</pre>
                            @endif
                            @if($t->error_details)
                                <p class="text-sm mt-3 mb-1 text-red-700"><strong>Error:</strong></p>
                                <pre class="bg-red-50 p-3 overflow-auto">{{ $t->error_details }}</pre>
                            @endif
                            <div class="mt-4 text-right">
                                <button onclick="document.getElementById('m{{ $t->id }}').close()" class="border px-3 py-1 rounded">Close</button>
                            </div>
                        </div>
                    </dialog>
                </td>
            </tr>
        @empty
            <tr><td class="p-4 text-gray-500" colspan="6">No tasks yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $tasks->links() }}
    </div>
</div>
