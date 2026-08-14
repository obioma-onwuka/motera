<?php

use Livewire\Volt\Component;
use App\Models\DepositRequest;
use App\Models\WithdrawalRequest;
use App\Models\CardRequest;
use App\Models\KycSubmission;
use App\Enums\RequestStatus;
use App\Enums\KycStatus;

new class extends Component {
    public function with()
    {
        $user = auth()->user();

        return [
            'pendingDeposits' => DepositRequest::where('user_id', $user->id)->where('status', RequestStatus::PENDING)->count(),
            'pendingWithdrawals' => WithdrawalRequest::where('user_id', $user->id)->where('status', RequestStatus::PENDING)->count(),
            'pendingCards' => CardRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'kycPending' => KycSubmission::where('user_id', $user->id)->where('status', KycStatus::PENDING)->exists(),
        ];
    }
}; ?>

<div wire:poll.30s class="flex h-full flex-col">
    <div class="h-16 px-6 flex items-center">
        <a href="{{ route('dashboard') }}">
            <span class="text-2xl font-bold text-brand-primary">MOTERA</span>
        </a>
    </div>

    <nav class="flex-1 space-y-1 px-4 py-4 overflow-y-auto">
        <a href="{{ route('dashboard') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('dashboard'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('dashboard'),
           ])
           @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            </span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('transfers.create') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('transfers.create'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('transfers.create'),
           ])
           @if(request()->routeIs('transfers.create')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            </span>
            <span>Transfers</span>
        </a>

        <a href="{{ route('deposits.create') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('deposits.create'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('deposits.create'),
           ])
           @if(request()->routeIs('deposits.create')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            </span>
            <span>Add Money</span>
            @if ($pendingDeposits > 0)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-teal-600">{{ $pendingDeposits }}</span>
            @endif
        </a>

        <a href="{{ route('withdrawals.create') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('withdrawals.create'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('withdrawals.create'),
           ])
           @if(request()->routeIs('withdrawals.create')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
            </span>
            <span>Withdrawals</span>
            @if ($pendingWithdrawals > 0)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-red-600">{{ $pendingWithdrawals }}</span>
            @endif
        </a>

        <a href="{{ route('bills.index') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('bills.index'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('bills.index'),
           ])
           @if(request()->routeIs('bills.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <span>Bills & Payments</span>
        </a>

        <a href="{{ route('transactions.index') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('transactions.index'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('transactions.index'),
           ])
           @if(request()->routeIs('transactions.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </span>
            <span>Transaction History</span>
        </a>

        <a href="{{ route('cards.index') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('cards.index'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('cards.index'),
           ])
           @if(request()->routeIs('cards.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
            </span>
            <span>Cards</span>
            @if ($pendingCards > 0)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-purple-600">{{ $pendingCards }}</span>
            @endif
        </a>

        <a href="{{ route('account.upgrade') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('account.upgrade'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('account.upgrade'),
           ])
           @if(request()->routeIs('account.upgrade')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <span>Account Upgrades</span>
        </a>

        <a href="{{ route('compliance.kyc') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('compliance.kyc'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('compliance.kyc'),
           ])
           @if(request()->routeIs('compliance.kyc')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </span>
            <span>Identity Verification</span>
            @if ($kycPending)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-orange-600">!</span>
            @endif
        </a>

        <a href="{{ route('notifications.index') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('notifications.index'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('notifications.index'),
           ])
           @if(request()->routeIs('notifications.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            </span>
            <span>Notifications</span>
        </a>

        <a href="{{ route('profile.edit') }}"
           @class([
               'flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium transition-colors',
               'bg-blue-50 text-brand-primary' => request()->routeIs('profile.edit'),
               'text-brand-text-secondary hover:bg-gray-50' => ! request()->routeIs('profile.edit'),
           ])
           @if(request()->routeIs('profile.edit')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </span>
            <span>Profile</span>
        </a>
    </nav>

    <div class="border-t border-brand-border p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-4 py-2 text-sm font-medium text-brand-danger hover:bg-red-50 transition-colors">
                <span class="h-5 w-5 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                </span>
                Logout
            </button>
        </form>
    </div>
</div>
