<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\KycSubmission;
use App\Models\DepositRequest;
use App\Models\WithdrawalRequest;
use App\Enums\TransactionStatus;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    public function with()
    {
        $this->authorize('view-metrics');

        return [
            'totalUsers' => User::count(),
            'totalAssets' => BankAccount::sum('ledger_balance'),
            'transactionVolume' => Transaction::where('status', TransactionStatus::SUCCESSFUL)->sum('amount'),
            'pendingKyc' => KycSubmission::where('status', 'pending')->count(),
            'pendingDeposits' => DepositRequest::where('status', 'pending')->count(),
            'pendingWithdrawals' => WithdrawalRequest::where('status', 'pending')->count(),
            'recentEvents' => Activity::latest()->limit(5)->get(),
        ];
    }
}; ?>

<div class="space-y-8">
    <!-- Top Level Platform Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Active Users</p>
            <div class="flex items-end justify-between uppercase">
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($totalUsers) }}</h3>
                <span class="text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-lg">Realtime</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Fluidity</p>
            <h3 class="text-3xl font-black text-blue-600 tracking-tight">${{ number_format($totalAssets, 2) }}</h3>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-brand-border shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Processed Volume</p>
            <h3 class="text-2xl font-black text-slate-700 tracking-tight">${{ number_format($transactionVolume, 2) }}</h3>
        </div>

        <div class="bg-slate-900 p-6 rounded-[2rem] text-white shadow-xl">
             <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Platform Status</p>
             <div class="flex items-center gap-2 mt-2">
                 <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
                 <span class="text-sm font-black tracking-tight">HEALTHY & OPTIMIZED</span>
             </div>
        </div>
    </div>

    <!-- Operations & System Events -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- critical queues -->
        <div class="lg:col-span-2 space-y-6">
             <div class="bg-white rounded-[2.5rem] border border-brand-border shadow-sm overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between">
                    <h4 class="font-black text-gray-900 text-lg">Critical Operations Queue</h4>
                    <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest">Awaiting Action</span>
                </div>
                <div class="divide-y divide-gray-50">
                    <a href="{{ route('admin.compliance.kyc.index') }}" class="flex items-center justify-between p-8 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-gray-900">Pending KYC Submissions</p>
                                <p class="text-xs text-gray-400">Verifying high-tier account upgrades</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-7 min-w-[1.75rem] px-2 flex items-center justify-center bg-orange-600 text-white rounded-lg text-[11px] font-black shadow-lg shadow-orange-100">{{ $pendingKyc }}</span>
                            <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </a>

                    <a href="{{ route('admin.financials.deposits.index') }}" class="flex items-center justify-between p-8 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-gray-900">Deposit Settlement</p>
                                <p class="text-xs text-gray-400">Reviewing incoming bank wires</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-7 min-w-[1.75rem] px-2 flex items-center justify-center bg-teal-600 text-white rounded-lg text-[11px] font-black shadow-lg shadow-teal-100">{{ $pendingDeposits }}</span>
                            <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </a>

                    <a href="{{ route('admin.financials.withdrawals.index') }}" class="flex items-center justify-between p-8 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-gray-900">Withdrawal Pipeline</p>
                                <p class="text-xs text-gray-400">Awaiting treasury approval for payout</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-7 min-w-[1.75rem] px-2 flex items-center justify-center bg-red-600 text-white rounded-lg text-[11px] font-black shadow-lg shadow-red-100">{{ $pendingWithdrawals }}</span>
                            <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="bg-white rounded-[2.5rem] border border-brand-border shadow-sm p-8">
            <h4 class="font-black text-gray-900 tracking-tight mb-8">System Pulse</h4>
            <div class="space-y-6">
                @foreach($recentEvents as $event)
                    <div class="flex gap-4">
                        <div class="h-2 w-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="text-xs font-bold text-slate-700 leading-tight">{{ $event->description }}</p>
                            <p class="text-[9px] text-slate-400 mt-1 uppercase font-black tracking-widest">{{ $event->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
                
                @if($recentEvents->isEmpty())
                    <p class="text-xs text-slate-400 text-center py-10 italic">No system events recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>