<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with()
    {
        $this->authorize('view-customers');

        return [
            'customers' => User::role('Customer')
                ->with('primaryAccount')
                ->withCount('bankAccounts')
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="bg-white rounded-2xl border border-brand-border overflow-hidden shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-brand-border">
            <tr>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Customer</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Account Number</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Available Balance</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Tier</th>
                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-border text-sm">
            @foreach($customers as $customer)
                <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-bold">{{ $customer->name }}</p>
                        <p class="text-[10px] text-slate-400">{{ $customer->email }}</p>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs">{{ $customer->primaryAccount?->account_number ?? '—' }}</td>
                    <td class="px-6 py-4 font-bold text-teal-600">
                        ${{ number_format($customer->primaryAccount?->available_balance ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold uppercase">
                            {{ ucwords(str_replace('_', ' ', $customer->primaryAccount?->tier?->value ?? 'tier_1')) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[8px] font-bold uppercase {{ ($customer->primaryAccount?->status?->value ?? 'pending') === 'active' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ $customer->primaryAccount?->status?->value ?? 'No account' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-6 border-t border-brand-border">
        {{ $customers->links() }}
    </div>
</div>
