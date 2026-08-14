<?php

use App\Models\Biller;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function with()
    {
        $this->authorize('manage-billers');

        return [
            'billers' => Biller::withCount('payments')->latest()->paginate(15),
        ];
    }

    public function toggleActive($id)
    {
        $this->authorize('manage-billers');

        $biller = Biller::findOrFail($id);
        $biller->update(['is_active' => ! $biller->is_active]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($biller)
            ->withProperties(['is_active' => $biller->is_active])
            ->log($biller->is_active ? 'biller.activated' : 'biller.deactivated');

        session()->flash('success', $biller->is_active
            ? 'Biller activated.'
            : 'Biller deactivated.');
    }
};
?>

<div class="space-y-6">
    @if (session('success'))
        <div class="p-4 bg-green-50 rounded-2xl border border-green-200 text-sm text-green-700 font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-brand-border overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-brand-border">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Biller</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Category</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Payments</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm">
                @foreach($billers as $biller)
                    <tr wire:key="biller-{{ $biller->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-bold">{{ $biller->name }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold uppercase">{{ $biller->category->value }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $biller->payments_count }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-bold uppercase {{ $biller->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $biller->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click.throttle.1500ms="toggleActive('{{ $biller->id }}')" class="text-xs font-bold {{ $biller->is_active ? 'text-red-500 hover:text-red-700' : 'text-teal-600 hover:text-teal-700' }} hover:underline">
                                {{ $biller->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 border-t border-brand-border">
            {{ $billers->links() }}
        </div>
    </div>
</div>
