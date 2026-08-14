<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component {
    public $name;
    public $email;
    public $phone;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? ''; 
    }

    public function updateProfile()
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        session()->flash('success_profile', 'Profile updated successfully.');
    }
}; ?>

<div class="bg-white p-6 rounded-3xl border border-brand-border shadow-sm mb-6">
    <h3 class="text-lg font-bold mb-2">Personal Information</h3>
    <p class="text-sm text-brand-text-secondary mb-6">Update your personal details and contact info.</p>
    
    <form wire:submit="updateProfile" class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
            <input type="text" wire:model="name" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
            @error('name') <span class="text-brand-danger text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label>
                <input type="email" wire:model="email" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
                @error('email') <span class="text-brand-danger text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone Number (Optional)</label>
                <input type="text" wire:model="phone" class="w-full rounded-xl border-brand-border focus:border-brand-primary focus:ring-brand-primary transition-all">
                @error('phone') <span class="text-brand-danger text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        @if (session('success_profile'))
            <div class="p-3 bg-green-50 text-brand-success rounded-xl text-sm font-medium border border-green-200">
                {{ session('success_profile') }}
            </div>
        @endif

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full md:w-auto px-8">Save Changes</button>
        </div>
    </form>
</div>
