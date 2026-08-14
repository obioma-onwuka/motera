<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\DepositRequest;
use App\Actions\Deposits\ApproveDepositAction;
use App\Actions\Deposits\RejectDepositAction;
use App\Exceptions\RequestAlreadyProcessedException;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination;

    public $selectedRequest = null;
    public $adminNote = '';

    public function with()
    {
        $this->authorize('approve-deposits');

        return [
            'requests' => DepositRequest::with('user', 'bankAccount')->latest()->paginate(15),
        ];
    }

    public function selectRequest($id)
    {
        $this->selectedRequest = DepositRequest::with('user')->find($id);
    }

    public function approve(ApproveDepositAction $action)
    {
        $this->authorize('approve-deposits');

        try {
            $action->execute($this->selectedRequest, $this->adminNote);
            $this->selectedRequest = null;
            $this->adminNote = '';
            session()->flash('success', 'Deposit approved and account credited.');
        } catch (RequestAlreadyProcessedException $e) {
            session()->flash('error', $e->getMessage());
            $this->selectedRequest = null;
            $this->adminNote = '';
        }
    }

    public function reject(RejectDepositAction $action)
    {
        $this->authorize('approve-deposits');

        $this->validate(['adminNote' => 'required|string|min:5']);

        try {
            $action->execute($this->selectedRequest, $this->adminNote);
            $this->selectedRequest = null;
            $this->adminNote = '';
            session()->flash('success', 'Deposit request rejected.');
        } catch (RequestAlreadyProcessedException $e) {
            session()->flash('error', $e->getMessage());
            $this->selectedRequest = null;
            $this->adminNote = '';
        }
    }
};
?>

<div class="space-y-6">
    @if (session('error'))
        <div class="p-4 bg-red-50 rounded-2xl border border-red-200 text-sm text-red-700 font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-brand-border overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-brand-border">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">User</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Amount</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Reference</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm">
                @foreach($requests as $request)
                    <tr wire:key="deposit-{{ $request->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold">{{ $request->user->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $request->user->email }}</p>
                        </td>
                        <td class="px-6 py-4 font-bold text-teal-600">${{ number_format($request->amount, 2) }}</td>
                        <td class="px-6 py-4 font-mono text-xs">{{ $request->reference }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full text-[8px] font-bold uppercase {{ $request->status->value === 'approved' ? 'bg-green-100 text-green-700' : ($request->status->value === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700') }}">
                                {{ $request->status->value }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click="selectRequest('{{ $request->id }}')" class="text-xs font-bold text-brand-primary hover:underline">Review Proof</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 border-t border-brand-border">
            {{ $requests->links() }}
        </div>
    </div>

    @if($selectedRequest)
        <div role="dialog" aria-modal="true" aria-labelledby="modal-title" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-6 border-b border-brand-border flex items-center justify-between">
                    <h3 id="modal-title" class="font-bold text-lg">Review Deposit Proof</h3>
                    <button wire:click="$set('selectedRequest', null)" aria-label="Close" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-8 space-y-6">
                    <div class="aspect-video w-full rounded-2xl bg-slate-100 border border-brand-border overflow-hidden">
                        <img src="{{ Storage::disk('local')->temporaryUrl($selectedRequest->proof_path, now()->addMinutes(5)) }}" class="w-full h-full object-contain">
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label for="adminNote" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Internal Note / Admin Comment</label>
                            <textarea id="adminNote" wire:model="adminNote" rows="3" class="w-full px-4 py-3 rounded-xl border-brand-border text-sm" placeholder="Enter reason for approval or rejection..."></textarea>
                            @error('adminNote') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex gap-4">
                            <button wire:click.throttle.1500ms="reject" class="flex-1 btn-outline border-red-200 text-red-600 hover:bg-red-50">Reject Request</button>
                            <button wire:click.throttle.1500ms="approve" class="flex-1 btn-primary bg-teal-600 shadow-teal-100">Approve & Credit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
