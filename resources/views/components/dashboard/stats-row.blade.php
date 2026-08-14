<?php

use Livewire\Volt\Component;
use App\Enums\RequestStatus;
use App\Models\CardRequest;
use App\Models\DepositRequest;
use App\Models\WithdrawalRequest;

new class extends Component {
    public function with(): array
    {
        $user = auth()->user();
        $account = $user->primaryAccount;
        $startOfMonth = now()->startOfMonth();

        return [
            'frozenFunds' => $account ? max((float) $account->ledger_balance - (float) $account->available_balance, 0) : 0.0,
            'monthIn' => $account ? (float) $account->ledgerEntries()->where('type', 'credit')->where('created_at', '>=', $startOfMonth)->sum('amount') : 0.0,
            'monthOut' => $account ? (float) $account->ledgerEntries()->where('type', 'debit')->where('created_at', '>=', $startOfMonth)->sum('amount') : 0.0,
            'pendingRequests' => DepositRequest::where('user_id', $user->id)->where('status', RequestStatus::PENDING)->count()
                + WithdrawalRequest::where('user_id', $user->id)->where('status', RequestStatus::PENDING)->count()
                + CardRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
        ];
    }
}; ?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <!-- Frozen Funds -->
    <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
        <div class="flex items-start justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Frozen Funds</p>
            <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
        </div>
        <h3 class="text-2xl font-black tracking-tight tabular-nums text-slate-900">${{ number_format($frozenFunds, 2) }}</h3>
    </div>

    <!-- This Month In -->
    <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
        <div class="flex items-start justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">This Month In</p>
            <div class="h-10 w-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
            </div>
        </div>
        <h3 class="text-2xl font-black tracking-tight tabular-nums text-slate-900">+${{ number_format($monthIn, 2) }}</h3>
    </div>

    <!-- This Month Out -->
    <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
        <div class="flex items-start justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">This Month Out</p>
            <div class="h-10 w-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
            </div>
        </div>
        <h3 class="text-2xl font-black tracking-tight tabular-nums text-slate-900">−${{ number_format($monthOut, 2) }}</h3>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
        <div class="flex items-start justify-between">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pending Requests</p>
            <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>
        <h3 class="text-2xl font-black tracking-tight tabular-nums text-slate-900">{{ number_format($pendingRequests) }}</h3>
    </div>
</div>
