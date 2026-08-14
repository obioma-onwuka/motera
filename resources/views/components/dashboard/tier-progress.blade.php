<?php

use Livewire\Volt\Component;
use App\Enums\KycStatus;

new class extends Component {
    public function with(): array
    {
        $user = auth()->user();
        $tier = $user->primaryAccount?->tier?->value ?? 'tier_1';

        $names = ['tier_1' => 'Tier 1 (Starter)', 'tier_2' => 'Tier 2 (Silver)', 'tier_3' => 'Tier 3 (Gold)'];
        $shortNames = ['tier_1' => 'Tier 1', 'tier_2' => 'Tier 2', 'tier_3' => 'Tier 3'];

        $currentIndex = (int) str_replace('tier_', '', $tier);
        $dailyLimit = config('motera.limits.tier_limits.'.$tier);
        $nextTier = $currentIndex < 3 ? 'tier_'.($currentIndex + 1) : null;
        $nextTierName = $nextTier ? $shortNames[$nextTier] : null;
        $nextLimit = $nextTier ? config('motera.limits.tier_limits.'.$nextTier) : null;
        $kycPending = $user->kycSubmission?->status === KycStatus::PENDING;

        return [
            'tier' => $tier,
            'tierName' => $names[$tier],
            'currentIndex' => $currentIndex,
            'dailyLimit' => $dailyLimit,
            'nextTier' => $nextTier,
            'nextTierName' => $nextTierName,
            'nextLimit' => $nextLimit,
            'kycPending' => $kycPending,
        ];
    }
}; ?>

<div class="bg-white rounded-[2.5rem] p-8 border border-brand-border shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
            <div>
                <p class="text-sm font-bold text-brand-text-primary">Account Tier</p>
                <p class="text-[10px] text-brand-text-secondary uppercase font-bold tracking-widest mt-1">Daily limits & upgrades</p>
            </div>
        </div>
        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest flex-shrink-0">{{ $tierName }}</span>
    </div>

    <!-- Tier Stepper -->
    <div class="mt-8 mb-2 flex items-center justify-between relative px-2">
        <div class="absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 -z-10 -translate-y-1/2"></div>
        <div class="absolute top-1/2 left-0 h-0.5 bg-brand-primary -z-10 -translate-y-1/2 transition-all duration-300" style="width: {{ (($currentIndex - 1) * 50) }}%"></div>

        @foreach (range(1, 3) as $index)
            <div class="flex flex-col items-center">
                <div class="h-8 w-8 rounded-full flex items-center justify-center text-[10px] font-black z-10 transition-all {{ $index <= $currentIndex ? 'bg-brand-primary text-white ring-4 ring-blue-100' : 'bg-white text-slate-400 border-2 border-slate-200' }}">{{ $index }}</div>
                <span class="text-[10px] mt-2 font-bold {{ $index <= $currentIndex ? 'text-brand-primary' : 'text-slate-400' }}">Tier {{ $index }}</span>
                <span class="text-[9px] text-slate-400 mt-1">${{ number_format(config('motera.limits.tier_limits.tier_'.$index)) }}</span>
            </div>
        @endforeach
    </div>

    <!-- Body -->
    <p class="mt-6 text-xs text-slate-500">Daily limit <span class="font-bold text-brand-text-primary">${{ number_format($dailyLimit) }}</span>. Limits shown are indicative and not enforced by MOTERA at this time.</p>

    <!-- Footer -->
    @if($kycPending)
        <div class="mt-6 bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center gap-3">
            <svg class="h-5 w-5 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            <p class="text-xs font-bold text-blue-700">KYC under review — upgrades unlock after approval.</p>
        </div>
    @elseif($nextTier)
        <a href="{{ route('compliance.kyc') }}" class="btn-primary w-full shadow-lg shadow-blue-100 mt-6">Upgrade to {{ $nextTierName }}</a>
        <p class="text-[10px] text-slate-400 text-center mt-3">Unlock up to ${{ number_format($nextLimit) }} daily.</p>
    @else
        <div class="mt-6 bg-green-50 border border-green-100 rounded-2xl p-4 flex items-center gap-3">
            <svg class="h-5 w-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-xs font-bold text-green-600">You're at the highest tier.</p>
        </div>
    @endif
</div>
