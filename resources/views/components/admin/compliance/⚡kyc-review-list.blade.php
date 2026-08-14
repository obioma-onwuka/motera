<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\KycSubmission;
use App\Actions\Compliance\ApproveKycAction;
use App\Actions\Compliance\RejectKycAction;
use App\Exceptions\RequestAlreadyProcessedException;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination;

    public $selectedSubmission = null;
    public $rejectReason = '';

    public function with()
    {
        $this->authorize('review-kyc');

        return [
            'submissions' => KycSubmission::with(['user', 'documents'])->latest()->paginate(15),
        ];
    }

    public function selectSubmission($id)
    {
        $this->selectedSubmission = KycSubmission::with(['user', 'documents'])->find($id);
    }

    public function approve(ApproveKycAction $action)
    {
        $this->authorize('review-kyc');

        try {
            $action->execute($this->selectedSubmission);
            $this->selectedSubmission = null;
            session()->flash('success', 'KYC submission approved.');
        } catch (RequestAlreadyProcessedException $e) {
            $this->selectedSubmission = null;
            session()->flash('error', $e->getMessage());
        }
    }

    public function reject(RejectKycAction $action)
    {
        $this->authorize('review-kyc');

        $this->validate(['rejectReason' => 'required|string|min:5']);

        try {
            $action->execute($this->selectedSubmission, $this->rejectReason);
            $this->selectedSubmission = null;
            $this->rejectReason = '';
            session()->flash('success', 'KYC submission rejected.');
        } catch (RequestAlreadyProcessedException $e) {
            $this->selectedSubmission = null;
            $this->rejectReason = '';
            session()->flash('error', $e->getMessage());
        }
    }
};
?>

<div class="space-y-6">
    @if(session('error'))
        <div role="alert" class="bg-red-50 border border-red-200 text-red-700 text-sm font-semibold px-4 py-3 rounded-xl shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- List -->
    <div class="bg-white rounded-2xl border border-brand-border overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-brand-border">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">User</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">ID Type</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Submitted</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
                @foreach($submissions as $sub)
                    <tr wire:key="kyc-{{ $sub->id }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-sm text-brand-text-primary">{{ $sub->first_name }} {{ $sub->last_name }}</p>
                            <p class="text-[10px] text-brand-text-secondary">{{ $sub->user->email }}</p>
                        </td>
                        <td class="px-6 py-4 text-xs font-medium text-slate-600">{{ $sub->id_type }}</td>
                        <td class="px-6 py-4 text-xs text-slate-500">{{ $sub->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-[8px] font-bold uppercase {{ $sub->status->value === 'approved' ? 'bg-green-100 text-green-700' : ($sub->status->value === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700') }}">
                                {{ $sub->status->value }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click="selectSubmission('{{ $sub->id }}')" class="text-xs font-bold text-brand-primary hover:underline">Review Documents</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-6 border-t border-brand-border">
            {{ $submissions->links() }}
        </div>
    </div>

    <!-- Review Modal -->
    @if($selectedSubmission)
        <div role="dialog" aria-modal="true" aria-labelledby="modal-title" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl overflow-hidden flex flex-col shadow-2xl">
                <div class="p-6 border-b border-brand-border flex items-center justify-between">
                    <div>
                        <h3 id="modal-title" class="text-xl font-bold">Reviewing Submission</h3>
                        <p class="text-sm text-slate-500">{{ $selectedSubmission->first_name }} {{ $selectedSubmission->last_name }} ({{ $selectedSubmission->user->email }})</p>
                    </div>
                    <button wire:click="$set('selectedSubmission', null)" aria-label="Close" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 bg-slate-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Info -->
                        <div class="space-y-6">
                            <div class="bg-white p-6 rounded-2xl border border-brand-border shadow-sm">
                                <h4 class="text-xs font-bold text-slate-400 uppercase mb-4 tracking-widest">Personal Info</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[10px] text-slate-500">DOB</p>
                                        <p class="text-sm font-bold">{{ $selectedSubmission->date_of_birth }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500">ID Number</p>
                                        <p class="text-sm font-bold">{{ $selectedSubmission->id_number }}</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-[10px] text-slate-500">Address</p>
                                    <p class="text-sm font-bold">{{ $selectedSubmission->address }}</p>
                                </div>
                            </div>

                            @if($selectedSubmission->status->value === 'pending')
                                <div class="bg-white p-6 rounded-2xl border border-brand-border shadow-sm space-y-4">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Decision Console</h4>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2">Rejection Reason (Optional for approval)</label>
                                        <textarea wire:model="rejectReason" class="w-full px-4 py-3 rounded-xl border-brand-border text-sm" rows="3" placeholder="Explain why this was rejected..."></textarea>
                                        @error('rejectReason') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="flex gap-4">
                                        <button wire:click.throttle.1500ms="reject" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-red-100 hover:bg-red-700 transition-colors">Reject Submission</button>
                                        <button wire:click.throttle.1500ms="approve" class="flex-1 px-4 py-3 bg-green-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-green-100 hover:bg-green-700 transition-colors">Approve KYC</button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Documents -->
                        <div class="space-y-6">
                            @foreach($selectedSubmission->documents as $doc)
                                <div class="bg-white p-4 rounded-2xl border border-brand-border shadow-sm space-y-2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ str_replace('_', ' ', $doc->document_type) }}</p>
                                    <a href="{{ Storage::disk('local')->temporaryUrl($doc->file_path, now()->addMinutes(5)) }}" target="_blank" class="block rounded-lg overflow-hidden border border-brand-border">
                                        <img src="{{ Storage::disk('local')->temporaryUrl($doc->file_path, now()->addMinutes(5)) }}" class="w-full h-auto max-h-64 object-contain">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>