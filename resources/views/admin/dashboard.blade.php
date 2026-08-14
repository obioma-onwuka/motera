<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">Operations Hub</h2>
        <p class="text-sm text-brand-text-secondary">Platform-wide metrics, queues, and monitoring.</p>
    </x-slot>

    <div class="space-y-8">
        <livewire:admin.metrics />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="lg:col-span-2">
                <livewire:admin.volume-chart />
            </div>
            <livewire:admin.deposit-withdrawal-chart />
            <livewire:admin.tier-donut />
            <div class="lg:col-span-2">
                <livewire:admin.user-growth-chart />
            </div>
        </div>

        <livewire:admin.recent-transactions />
    </div>
</x-admin-layout>
