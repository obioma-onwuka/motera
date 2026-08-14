<?php

use App\Actions\Transfers\InitiateInternalTransferAction;
use App\Exceptions\AccountRestrictedException;
use App\Exceptions\IdempotencyViolationException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\TooManyPinAttemptsException;
use App\Models\BankAccount;
use App\Services\NameMasker;
use App\Traits\InteractsWithIdempotency;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    use InteractsWithIdempotency;

    public $accountNumber;
    public $recipientName = null;
    public $recipientFound = false;
    public $amount;
    public $description;
    public $step = 1;
    public $pin = '';
    public $idempotencyKey;

    public function mount()
    {
        $this->idempotencyKey = Str::uuid()->toString();
    }

    public function updatedAccountNumber($value)
    {
        $this->recipientName = null;
        $this->recipientFound = false;

        if (blank($value)) {
            return;
        }

        $account = RateLimiter::attempt('account-lookup:'.auth()->id(), 10, function () use ($value) {
            return BankAccount::where('account_number', $value)->first();
        }, 60);

        if ($account === false) {
            $this->addError('accountNumber', 'Too many lookups. Try again in a minute.');

            return;
        }

        if ($account instanceof BankAccount && $account->id !== auth()->user()->primaryAccount?->id) {
            $this->recipientName = NameMasker::mask($account->user->name);
            $this->recipientFound = true;
        }
    }

    public function confirmTransfer()
    {
        $this->validate([
            'accountNumber' => ['required', 'string', function ($attr, $value, $fail) {
                if (! $this->recipientFound) {
                    $fail('Recipient account not found.');
                }
            }],
            'amount' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/', 'min:'.config('motera.limits.min_transfer')],
            'description' => 'nullable|string|max:100',
        ]);

        $this->step = 2;
    }

    public function processTransfer(InitiateInternalTransferAction $action)
    {
        $validated = $this->validate([
            'accountNumber' => ['required', 'string', function ($attr, $value, $fail) {
                if (! $this->recipientFound) {
                    $fail('Recipient account not found.');
                }
            }],
            'amount' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/', 'min:'.config('motera.limits.min_transfer')],
            'description' => 'nullable|string|max:100',
            'pin' => 'required|digits:4',
        ]);

        try {
            $this->idempotent($this->idempotencyKey, fn () => $action->execute(auth()->user(), [
                'recipient_account_number' => $validated['accountNumber'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'pin' => $validated['pin'],
            ]));
        } catch (IdempotencyViolationException $e) {
            session()->flash('info', 'This transfer was already submitted.');
            $this->redirect(route('transactions.index'), navigate: true);

            return;
        } catch (InvalidPinException|TooManyPinAttemptsException $e) {
            $this->addError('pin', $e->getMessage());

            return;
        } catch (InsufficientFundsException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        } catch (AccountRestrictedException $e) {
            $this->addError('accountNumber', $e->getMessage());

            return;
        } catch (ThrottleRequestsException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        session()->flash('success', 'Transfer completed successfully!');
        $this->redirect(route('transactions.index'), navigate: true);
    }
}; ?>

<div class="max-w-md mx-auto">
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-brand-border">
        @if ($step === 1)
            <h3 class="text-xl font-bold text-brand-text-primary mb-6">Internal Transfer</h3>

            <form wire:submit="confirmTransfer" class="space-y-4">
                <div>
                    <label for="account-number" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Account Number</label>
                    <div class="relative">
                        <input id="account-number" type="text" wire:model.live.debounce.500ms="accountNumber"
                               class="w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-gray-50 transition-all"
                               placeholder="Enter 10-digit number">
                        @if ($recipientFound)
                            <div class="mt-2 p-3 bg-blue-50 rounded-xl border border-blue-100 flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-blue-600 flex items-center justify-center text-[10px] text-white font-bold">
                                    {{ substr($recipientName, 0, 1) }}
                                </div>
                                <span class="text-xs font-bold text-blue-700 uppercase tracking-tighter">Sending to: {{ $recipientName }}</span>
                            </div>
                        @endif
                    </div>
                    @error('accountNumber') <span class="text-brand-danger text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="amount" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Amount</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm font-bold">$</span>
                        </div>
                        <input id="amount" type="number" wire:model="amount"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-gray-50 transition-all"
                               placeholder="0.00">
                    </div>
                    @error('amount') <span class="text-brand-danger text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Description (Optional)</label>
                    <input id="description" type="text" wire:model="description"
                           class="w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-gray-50 transition-all"
                           placeholder="e.g. For dinner">
                    @error('description') <span class="text-brand-danger text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4">
                    <button type="submit"
                            class="btn-primary w-full shadow-lg shadow-blue-100"
                            {{ !$recipientFound ? 'disabled' : '' }}>
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
                    <h4 class="text-3xl font-black text-brand-primary">${{ number_format($amount, 2) }}</h4>
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
                    <label for="pin" class="block text-sm font-semibold text-brand-text-primary mb-2">Transaction PIN</label>
                    <input id="pin" type="password" wire:model="pin" maxlength="4" class="block w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-center tracking-[1em] text-lg transition-all" placeholder="••••">
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
