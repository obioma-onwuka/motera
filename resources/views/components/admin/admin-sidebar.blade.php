<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Gate;
use App\Models\KycSubmission;
use App\Models\DepositRequest;
use App\Models\WithdrawalRequest;
use App\Enums\KycStatus;
use App\Enums\RequestStatus;

new class extends Component {
    public function with()
    {
        Gate::allowIf(fn ($user) => $user->hasAnyRole(['Super Admin', 'Operations Admin', 'Compliance Admin', 'Support Admin']));

        $user = auth()->user();

        return [
            'pendingKyc' => $user->can('review-kyc') ? KycSubmission::where('status', KycStatus::PENDING)->count() : 0,
            'pendingDeposits' => $user->can('approve-deposits') ? DepositRequest::where('status', RequestStatus::PENDING)->count() : 0,
            'pendingWithdrawals' => $user->can('approve-withdrawals') ? WithdrawalRequest::where('status', RequestStatus::PENDING)->count() : 0,
        ];
    }
}; ?>

<div wire:poll.30s>
    <nav class="px-4 py-4 space-y-1">
        <a href="{{ route('admin.dashboard') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.dashboard'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.dashboard'),
           ])
           @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            </span>
            <span>Overview</span>
        </a>

        <a href="{{ route('admin.customers.index') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.customers.index'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.customers.index'),
           ])
           @if (request()->routeIs('admin.customers.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </span>
            <span>Customers</span>
        </a>

        <a href="{{ route('admin.compliance.kyc.index') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.compliance.kyc.index'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.compliance.kyc.index'),
           ])
           @if (request()->routeIs('admin.compliance.kyc.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </span>
            <span>KYC Reviews</span>
            @if ($pendingKyc > 0)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-orange-600">{{ $pendingKyc }}</span>
            @endif
        </a>

        <a href="{{ route('admin.financials.deposits.index') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.financials.deposits.index'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.financials.deposits.index'),
           ])
           @if (request()->routeIs('admin.financials.deposits.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            </span>
            <span>Deposit Requests</span>
            @if ($pendingDeposits > 0)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-teal-600">{{ $pendingDeposits }}</span>
            @endif
        </a>

        <a href="{{ route('admin.financials.withdrawals.index') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.financials.withdrawals.index'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.financials.withdrawals.index'),
           ])
           @if (request()->routeIs('admin.financials.withdrawals.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
            </span>
            <span>Withdrawal Requests</span>
            @if ($pendingWithdrawals > 0)
                <span class="ml-auto h-5 min-w-[1.25rem] px-1.5 rounded-lg text-[10px] font-black flex items-center justify-center text-white bg-red-600">{{ $pendingWithdrawals }}</span>
            @endif
        </a>

        <a href="{{ route('admin.billers.index') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.billers.index'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.billers.index'),
           ])
           @if (request()->routeIs('admin.billers.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
            </span>
            <span>Manage Billers</span>
        </a>

        <a href="{{ route('admin.audit-logs.index') }}"
           @class([
               'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-colors',
               'bg-blue-600 text-white shadow-lg shadow-blue-900/40' => request()->routeIs('admin.audit-logs.index'),
               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs('admin.audit-logs.index'),
           ])
           @if (request()->routeIs('admin.audit-logs.index')) aria-current="page" @endif>
            <span class="h-5 w-5 flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </span>
            <span>Audit Logs</span>
        </a>
    </nav>
</div>
