<?php

use Livewire\Volt\Component;

new class extends Component {
    public function getTiers()
    {
        return [
            [
                'id' => 'tier_1',
                'name' => 'Tier 1 (Starter)',
                'limit' => '₦50,000',
                'color' => 'blue',
                'requirements' => ['Email Verification', 'Phone Number'],
                'features' => ['Local Transfers', 'Bill Payments'],
                'status' => auth()->user()->primaryAccount->tier === 'tier_1' ? 'current' : 'completed'
            ],
            [
                'id' => 'tier_2',
                'name' => 'Tier 2 (Silver)',
                'limit' => '₦500,000',
                'color' => 'teal',
                'requirements' => ['BVN Verification', 'Identity Document'],
                'features' => ['Higher Limits', 'Debit Cards'],
                'status' => auth()->user()->primaryAccount->tier === 'tier_2' ? 'current' : (auth()->user()->primaryAccount->tier === 'tier_1' ? 'available' : 'completed')
            ],
            [
                'id' => 'tier_3',
                'name' => 'Tier 3 (Gold)',
                'limit' => '₦5,000,000',
                'color' => 'purple',
                'requirements' => ['Proof of Address', 'Physical Verification'],
                'features' => ['Global Cards', 'Investment Access'],
                'status' => auth()->user()->primaryAccount->tier === 'tier_3' ? 'current' : 'available'
            ]
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($this->getTiers() as $tier)
            <div class="relative overflow-hidden bg-white p-6 rounded-[2.5rem] border {{ $tier['status'] === 'current' ? 'border-brand-primary ring-4 ring-blue-50' : 'border-brand-border' }} transition-all shadow-sm">
                <!-- Status Badge -->
                <div class="flex justify-between items-start mb-6">
                    <div class="h-12 w-12 rounded-2xl bg-{{ $tier['color'] }}-50 text-{{ $tier['color'] }}-600 flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                    </div>
                    @if($tier['status'] === 'current')
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest">Active</span>
                    @elseif($tier['status'] === 'completed')
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black uppercase tracking-widest text-center">Completed</span>
                    @endif
                </div>

                <h4 class="text-xl font-black text-gray-900 mb-1">{{ $tier['name'] }}</h4>
                <p class="text-xs text-gray-500 font-medium mb-6">Daily Limit: <span class="text-{{ $tier['color'] }}-600 font-bold">{{ $tier['limit'] }}</span></p>
                
                <div class="space-y-4 mb-8">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Requirements</p>
                        <ul class="space-y-2">
                            @foreach($tier['requirements'] as $req)
                                <li class="flex items-center gap-2 text-xs font-medium text-gray-700">
                                    <svg class="h-3.5 w-3.5 text-{{ $tier['color'] }}-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    {{ $req }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                @if($tier['status'] === 'available')
                    <a href="{{ route('compliance.kyc') }}" class="btn-primary w-full shadow-lg shadow-blue-100">Upgrade to {{ str_replace(' (Active)', '', $tier['name']) }}</a>
                @endif
            </div>
        @endforeach
    </div>
</div>
