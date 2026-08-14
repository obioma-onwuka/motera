<x-customer-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-brand-text-secondary">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h2 class="text-2xl font-bold">Deposit Funds</h2>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto">
        <livewire:deposits.manual-deposit-form />
    </div>
</x-customer-layout>
