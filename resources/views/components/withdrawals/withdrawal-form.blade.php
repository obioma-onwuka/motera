<?php

use Livewire\Volt\Component;
use App\Actions\Withdrawals\RequestWithdrawalAction;
use App\Exceptions\AccountRestrictedException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\TooManyPinAttemptsException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $amount;
    public $bank_name;
    public $account_number;
    public $account_name;
    public $pin = '';

    protected function rules()
    {
        return [
            'amount' => 'required|numeric|min:' . config('motera.limits.min_withdrawal'),
            'bank_name' => 'required|string',
            'account_number' => 'required|numeric|digits:10',
            'account_name' => 'required|string|min:3',
            'pin' => 'required|digits:4',
        ];
    }

    public function submit(RequestWithdrawalAction $action)
    {
        $this->validate();

        try {
            $action->execute(Auth::user(), [
                'amount' => $this->amount,
                'pin' => $this->pin,
                'bank_name' => $this->bank_name,
                'account_number' => $this->account_number,
                'account_name' => $this->account_name,
            ]);

            $this->pin = '';

            session()->flash('success', 'Withdrawal request submitted! It will be processed soon.');
            return redirect()->route('dashboard');
        } catch (InvalidPinException|TooManyPinAttemptsException $e) {
            $this->addError('pin', $e->getMessage());
        } catch (InsufficientFundsException|AccountRestrictedException|ThrottleRequestsException $e) {
            $this->addError('amount', $e->getMessage());
        }
    }
};
?>

<div class="space-y-6">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-brand-border">
        <div class="mb-6 p-4 bg-slate-50 rounded-2xl border border-brand-border flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-brand-text-secondary">Available for Withdrawal</p>
                <p class="text-xl font-bold text-brand-primary">${{ number_format(Auth::user()->primaryAccount?->available_balance ?? 0, 2) }}</p>
            </div>
            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-brand-primary">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <form wire:submit="submit" class="space-y-5">
            <div>
                <label for="amount" class="block text-sm font-semibold text-brand-text-primary mb-2">Amount to Withdraw ($)</label>
                <input id="amount" type="number" wire:model.blur="amount" class="block w-full px-4 py-4 rounded-2xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="Min {{ number_format(config('motera.limits.min_withdrawal'), 2) }}">
                @error('amount') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-2 border-t border-brand-border">
                <p class="text-[10px] font-bold text-brand-text-secondary uppercase mb-4">Destination Bank Details</p>

                <div class="space-y-4">
                    <div>
                        <label for="bank_name" class="block text-xs font-semibold text-brand-text-secondary mb-1">Bank Name</label>
                        <select id="bank_name" wire:model="bank_name" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary bg-white">
                            <option value="">Select Bank</option>
                            @foreach(config('motera.banks') as $bank)
                                <option value="{{ $bank['code'] }}">{{ $bank['name'] }}</option>
                            @endforeach
                        </select>
                        @error('bank_name') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="account_number" class="block text-xs font-semibold text-brand-text-secondary mb-1">Account Number</label>
                        <input id="account_number" type="text" wire:model="account_number" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="10 Digits">
                        @error('account_number') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="account_name" class="block text-xs font-semibold text-brand-text-secondary mb-1">Account Name</label>
                        <input id="account_name" type="text" wire:model="account_name" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="Full Name as it appears on bank">
                        @error('account_name') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-brand-border">
                <label for="pin" class="block text-sm font-semibold text-brand-text-primary mb-2">Transaction PIN</label>
                <input id="pin" type="password" wire:model="pin" maxlength="4" inputmode="numeric" autocomplete="one-time-code" class="block w-full px-4 py-4 rounded-2xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-center tracking-[1em] text-lg transition-all" placeholder="••••">
                @error('pin') <span class="text-xs text-brand-danger mt-1 block">{{ $message }}</span> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" class="btn-primary w-full shadow-lg shadow-blue-100 mt-4">
                <span wire:loading.remove>Request Withdrawal</span>
                <span wire:loading>Processing Request...</span>
            </button>
        </form>
    </div>
</div>