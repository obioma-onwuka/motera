<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Transaction;

new class extends Component
{
    use WithPagination;

    public function with()
    {
        $this->authorize('view-metrics');

        return [
            'transactions' => Transaction::with('user')->latest()->paginate(10),
        ];
    }
};
?>

<div class="bg-white rounded-2xl border border-brand-border overflow-hidden shadow-sm">
    <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
        <h3 class="text-sm font-bold text-brand-text-primary">Recent Transactions</h3>
        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest">Platform Ledger</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left border-collapse">
            <thead class="bg-slate-50 border-b border-brand-border">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Reference</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Customer</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Type</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm">
                @foreach($transactions as $tx)
                    <tr wire:key="tx-{{ $tx->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs">{{ $tx->reference }}</td>
                        <td class="px-6 py-4">
                            <p class="font-bold">{{ $tx->user?->name ?? 'System' }}</p>
                            <p class="text-[10px] text-slate-400">{{ $tx->user?->email ?? '—' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold uppercase">
                                {{ str_replace('_', ' ', $tx->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold tabular-nums">${{ number_format($tx->amount, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-bold uppercase {{ match ($tx->status->value) {
                                'successful' => 'bg-green-100 text-green-700',
                                'processing' => 'bg-blue-100 text-blue-700',
                                'pending' => 'bg-orange-100 text-orange-700',
                                'failed' => 'bg-red-100 text-red-700',
                                'reversed', 'cancelled' => 'bg-slate-100 text-slate-600',
                                default => 'bg-slate-100 text-slate-600',
                            } }}">
                                {{ $tx->status->value }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">{{ $tx->created_at->format('j M Y, H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="p-6 border-t border-brand-border">
        {{ $transactions->links() }}
    </div>
</div>
