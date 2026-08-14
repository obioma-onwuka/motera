<x-base-layout>
    <div class="min-h-full pb-20 lg:pb-0 lg:pl-64">
        <!-- Sidebar for Desktop -->
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-brand-border bg-white lg:block">
            <div class="flex h-full flex-col">
                <div class="flex h-16 items-center px-6">
                    <span class="text-2xl font-bold text-brand-primary">MOTERA</span>
                </div>
                <nav class="flex-1 space-y-1 px-4 py-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-brand-primary' : 'text-brand-text-secondary hover:bg-gray-50' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('transfers.create') }}" class="flex items-center rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('transfers.create') ? 'bg-blue-50 text-brand-primary' : 'text-brand-text-secondary hover:bg-gray-50' }}">
                        Transfers
                    </a>
                    <a href="{{ route('bills.index') }}" class="flex items-center rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('bills.index') ? 'bg-blue-50 text-brand-primary' : 'text-brand-text-secondary hover:bg-gray-50' }}">
                        Bills & Payments
                    </a>
                    <a href="{{ route('transactions.index') }}" class="flex items-center rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('transactions.index') ? 'bg-blue-50 text-brand-primary' : 'text-brand-text-secondary hover:bg-gray-50' }}">
                        Transaction History
                    </a>
                    <a href="{{ route('cards.index') }}" class="flex items-center rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('cards.index') ? 'bg-blue-50 text-brand-primary' : 'text-brand-text-secondary hover:bg-gray-50' }}">
                        Cards
                    </a>
                    <a href="{{ route('account.upgrade') }}" class="flex items-center rounded-lg px-4 py-2 text-sm font-medium {{ request()->routeIs('account.upgrade') ? 'bg-blue-50 text-brand-primary' : 'text-brand-text-secondary hover:bg-gray-50' }}">
                        Account Upgrades
                    </a>
                </nav>
                <div class="border-t border-brand-border p-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-brand-danger hover:bg-red-50">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Top Header for Mobile -->
        <header class="h-16 border-b border-brand-border bg-white flex items-center justify-between px-6 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-bold">Dashboard</h2>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('notifications.index') }}" class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 relative">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                        @endif
                    </a>
                    <div class="h-10 w-10 rounded-xl bg-brand-primary flex items-center justify-center text-white font-bold">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                </div>
            </header>

        <!-- Main Content -->
        <main class="py-6 px-4 sm:px-6 lg:px-8">
            @if (isset($header))
                <header class="mb-6">
                    {{ $header }}
                </header>
            @endif

            {{ $slot }}
        </main>

        <!-- Bottom Navigation for Mobile -->
        <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-brand-border bg-white px-2 py-3 lg:hidden">
            <div class="flex items-center justify-around">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('dashboard') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Home</span>
                </a>
                <a href="{{ route('transfers.create') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('transfers.create') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Send</span>
                </a>
                <a href="{{ route('bills.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('bills.index') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Bills</span>
                </a>
                <a href="{{ route('transactions.index') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('transactions.index') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">History</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-1 {{ request()->routeIs('profile.edit') ? 'text-brand-primary' : 'text-brand-text-secondary' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Profile</span>
                </a>
            </div>
        </nav>
    </div>
</x-base-layout>
