<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold">System Audit Logs</h2>
        <p class="text-sm text-brand-text-secondary">Track every administrative action and system-wide event for accountability.</p>
    </x-slot>

    <div class="space-y-6">
        <livewire:admin.audit-logs />
    </div>
</x-admin-layout>
