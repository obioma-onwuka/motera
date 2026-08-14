<x-base-layout>
    <div class="min-h-full flex flex-col lg:flex-row h-screen">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:w-64 flex-col border-r border-brand-border bg-slate-900 text-white flex-shrink-0 overflow-y-auto">
            <div class="p-6">
                <span class="text-2xl font-bold">MOTERA <span class="text-xs font-normal text-slate-400">Admin</span></span>
            </div>
            <livewire:admin.admin-sidebar />
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-slate-50 flex flex-col">
            <!-- Admin Topbar -->
            <header class="h-16 border-b border-brand-border bg-white flex items-center justify-between px-8 flex-shrink-0">
                @php
                    $pageTitles = [
                        'admin.dashboard' => 'Operations Hub',
                        'admin.customers.index' => 'Customers',
                        'admin.compliance.kyc.index' => 'KYC Reviews',
                        'admin.financials.deposits.index' => 'Deposit Requests',
                        'admin.financials.withdrawals.index' => 'Withdrawal Requests',
                        'admin.billers.index' => 'Manage Billers',
                        'admin.audit-logs.index' => 'Audit Logs',
                    ];
                    $topbarTitle = $pageTitles[request()->route()?->getName()] ?? 'Admin Console';
                @endphp
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-bold">{{ $topbarTitle }}</h2>
                </div>
                <div class="flex items-center gap-6">
                    <a href="{{ route('notifications.index') }}" class="relative h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:text-brand-primary transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                        @endif
                    </a>

                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="true" class="flex items-center gap-3 rounded-xl px-2 py-1.5 transition hover:bg-slate-100 focus:outline-none">
                            <div class="text-right hidden sm:block">
                                <p class="text-xs font-bold">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-brand-text-secondary">{{ auth()->user()->roles->pluck('name')->first() ?? 'Staff' }}</p>
                            </div>
                            <div class="h-10 w-10 rounded-xl bg-brand-primary flex items-center justify-center text-white font-bold">{{ substr(auth()->user()->name, 0, 1) }}</div>
                            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute right-0 top-full mt-2 z-50 w-56 rounded-xl border border-brand-border bg-white shadow-lg py-2">
                            <div class="px-4 py-3 border-b border-brand-border">
                                <p class="text-sm font-bold truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] uppercase font-black tracking-widest text-blue-600">{{ auth()->user()->roles->pluck('name')->first() ?? 'Staff' }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-brand-danger hover:bg-red-50">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Admin Page Content -->
            <div class="p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8">
                @if (isset($header))
                    <div class="mb-8">
                        {{ $header }}
                    </div>
                @endif

                {{ $slot }}
            </div>

            <!-- Mobile Bottom Nav -->
            <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-brand-border bg-white px-2 py-3 lg:hidden flex justify-around">
                <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('admin.dashboard') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Overview</span>
                </a>
                <a href="{{ route('admin.compliance.kyc.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('admin.compliance.kyc.index') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">KYC</span>
                </a>
                <a href="{{ route('admin.financials.deposits.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('admin.financials.deposits.index') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Deposits</span>
                </a>
                <a href="{{ route('admin.financials.withdrawals.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('admin.financials.withdrawals.index') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Withdrawals</span>
                </a>
                <a href="{{ route('admin.customers.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('admin.customers.index') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Customers</span>
                </a>
            </div>
        </main>
    </div>
</x-base-layout>
