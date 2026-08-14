<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Compliance\SubmitKycAction;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithFileUploads;

    public $step = 1;

    // Step 1: Personal Details
    public $first_name;
    public $last_name;
    public $date_of_birth;
    public $address;

    // Step 2: ID info
    public $id_type = '';
    public $id_number;

    // Step 3: Documents
    public $doc_id_card;
    public $doc_utility_bill;
    public $doc_selfie;

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'first_name' => 'required|string|min:2',
                'last_name' => 'required|string|min:2',
                'date_of_birth' => 'required|date|before:-18 years',
                'address' => 'required|string|min:10',
            ]);
        } elseif ($this->step === 2) {
            $this->validate([
                'id_type' => 'required|string',
                'id_number' => 'required|string|min:5',
            ]);
        }

        $this->step++;
    }

    public function previousStep()
    {
        $this->step--;
    }

    public function submit(SubmitKycAction $action)
    {
        $this->validate([
            'doc_id_card' => 'required|image|max:2048',
            'doc_utility_bill' => 'required|image|max:2048',
            'doc_selfie' => 'required|image|max:2048',
        ]);

        $action->execute(Auth::user(), [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'address' => $this->address,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'documents' => [
                'id_card' => $this->doc_id_card,
                'utility_bill' => $this->doc_utility_bill,
                'selfie' => $this->doc_selfie,
            ],
        ]);

        session()->flash('success', 'Your KYC documents have been submitted for review!');
        return redirect()->route('dashboard');
    }
};
?>

<div class="space-y-8">
    <!-- Progress Stepper -->
    <div class="flex items-center justify-between relative px-2">
        <div class="absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 -z-10 -translate-y-1/2"></div>
        <div class="absolute top-1/2 left-0 h-0.5 bg-brand-primary -z-10 -translate-y-1/2 transition-all duration-300" style="width: {{ ($step - 1) * 50 }}%"></div>
        
        <div class="flex flex-col items-center">
            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $step >= 1 ? 'bg-brand-primary text-white ring-4 ring-blue-100' : 'bg-white text-slate-400 border-2 border-slate-200' }}">1</div>
            <span class="text-[10px] mt-2 font-bold {{ $step >= 1 ? 'text-brand-primary' : 'text-slate-400' }}">Personal</span>
        </div>
        <div class="flex flex-col items-center">
            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $step >= 2 ? 'bg-brand-primary text-white ring-4 ring-blue-100' : 'bg-white text-slate-400 border-2 border-slate-200' }}">2</div>
            <span class="text-[10px] mt-2 font-bold {{ $step >= 2 ? 'text-brand-primary' : 'text-slate-400' }}">Identity</span>
        </div>
        <div class="flex flex-col items-center">
            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold transition-all {{ $step >= 3 ? 'bg-brand-primary text-white ring-4 ring-blue-100' : 'bg-white text-slate-400 border-2 border-slate-200' }}">3</div>
            <span class="text-[10px] mt-2 font-bold {{ $step >= 3 ? 'text-brand-primary' : 'text-slate-400' }}">Documents</span>
        </div>
    </div>

    <div class="bg-white rounded-3xl p-8 shadow-sm border border-brand-border">
        @if($step === 1)
            <!-- Step 1: Personal Details -->
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-brand-text-primary mb-2">First Name</label>
                        <input type="text" wire:model="first_name" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="Jane">
                        @error('first_name') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-brand-text-primary mb-2">Last Name</label>
                        <input type="text" wire:model="last_name" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="Doe">
                        @error('last_name') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-brand-text-primary mb-2">Date of Birth</label>
                    <input type="date" wire:model="date_of_birth" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary">
                    @error('date_of_birth') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-brand-text-primary mb-2">Residential Address</label>
                    <textarea wire:model="address" rows="3" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="Full residential address"></textarea>
                    @error('address') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                </div>
                <button type="button" wire:click="nextStep" class="btn-primary w-full">Continue to Next Step</button>
            </div>
        @elseif($step === 2)
            <!-- Step 2: ID Information -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-brand-text-primary mb-2">Identification Type</label>
                    <select wire:model="id_type" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary bg-white">
                        <option value="">Select ID Type</option>
                        <option value="National ID">National Identity Card</option>
                        <option value="International Passport">International Passport</option>
                        <option value="Drivers License">Driver's License</option>
                        <option value="Voters Card">Voter's Card</option>
                    </select>
                    @error('id_type') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-brand-text-primary mb-2">ID Number</label>
                    <input type="text" wire:model="id_number" class="block w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary" placeholder="Enter ID number">
                    @error('id_number') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" wire:click="previousStep" class="btn-outline flex-1">Back</button>
                    <button type="button" wire:click="nextStep" class="btn-primary flex-1">Continue</button>
                </div>
            </div>
        @elseif($step === 3)
            <!-- Step 3: Documents -->
            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Photo of ID -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Photo of ID Card</label>
                        <div class="relative h-40 border-2 border-brand-border border-dashed rounded-2xl flex items-center justify-center bg-slate-50 overflow-hidden cursor-pointer hover:bg-slate-100 transition-colors">
                            @if($doc_id_card)
                                <img src="{{ $doc_id_card->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            @endif
                            <input type="file" wire:model="doc_id_card" class="absolute inset-0 opacity-0 cursor-pointer">
                        </div>
                        @error('doc_id_card') <span class="text-[10px] text-brand-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Proof of Address -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Utility Bill / Proof of Address</label>
                        <div class="relative h-40 border-2 border-brand-border border-dashed rounded-2xl flex items-center justify-center bg-slate-50 overflow-hidden cursor-pointer hover:bg-slate-100 transition-colors">
                            @if($doc_utility_bill)
                                <img src="{{ $doc_utility_bill->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            @endif
                            <input type="file" wire:model="doc_utility_bill" class="absolute inset-0 opacity-0 cursor-pointer">
                        </div>
                        @error('doc_utility_bill') <span class="text-[10px] text-brand-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Selfie -->
                    <div class="space-y-2 sm:col-span-2">
                        <label class="text-xs font-bold text-slate-500 uppercase">Selfie with ID</label>
                        <div class="relative h-40 border-2 border-brand-border border-dashed rounded-2xl flex items-center justify-center bg-slate-50 overflow-hidden cursor-pointer hover:bg-slate-100 transition-colors">
                            @if($doc_selfie)
                                <img src="{{ $doc_selfie->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            @endif
                            <input type="file" wire:model="doc_selfie" class="absolute inset-0 opacity-0 cursor-pointer">
                        </div>
                        @error('doc_selfie') <span class="text-[10px] text-brand-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" wire:click="previousStep" class="btn-outline flex-1">Back</button>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled" class="btn-primary flex-1 shadow-lg shadow-blue-100">
                        <span wire:loading.remove>Submit Documents</span>
                        <span wire:loading italic>Uploading...</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>