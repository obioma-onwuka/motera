<?php

use Livewire\Volt\Component;
use Spatie\Activitylog\Models\Activity;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with()
    {
        $this->authorize('view-audit-logs');

        return [
            'logs' => Activity::with('causer')->latest()->paginate(20),
        ];
    }
};
?>

<div class="bg-white rounded-2xl border border-brand-border overflow-hidden shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-brand-border">
            <tr>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Action</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Performer</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Log Name</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Timestamp</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-border text-xs">
            @foreach($logs as $log)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-bold text-slate-700">{{ $log->description }}</p>
                        @if($log->subject_type)
                            <p class="text-[9px] text-slate-400 mt-0.5">{{ class_basename($log->subject_type) }} #{{ substr($log->subject_id, 0, 8) }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-slate-600">{{ $log->causer?->name ?? 'System' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-mono text-[9px]">{{ $log->log_name }}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-400">{{ $log->created_at->format('M d, H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-6 border-t border-brand-border">
        {{ $logs->links() }}
    </div>
</div>