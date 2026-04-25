<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new class extends Component {
    public $current_password;
    public $password;
    public $password_confirmation;
    public $two_factor_method = 'email'; // email, app, none

    public $pin;
    public $pin_confirmation;
    public $pin_password;

    public function mount()
    {
        // ...
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('success_password', 'Password updated successfully.');
    }

    public function setTransactionPin()
    {
        if (auth()->user()->hasTransactionPin()) {
            return;
        }

        $this->validate([
            'pin' => 'required|digits:4|confirmed',
            'pin_password' => ['required', 'current_password'],
        ]);

        auth()->user()->update([
            'transaction_pin' => Hash::make($this->pin),
        ]);

        $this->reset(['pin', 'pin_confirmation', 'pin_password']);
        session()->flash('success_pin', 'Transaction PIN has been set successfully.');
    }

    public function toggleTwoFactor()
    {
        // Logic for enabling/disabling 2FA
        session()->flash('success', '2FA settings updated.');
    }
}; ?>

<div class="space-y-8">
    <!-- Password Section -->
    <div class="bg-white p-6 rounded-3xl border border-brand-border shadow-sm">
        <h3 class="text-lg font-bold mb-6">Security & Password</h3>
        
        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Password</label>
                <input type="password" wire:model="current_password" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
                @error('current_password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New Password</label>
                    <input type="password" wire:model="password" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm New Password</label>
                    <input type="password" wire:model="password_confirmation" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
                </div>
            </div>

            @if (session('success_password'))
                <div class="p-3 bg-green-50 text-brand-success rounded-xl text-sm font-medium border border-green-200">
                    {{ session('success_password') }}
                </div>
            @endif

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full md:w-auto px-8">Update Password</button>
            </div>
        </form>
    </div>

    <!-- Transaction PIN Section -->
    <div class="bg-white p-6 rounded-3xl border border-brand-border shadow-sm">
        <h3 class="text-lg font-bold mb-2">Transaction PIN</h3>
        <p class="text-sm text-brand-text-secondary mb-6">Your 4-digit PIN is required to authorize transfers, bills, and card requests.</p>
        
        @if(auth()->user()->hasTransactionPin())
            <div class="p-4 bg-slate-50 rounded-2xl border border-brand-border flex items-center gap-4">
                <div class="h-10 w-10 bg-green-50 text-brand-success rounded-xl flex items-center justify-center">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-brand-text-primary">PIN is active</p>
                    <p class="text-xs text-brand-text-secondary">Your account is secured with a Transaction PIN.</p>
                </div>
                <button class="ml-auto text-xs font-bold text-brand-primary uppercase tracking-widest hover:underline">Change PIN</button>
            </div>
        @else
            <form wire:submit="setTransactionPin" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Set 4-Digit PIN</label>
                        <input type="password" wire:model="pin" maxlength="4" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary text-center tracking-[1em] text-lg transition-all" placeholder="••••">
                        @error('pin') <span class="text-brand-danger text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm PIN</label>
                        <input type="password" wire:model="pin_confirmation" maxlength="4" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary text-center tracking-[1em] text-lg transition-all" placeholder="••••">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Account Password</label>
                    <input type="password" wire:model="pin_password" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
                    <p class="text-[10px] text-gray-400 mt-1">We need your password to verify this change.</p>
                    @error('pin_password') <span class="text-brand-danger text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                @if (session('success_pin'))
                    <div class="p-3 bg-green-50 text-brand-success rounded-xl text-sm font-medium border border-green-200">
                        {{ session('success_pin') }}
                    </div>
                @endif

                <div class="pt-2">
                    <button type="submit" class="btn-primary w-full md:w-auto px-8">Set Transaction PIN</button>
                </div>
            </form>
        @endif
    </div>

    <!-- 2FA Section -->
    <div class="bg-white p-6 rounded-3xl border border-brand-border shadow-sm">
        <h3 class="text-lg font-bold mb-2">Two-Step Verification</h3>
        <p class="text-sm text-brand-text-secondary mb-6">Add an extra layer of security to your account.</p>

        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 rounded-2xl border border-brand-border hover:border-brand-primary transition-colors cursor-pointer group">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 bg-blue-50 text-brand-primary rounded-xl flex items-center justify-center group-hover:bg-brand-primary group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold">Email OTP</p>
                        <p class="text-xs text-gray-500">Receive a code via email for every login.</p>
                    </div>
                </div>
                <div class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" checked class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 rounded-2xl border border-brand-border hover:border-brand-primary transition-colors cursor-pointer group">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold">Authenticator App</p>
                        <p class="text-xs text-gray-500">Use Google Authenticator or Authy.</p>
                    </div>
                </div>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Available on Staging</span>
            </div>
        </div>
    </div>
</div>
