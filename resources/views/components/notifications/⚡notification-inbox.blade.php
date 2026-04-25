<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public function with()
    {
        return [
            'notifications' => Auth::user()->notifications()->latest()->get(),
        ];
    }

    public function markAsRead($id)
    {
        Auth::user()->notifications()->find($id)->markAsRead();
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }
};
?>

<div class="space-y-6">
    @if(Auth::user()->unreadNotifications->count() > 0)
        <div class="flex justify-end">
            <button wire:click="markAllAsRead" class="text-xs font-bold text-brand-primary hover:underline">Mark all as read</button>
        </div>
    @endif

    <div class="bg-white rounded-3xl overflow-hidden border border-brand-border shadow-sm">
        <div class="divide-y divide-brand-border">
            @forelse($notifications as $notification)
                <div class="p-6 flex items-start gap-4 hover:bg-slate-50 transition-colors {{ $notification->read_at ? 'opacity-60' : '' }}">
                    <div class="h-10 w-10 rounded-2xl bg-blue-50 text-brand-primary flex items-center justify-center flex-shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-bold text-sm text-brand-text-primary">{{ $notification->data['title'] ?? 'System Update' }}</p>
                            <span class="text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-brand-text-secondary leading-relaxed">{{ $notification->data['message'] ?? '' }}</p>
                        
                        @if(!$notification->read_at)
                            <button wire:click="markAsRead('{{ $notification->id }}')" class="mt-3 text-[10px] font-bold text-brand-primary uppercase tracking-widest hover:underline">Mark as read</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="text-slate-300 mb-4 flex justify-center">
                        <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                    </div>
                    <p class="text-brand-text-secondary font-medium">Your inbox is empty.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>