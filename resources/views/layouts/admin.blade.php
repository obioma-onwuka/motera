<x-base-layout>
    <div class="min-h-full flex flex-col lg:flex-row h-screen">
        <!-- Sidebar -->
        <aside class="w-full lg:w-64 border-r border-brand-border bg-slate-900 text-white flex-shrink-0 overflow-y-auto">
            <div class="p-6">
                <span class="text-2xl font-bold">MOTERA <span class="text-xs font-normal text-slate-400">Admin</span></span>
            </div>
            <nav class="px-4 py-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    Overview
                </a>
                <a href="{{ route('admin.customers.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.customers.index') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    Customers
                </a>
                <a href="{{ route('admin.compliance.kyc.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.compliance.kyc.index') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    KYC Reviews
                </a>
                <a href="{{ route('admin.financials.deposits.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.financials.deposits.index') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    Deposit Requests
                </a>
                <a href="{{ route('admin.financials.withdrawals.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.financials.withdrawals.index') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    Withdrawal Requests
                </a>
                <a href="{{ route('admin.billers.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.billers.index') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    Manage Billers
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center px-4 py-3 text-sm font-medium {{ request()->routeIs('admin.audit-logs.index') ? 'bg-blue-600' : 'text-slate-300 hover:bg-slate-800' }} rounded-xl">
                    Audit Logs
               </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-slate-50 flex flex-col">
            <!-- Admin Topbar -->
            <header class="h-16 border-b border-brand-border bg-white flex items-center justify-between px-8 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-bold">Admin Console</h2>
                </div>
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-xs font-bold">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-brand-text-secondary">Super Admin</p>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 font-bold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Admin Page Content -->
            <div class="p-8">
                @if (isset($header))
                    <div class="mb-8">
                        {{ $header }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</x-base-layout>
