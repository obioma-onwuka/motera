<?php

use Livewire\Volt\Component;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\InteractsWithIdempotency;

new class extends Component {
    use InteractsWithIdempotency;

    public $accountNumber;
    public $recipientName;
    public $amount;
    public $description;
    public $step = 1;
    public $pin;
    public $idempotencyKey;

    public function mount()
    {
        $this->idempotencyKey = Str::uuid()->toString();
    }

    public function updatedAccountNumber()
    {
        $account = BankAccount::where('account_number', $this->accountNumber)->first();
        $this->recipientName = $account && $account->user_id !== auth()->id() ? $account->user->name : null;
    }

    public function confirmTransfer()
    {
        $this->validate([
            'accountNumber' => 'required|exists:bank_accounts,account_number',
            'amount' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:100',
        ]);

        $senderAccount = auth()->user()->primaryAccount;
        if ($senderAccount->available_balance < $this->amount) {
            $this->addError('amount', 'Insufficient balance.');
            return;
        }

        $this->step = 2;
    }

    public function processTransfer()
    {
        $this->validate([
            'pin' => 'required|digits:4',
        ]);

        $sender = auth()->user();

        if (!\Illuminate\Support\Facades\Hash::check($this->pin, $sender->transaction_pin)) {
            $this->addError('pin', 'Incorrect Transaction PIN.');
            return;
        }

        return $this->idempotent($this->idempotencyKey, function () use ($sender) {
            return DB::transaction(function () use ($sender) {
                // Determine locking order by ID to prevent deadlocks
                $recipientAccount = BankAccount::where('account_number', $this->accountNumber)->first();
                $senderAccount = $sender->primaryAccount;

                if (!$senderAccount || !$recipientAccount) {
                    throw new \Exception('Account not found.');
                }

                if ($senderAccount->id === $recipientAccount->id) {
                    $this->addError('accountNumber', 'You cannot transfer to yourself.');
                    return;
                }

                $firstId = $senderAccount->id < $recipientAccount->id ? $senderAccount->id : $recipientAccount->id;
                $secondId = $senderAccount->id < $recipientAccount->id ? $recipientAccount->id : $senderAccount->id;

                $lockedAccounts = BankAccount::whereIn('id', [$firstId, $secondId])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $senderAccount = $lockedAccounts->get($senderAccount->id);
                $recipientAccount = $lockedAccounts->get($recipientAccount->id);

                if ($senderAccount->available_balance < $this->amount) {
                    $this->addError('amount', 'Insufficient balance.');
                    return;
                }

                $reference = 'TRF-' . strtoupper(Str::random(10));

                // 1. Create High-Level Transaction Record
                $transaction = Transaction::create([
                    'user_id' => $sender->id,
                    'bank_account_id' => $senderAccount->id,
                    'type' => 'transfer',
                    'amount' => $this->amount,
                    'status' => 'completed',
                    'reference' => $reference,
                    'description' => $this->description ?? 'Internal Transfer',
                    'metadata' => [
                        'recipient_account_number' => $this->accountNumber,
                        'recipient_name' => $recipientAccount->user->name,
                        'idempotency_key' => $this->idempotencyKey,
                    ],
                ]);

                // 2. Debit Sender
                $senderAccount->decrement('available_balance', $this->amount);
                $senderAccount->decrement('ledger_balance', $this->amount);
                
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'bank_account_id' => $senderAccount->id,
                    'type' => 'debit',
                    'amount' => $this->amount,
                    'description' => "Transfer to {$recipientAccount->user->name}: {$this->description}",
                    'reference' => $reference,
                    'balance_after' => $senderAccount->available_balance,
                ]);

                // 3. Credit Recipient
                $recipientAccount->increment('available_balance', $this->amount);
                $recipientAccount->increment('ledger_balance', $this->amount);
                
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'bank_account_id' => $recipientAccount->id,
                    'type' => 'credit',
                    'amount' => $this->amount,
                    'description' => "Transfer from {$sender->name}: {$this->description}",
                    'reference' => $reference,
                    'balance_after' => $recipientAccount->available_balance,
                ]);

                session()->flash('success', 'Transfer completed successfully!');
                return redirect()->route('dashboard');
            });
        });
    }
}; ?>

<div class="max-w-md mx-auto">
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-brand-border">
        @if ($step === 1)
            <h3 class="text-xl font-bold text-brand-text-primary mb-6">Internal Transfer</h3>

            <form wire:submit="confirmTransfer" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Account Number</label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.500ms="accountNumber" 
                               class="w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-gray-50 transition-all"
                               placeholder="Enter 10-digit number">
                        @if ($recipientName)
                            <div class="mt-2 p-3 bg-blue-50 rounded-xl border border-blue-100 flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-blue-600 flex items-center justify-center text-[10px] text-white font-bold">
                                    {{ substr($recipientName, 0, 1) }}
                                </div>
                                <span class="text-xs font-bold text-blue-700 uppercase tracking-tighter">{{ $recipientName }}</span>
                            </div>
                        @endif
                    </div>
                    @error('accountNumber') <span class="text-brand-danger text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Amount</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm font-bold">₦</span>
                        </div>
                        <input type="number" wire:model="amount" 
                               class="w-full pl-10 pr-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-gray-50 transition-all"
                               placeholder="0.00">
                    </div>
                    @error('amount') <span class="text-brand-danger text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Description (Optional)</label>
                    <input type="text" wire:model="description" 
                           class="w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-gray-50 transition-all"
                           placeholder="e.g. For dinner">
                    @error('description') <span class="text-brand-danger text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            class="btn-primary w-full shadow-lg shadow-blue-100"
                            {{ !$recipientName ? 'disabled' : '' }}>
                        Continue
                    </button>
                </div>
            </form>
        @else
            <button wire:click="$set('step', 1)" class="mb-6 text-xs font-bold text-brand-primary uppercase tracking-widest hover:underline flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to edit
            </button>

            <h3 class="text-xl font-bold text-brand-text-primary mb-6">Confirm Transfer</h3>

            <div class="bg-slate-50 rounded-2xl p-6 mb-6 border border-brand-border">
                <div class="text-center mb-6">
                    <p class="text-[10px] uppercase font-bold text-brand-text-secondary tracking-widest mb-1">Send exactly</p>
                    <h4 class="text-3xl font-black text-brand-primary">₦{{ number_format($amount, 2) }}</h4>
                </div>

                <div class="space-y-3 pt-4 border-t border-brand-border text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-brand-text-secondary">To</span>
                        <span class="font-bold text-brand-text-primary uppercase">{{ $recipientName }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-brand-text-secondary">Account</span>
                        <span class="font-mono text-brand-text-primary">{{ $accountNumber }}</span>
                    </div>
                    @if($description)
                    <div class="flex justify-between items-center">
                        <span class="text-brand-text-secondary">Note</span>
                        <span class="text-brand-text-primary italic">"{{ $description }}"</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center pt-3 border-t border-brand-border border-dashed">
                        <span class="text-brand-text-secondary">Fee</span>
                        <span class="font-bold text-brand-success">Free</span>
                    </div>
                </div>
            </div>

            <form wire:submit="processTransfer" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-brand-text-primary mb-2">Transaction PIN</label>
                    <input type="password" wire:model="pin" maxlength="4" class="block w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-center tracking-[1em] text-lg transition-all" placeholder="••••">
                    @error('pin') <span class="text-xs text-brand-danger mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary w-full shadow-lg shadow-blue-100">
                        <span wire:loading.remove wire:target="processTransfer">Send Money Now</span>
                        <span wire:loading wire:target="processTransfer">Processing...</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>