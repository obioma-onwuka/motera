<?php

use Livewire\Volt\Component;
use App\Models\CardRequest;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\InteractsWithIdempotency;

new class extends Component {
    use InteractsWithIdempotency;

    public $type = 'virtual';
    public $cardName;
    public $deliveryAddress;
    public $showRequestForm = false;
    public $idempotencyKey;

    public function mount()
    {
        $this->cardName = auth()->user()->name;
        $this->idempotencyKey = Str::uuid()->toString();
    }

    public function submitRequest()
    {
        $this->validate([
            'type' => 'required|in:virtual,physical',
            'cardName' => 'required|string|max:26',
            'deliveryAddress' => 'required_if:type,physical|nullable|string',
        ]);

        return $this->idempotent($this->idempotencyKey, function () {
            $user = auth()->user();
            $fee = $this->type === 'physical' ? 1000 : 0;

            return DB::transaction(function () use ($user, $fee) {
                $account = BankAccount::where('id', $user->primaryAccount->id)
                    ->lockForUpdate()
                    ->first();

                if ($account->available_balance < $fee) {
                    $this->addError('type', 'Insufficient balance for card issuance fee.');
                    return;
                }

                if ($fee > 0) {
                    $account->decrement('available_balance', $fee);
                    $account->decrement('ledger_balance', $fee);

                    $reference = 'CARD-FEE-' . strtoupper(Str::random(8));
                    
                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'bank_account_id' => $account->id,
                        'type' => 'card_fee',
                        'amount' => $fee,
                        'status' => 'successful',
                        'reference' => $reference,
                        'description' => "Card Issuance Fee ({$this->type})",
                        'metadata' => [
                            'idempotency_key' => $this->idempotencyKey,
                        ],
                    ]);
                    
                    \App\Models\LedgerEntry::create([
                        'transaction_id' => $transaction->id,
                        'bank_account_id' => $account->id,
                        'type' => 'debit',
                        'amount' => $fee,
                        'description' => "Card Issuance Fee ({$this->type})",
                        'reference' => $reference,
                        'balance_after' => $account->available_balance,
                    ]);
                }

                CardRequest::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $account->id,
                    'type' => $this->type,
                    'card_name' => $this->cardName,
                    'delivery_address' => $this->deliveryAddress,
                    'fee' => $fee,
                    'status' => 'pending',
                    'metadata' => ['idempotency_key' => $this->idempotencyKey],
                ]);
            });

            session()->flash('success', 'Card request submitted successfully!');
            $this->showRequestForm = false;
            $this->reset(['type', 'deliveryAddress']);
            $this->idempotencyKey = Str::uuid()->toString();
        });
    }

    public function with()
    {
        return [
            'requests' => auth()->user()->cardRequests()->latest()->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold text-brand-text-primary">Your Cards</h3>
        <button wire:click="$toggle('showRequestForm')" class="btn-primary text-sm shadow-sm py-2 px-4 min-h-0">
            Request New Card
        </button>
    </div>

    @if ($showRequestForm)
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-brand-border">
            <form wire:submit="submitRequest" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" wire:click="$set('type', 'virtual')" 
                            class="p-4 border-2 @if($type === 'virtual') border-brand-primary bg-blue-50 @else border-brand-border bg-white @endif rounded-2xl text-center transition-all cursor-pointer">
                        <span class="block font-bold text-brand-text-primary">Virtual Card</span>
                        <span class="text-xs text-brand-text-secondary">Free & Instant</span>
                    </button>
                    <button type="button" wire:click="$set('type', 'physical')" 
                            class="p-4 border-2 @if($type === 'physical') border-brand-primary bg-blue-50 @else border-brand-border bg-white @endif rounded-2xl text-center transition-all cursor-pointer">
                        <span class="block font-bold text-brand-text-primary">Physical Card</span>
                        <span class="text-xs text-brand-text-secondary">₦1,000.00 Fee</span>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-brand-text-primary mb-2">Name on Card</label>
                    <input type="text" wire:model.blur="cardName" class="block w-full px-4 py-3 rounded-xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all shadow-sm uppercase tracking-wider">
                    @error('cardName') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                </div>

                @if ($type === 'physical')
                    <div>
                        <label class="block text-sm font-semibold text-brand-text-primary mb-2">Delivery Address</label>
                        <textarea wire:model.blur="deliveryAddress" rows="3" class="block w-full px-4 py-3 rounded-xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all shadow-sm"></textarea>
                        @error('deliveryAddress') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t border-brand-border">
                    <button type="button" wire:click="$set('showRequestForm', false)" class="btn-outline text-sm py-2 px-4 min-h-0">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary text-sm py-2 px-6 min-h-0 shadow-lg shadow-blue-100">
                        <span wire:loading.remove wire:target="submitRequest">Submit Request</span>
                        <span wire:loading wire:target="submitRequest">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse ($requests as $request)
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 p-6 rounded-2xl shadow-xl border border-gray-700 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4">
                    <span class="px-2 py-1 text-[10px] uppercase font-bold rounded {{ $request->status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white' }}">
                        {{ $request->status }}
                    </span>
                </div>
                
                <div class="mb-8">
                    <span class="text-blue-400 font-bold tracking-widest text-lg">MOTERA</span>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="h-8 w-12 bg-yellow-400 rounded opacity-50"></div>
                        <div class="text-white font-mono tracking-[0.2em] text-lg">•••• •••• •••• ••••</div>
                    </div>
                    
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase">Card Holder</p>
                            <p class="text-white font-medium uppercase tracking-wider">{{ $request->card_name }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase">Type</p>
                            <p class="text-white text-xs uppercase">{{ $request->type }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 py-12 text-center bg-gray-50 dark:bg-gray-900/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <p class="text-gray-500">You don't have any cards yet.</p>
            </div>
        @endforelse
    </div>
</div>