<?php

use Livewire\Volt\Component;
use App\Models\Biller;
use App\Models\BillPayment;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\InteractsWithIdempotency;

new class extends Component {
    use InteractsWithIdempotency;

    public $step = 1;
    public $selectedCategory = null;
    public $selectedBillerId = null;
    public $amount;
    public $customerIdentifier;
    public $pin;
    public $idempotencyKey;

    public function mount()
    {
        $this->idempotencyKey = Str::uuid()->toString();
    }

    public function selectBiller($id)
    {
        $this->selectedBillerId = $id;
        $this->step = 2;
    }

    public function processPayment()
    {
        $this->validate([
            'amount' => 'required|numeric|min:100',
            'customerIdentifier' => 'required|string',
            'pin' => 'required|string|size:4',
        ]);

        return $this->idempotent($this->idempotencyKey, function () {
            $user = auth()->user();
            $biller = Biller::findOrFail($this->selectedBillerId);

            return DB::transaction(function () use ($user, $biller) {
                $account = BankAccount::where('id', $user->primaryAccount->id)
                    ->lockForUpdate()
                    ->first();

                if ($account->available_balance < $this->amount) {
                    $this->addError('amount', 'Insufficient balance.');
                    return;
                }

                if (!\Illuminate\Support\Facades\Hash::check($this->pin, $user->transaction_pin)) {
                    $this->addError('pin', 'Incorrect Transaction PIN.');
                    return;
                }

                $reference = 'BILL-' . strtoupper(Str::random(10));

                // 1. Create High-Level Transaction
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $account->id,
                    'type' => 'bill_payment',
                    'amount' => $this->amount,
                    'status' => 'successful',
                    'reference' => $reference,
                    'description' => "Bill Payment: {$biller->name} ({$this->customerIdentifier})",
                    'metadata' => [
                        'biller_id' => $biller->id,
                        'customer_identifier' => $this->customerIdentifier,
                        'idempotency_key' => $this->idempotencyKey,
                    ],
                ]);

                // 2. Deduct balances
                $account->decrement('available_balance', $this->amount);
                $account->decrement('ledger_balance', $this->amount);

                // 3. Record Bill Payment record
                BillPayment::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $account->id,
                    'biller_id' => $biller->id,
                    'amount' => $this->amount,
                    'reference' => $reference,
                    'customer_identifier' => $this->customerIdentifier,
                    'status' => 'successful',
                    'metadata' => ['idempotency_key' => $this->idempotencyKey],
                ]);

                // 4. Ledger Entry
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'bank_account_id' => $account->id,
                    'type' => 'debit',
                    'amount' => $this->amount,
                    'description' => "Bill Payment: {$biller->name} ({$this->customerIdentifier})",
                    'reference' => $reference,
                    'balance_after' => $account->available_balance,
                ]);

                session()->flash('success', 'Payment successful!');
                return redirect()->route('dashboard');
            });
        });
    }

    public function with()
    {
        return [
            'billers' => Biller::where('is_active', true)->get()->groupBy('category.value'),
        ];
    }
}; ?>

<div>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
        @if ($step === 1)
            <!-- Service selection UI (already sophisticated) -->
            <div class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Select a Service</h3>
                
                @foreach ($billers as $category => $items)
                    <div class="space-y-3">
                        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider">{{ $category }}</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach ($items as $biller)
                                <button wire:click="selectBiller('{{ $biller->id }}')" 
                                        class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold">{{ substr($biller->name, 0, 1) }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $biller->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="max-w-md mx-auto">
                <button wire:click="$set('step', 1)" class="mb-6 text-xs font-bold text-brand-primary uppercase tracking-widest hover:underline flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to services
                </button>

                <div class="bg-slate-50 rounded-2xl p-6 mb-6 border border-brand-border flex items-center gap-4">
                    <div class="h-12 w-12 bg-white rounded-xl flex items-center justify-center shadow-sm border border-brand-border flex-shrink-0">
                        <span class="text-brand-primary font-bold text-lg cursor-default">{{ substr(\App\Models\Biller::find($selectedBillerId)->name ?? 'B', 0, 1) }}</span>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-brand-text-secondary tracking-widest">Selected Biller</p>
                        <p class="font-bold text-brand-text-primary">{{ \App\Models\Biller::find($selectedBillerId)->name ?? 'Service' }}</p>
                    </div>
                </div>

                <form wire:submit="processPayment" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-brand-text-primary mb-2">Customer Identifier</label>
                        <input type="text" wire:model="customerIdentifier" class="block w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all" placeholder="Phone, Meter, or Decoder Number">
                        @error('customerIdentifier') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-brand-text-primary mb-2">Amount (₦)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-brand-text-secondary font-bold">₦</div>
                            <input type="number" wire:model="amount" class="block w-full pl-10 pr-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all" placeholder="0.00">
                        </div>
                        @error('amount') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-brand-text-primary mb-2">Transaction PIN</label>
                        <input type="password" wire:model="pin" maxlength="4" class="block w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-center tracking-[1em] text-lg transition-all" placeholder="••••">
                        @error('pin') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-primary w-full shadow-lg shadow-blue-100 uppercase tracking-wider text-sm">
                            <span wire:loading.remove wire:target="processPayment">Pay Bill Now</span>
                            <span wire:loading wire:target="processPayment">Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>