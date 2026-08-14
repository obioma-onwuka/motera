<x-customer-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-gray-900  tracking-tight">Good day, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
                <p class="text-xs text-gray-500 font-medium mt-1">Welcome back to your MOTERA dashboard.</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex -space-x-2">
                    <div class="h-8 w-8 rounded-full border-2 border-white bg-blue-100 flex items-center justify-center text-[10px] font-bold text-blue-600">{{ ucwords(str_replace('_', ' ', auth()->user()->primaryAccount?->tier?->value ?? 'tier_1')) }}</div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">
        <!-- KYC/Update Banner -->
        @php
            $kyc = auth()->user()->kycSubmission;
        @endphp
        
        @if(!$kyc)
            <div class="relative overflow-hidden p-6 bg-gradient-to-br from-orange-500 to-orange-600 rounded-3xl text-white shadow-lg shadow-orange-100 animate-pulse">
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div>
                            <p class="font-black text-lg">Verify Identity</p>
                            <p class="text-xs text-orange-50 opacity-90">Unlock daily limits and global cards.</p>
                        </div>
                    </div>
                    <a href="{{ route('compliance.kyc') }}" class="px-5 py-2.5 bg-white text-orange-600 rounded-xl text-xs font-black shadow-xl">Complete KYC</a>
                </div>
                <div class="absolute -right-10 -bottom-10 h-32 w-32 bg-white/10 rounded-full"></div>
            </div>
        @elseif($kyc->status->value === 'pending')
             <div class="p-5 bg-blue-50 border border-blue-100 rounded-3xl flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <svg class="h-7 w-7 animate-spin-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-blue-900">KYC Under Review</p>
                    <p class="text-xs text-blue-700">We're verifying your documents. This usually takes 24h.</p>
                </div>
            </div>
        @endif

        <!-- Card Section (Horizontal Scroll on Mobile) -->
        <div class="flex gap-4 overflow-x-auto pb-4 no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
            <!-- Balance Card -->
            <div class="relative min-w-[300px] w-full sm:w-[400px] flex-shrink-0 overflow-hidden rounded-[2.5rem] bg-brand-primary p-8 text-white shadow-2xl shadow-blue-200">
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-200 opacity-80">Available Balance</p>
                            <h3 class="mt-1 text-4xl font-black tracking-tighter">
                                <span class="text-2xl font-medium opacity-80">$</span>
                                {{ number_format(auth()->user()->primaryAccount?->available_balance ?? 0, 2) }}
                            </h3>
                        </div>
                        <div class="h-10 w-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-md">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                    </div>
                    
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-blue-200 mb-1">Account Number</p>
                            <p class="font-mono text-xl tracking-[0.3em]">{{ auth()->user()->primaryAccount?->account_number ?? '----------' }}</p>
                        </div>
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-8 opacity-80" alt="Mastercard">
                    </div>
                </div>
                <!-- Abstract Design Elements -->
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -left-10 -bottom-10 h-32 w-32 rounded-full bg-blue-600/30 blur-2xl"></div>
            </div>

            <!-- Stats Card (Pocket) -->
            <div class="relative min-w-[300px] w-full sm:w-[350px] flex-shrink-0 overflow-hidden rounded-[2.5rem] bg-gray-900 p-8 text-white shadow-xl">
                 <div class="relative z-10">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-6">Savings Pocket</p>
                    <h3 class="text-3xl font-black tracking-tighter mb-10">
                        <span class="text-xl font-medium opacity-50">$</span>0.00
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-white/10 rounded-full text-[10px] font-black">LOCKED</span>
                        <span class="text-[10px] text-gray-500 font-bold">Earn up to 12% P.A.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Grid -->
        <div class="grid grid-cols-4 gap-4">
            <a href="{{ route('transfers.create') }}" class="flex flex-col items-center gap-3 group">
                <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-white border border-brand-border flex items-center justify-center text-brand-primary shadow-sm group-hover:bg-brand-primary group-hover:text-white group-hover:scale-110 transition-all duration-300">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                </div>
                <span class="text-[11px] font-black text-gray-900 tracking-tight">Transfer</span>
            </a>
            <a href="{{ route('deposits.create') }}" class="flex flex-col items-center gap-3 group">
                <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-white border border-brand-border flex items-center justify-center text-teal-600 shadow-sm group-hover:bg-brand-secondary group-hover:text-white group-hover:scale-110 transition-all duration-300">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                </div>
                <span class="text-[11px] font-black text-gray-900 tracking-tight">Add Money</span>
            </a>
            <a href="{{ route('bills.index') }}" class="flex flex-col items-center gap-3 group">
                <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-white border border-brand-border flex items-center justify-center text-purple-600 shadow-sm group-hover:bg-purple-600 group-hover:text-white group-hover:scale-110 transition-all duration-300">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-[11px] font-black text-gray-900 tracking-tight">Pay Bills</span>
            </a>
            <a href="{{ route('cards.index') }}" class="flex flex-col items-center gap-3 group">
                <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-white border border-brand-border flex items-center justify-center text-blue-600 shadow-sm group-hover:bg-blue-600 group-hover:text-white group-hover:scale-110 transition-all duration-300">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                </div>
                <span class="text-[11px] font-black text-gray-900 tracking-tight">Cards</span>
            </a>
        </div>

        <!-- Key Metrics -->
        <livewire:dashboard.stats-row />

        <!-- Insights -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2">
                <livewire:dashboard.cashflow-chart />
            </div>
            <livewire:dashboard.tier-progress />
        </div>

        <!-- Recent Activity Module -->
        <div class="bg-white rounded-[2rem] p-8 border border-brand-border shadow-sm">
            <div class="flex items-center justify-between mb-8">
                <h4 class="font-black text-gray-900 text-lg tracking-tight">Recent Activity</h4>
                <a href="{{ route('transactions.index') }}" class="text-xs font-black text-brand-primary bg-blue-50 px-4 py-2 rounded-full hover:bg-brand-primary hover:text-white transition-all">View All</a>
            </div>
            
            <livewire:transactions.transaction-history :limit="5" />
        </div>
    </div>
</x-customer-layout>
