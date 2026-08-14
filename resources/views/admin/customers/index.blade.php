<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Customers</h2>
        <p class="text-sm text-brand-text-secondary">Overview of customer accounts and balances.</p>
    </x-slot>

    <div class="space-y-6">
        <livewire:admin.customer-list />
    </div>
</x-admin-layout>
