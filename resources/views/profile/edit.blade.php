<x-customer-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Security & Settings</h2>
        <p class="text-xs text-gray-500 font-medium mt-1">Manage your account access and security layers.</p>
    </x-slot>

    <div class="py-2">
        <livewire:settings.profile-settings />
        <livewire:settings.security-settings />
    </div>
</x-customer-layout>
