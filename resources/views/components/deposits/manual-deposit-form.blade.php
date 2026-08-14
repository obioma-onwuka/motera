<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Actions\Deposits\RequestDepositAction;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithFileUploads;

    public $amount;
    public $proof;

    protected function rules()
    {
        return [
            'amount' => 'required|numeric|min:'.config('motera.limits.min_deposit'),
            'proof' => 'required|image|max:2048', // 2MB max
        ];
    }

    public function submit(RequestDepositAction $action)
    {
        $this->validate();

        try {
            $action->execute(Auth::user(), [
                'amount' => $this->amount,
                'proof' => $this->proof,
            ]);

            session()->flash('success', 'Deposit request submitted successfully! An admin will review it shortly.');

            return redirect()->route('dashboard');
        } catch (ThrottleRequestsException $e) {
            $this->addError('amount', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('amount', 'Something went wrong. Please try again.');
        }
    }
};
?>

<div class="space-y-6">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-brand-border">
        <form wire:submit="submit" class="space-y-6">
            <!-- Amount Input -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-brand-text-primary mb-2">Amount to Deposit ($)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-brand-text-secondary font-bold">$</div>
                    <input type="number" wire:model="amount" id="amount" class="block w-full pl-10 pr-4 py-4 rounded-2xl border-brand-border focus:ring-brand-primary focus:border-brand-primary placeholder-gray-400" placeholder="0.00">
                </div>
                @error('amount') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Proof Upload -->
            <div>
                <label for="file-upload" class="block text-sm font-semibold text-brand-text-primary mb-2">Upload Proof of Payment</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-brand-border border-dashed rounded-2xl bg-slate-50 relative">
                    <div class="space-y-1 text-center">
                        @if ($proof)
                            <div class="mb-4">
                                <img src="{{ $proof->temporaryUrl() }}" class="mx-auto h-32 w-auto rounded-lg shadow-sm">
                            </div>
                        @else
                            <svg class="mx-auto h-12 w-12 text-brand-text-secondary" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.171-9.171a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @endif
                        <div class="flex text-sm text-brand-text-secondary">
                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-brand-primary hover:text-blue-500 focus-within:outline-none">
                                <span>Upload a file</span>
                                <input id="file-upload" wire:model="proof" type="file" class="sr-only">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-brand-text-secondary">PNG, JPG, up to 2MB</p>
                    </div>
                    <div wire:loading wire:target="proof" class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center rounded-2xl">
                        <span class="text-sm font-semibold text-brand-primary italic">Uploading...</span>
                    </div>
                </div>
                @error('proof') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Notice Section -->
            <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-brand-primary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-xs text-blue-800 leading-relaxed">
                        Please ensure the proof of payment clearly shows the reference, amount, and date. Your balance will be updated after admin verification.
                    </p>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" wire:loading.attr="disabled" class="btn-primary w-full shadow-lg shadow-blue-100">
                <span wire:loading.remove wire:target="submit">Submit Deposit Request</span>
                <span wire:loading wire:target="submit">Processing...</span>
            </button>
        </form>
    </div>
</div>