<?php

use Livewire\Volt\Component;
use App\Actions\Cards\RequestCardAction;
use Illuminate\Support\Str;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use App\Exceptions\AccountRestrictedException;
use App\Exceptions\IdempotencyViolationException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\TooManyPinAttemptsException;
use App\Traits\InteractsWithIdempotency;

new class extends Component {
    use InteractsWithIdempotency;

    public $type = 'virtual';
    public $cardName;
    public $deliveryAddress;
    public $pin;
    public $showRequestForm = false;
    public $idempotencyKey;

    public function mount()
    {
        $this->cardName = auth()->user()->name;
        $this->idempotencyKey = Str::uuid()->toString();
    }

    public function submitRequest(RequestCardAction $action)
    {
        $this->validate([
            'type' => 'required|in:virtual,physical',
            'cardName' => 'required|string|max:26',
            'deliveryAddress' => 'required_if:type,physical|nullable|string',
            'pin' => $this->type === 'physical' ? 'required|digits:4' : 'nullable',
        ]);

        try {
            $this->idempotent($this->idempotencyKey, function () use ($action) {
                $user = auth()->user();

                $action->execute($user, [
                    'type' => $this->type,
                    'card_name' => $this->cardName,
                    'delivery_address' => $this->deliveryAddress,
                    'pin' => $this->pin,
                ]);

                session()->flash('success', 'Card request submitted successfully!');
                $this->showRequestForm = false;
                $this->reset(['type', 'deliveryAddress', 'pin']);
                $this->idempotencyKey = Str::uuid()->toString();
            });
        } catch (InvalidPinException | TooManyPinAttemptsException $e) {
            $this->addError('pin', $e->getMessage());
        } catch (InsufficientFundsException $e) {
            $this->addError('amount', $e->getMessage());
        } catch (AccountRestrictedException $e) {
            $this->addError('amount', $e->getMessage());
        } catch (IdempotencyViolationException $e) {
            session()->flash('info', 'This request was already submitted.');
        } catch (ThrottleRequestsException $e) {
            $this->addError('amount', $e->getMessage());
        }
    }

    public function with()
    {
        return [
            'requests' => auth()->user()->cardRequests()->latest()->paginate(10),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold text-brand-text-primary">Your Cards</h3>
        <button wire:click="$toggle('showRequestForm')" class="btn-primary text-sm shadow-sm py-2 px-4 min-h-0">
            Request New Card
        </button>
    </div>

    @if ($showRequestForm)
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-brand-border">
            <form wire:submit="submitRequest" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" wire:click="$set('type', 'virtual')" 
                            class="p-4 border-2 @if($type === 'virtual') border-brand-primary bg-blue-50 @else border-brand-border bg-white @endif rounded-2xl text-center transition-all cursor-pointer">
                        <span class="block font-bold text-brand-text-primary">Virtual Card</span>
                        <span class="text-xs text-brand-text-secondary">Free & Instant</span>
                    </button>
                    <button type="button" wire:click="$set('type', 'physical')"
                            class="p-4 border-2 @if($type === 'physical') border-brand-primary bg-blue-50 @else border-brand-border bg-white @endif rounded-2xl text-center transition-all cursor-pointer">
                        <span class="block font-bold text-brand-text-primary">Physical Card</span>
                        <span class="text-xs text-brand-text-secondary">${{ number_format(config('motera.limits.card_physical_fee'), 2) }} Fee</span>
                    </button>
                </div>

                <div>
                    <label for="cardName" class="block text-sm font-semibold text-brand-text-primary mb-2">Name on Card</label>
                    <input type="text" id="cardName" wire:model.blur="cardName" class="block w-full px-4 py-3 rounded-xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all shadow-sm uppercase tracking-wider">
                    @error('cardName') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                </div>

                @if ($type === 'physical')
                    <div>
                        <label for="deliveryAddress" class="block text-sm font-semibold text-brand-text-primary mb-2">Delivery Address</label>
                        <textarea id="deliveryAddress" wire:model.blur="deliveryAddress" rows="3" class="block w-full px-4 py-3 rounded-xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-sm transition-all shadow-sm"></textarea>
                        @error('deliveryAddress') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="pin" class="block text-sm font-semibold text-brand-text-primary mb-2">Transaction PIN</label>
                        <input type="password" id="pin" wire:model="pin" maxlength="4" class="block w-full px-4 py-3 rounded-xl border-brand-border bg-white focus:ring-brand-primary focus:border-brand-primary text-center tracking-[1em] text-lg transition-all shadow-sm" placeholder="••••">
                        @error('pin') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif

                @error('amount') <span class="text-xs text-brand-danger mt-1">{{ $message }}</span> @enderror

                <div class="flex justify-end gap-3 pt-4 border-t border-brand-border">
                    <button type="button" wire:click="$set('showRequestForm', false)" class="btn-outline text-sm py-2 px-4 min-h-0">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary text-sm py-2 px-6 min-h-0 shadow-lg shadow-blue-100">
                        <span wire:loading.remove wire:target="submitRequest">Submit Request</span>
                        <span wire:loading wire:target="submitRequest">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse ($requests as $request)
            <div wire:key="card-request-{{ $request->id }}" class="bg-gradient-to-br from-gray-900 to-gray-800 p-6 rounded-2xl shadow-xl border border-gray-700 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4">
                    <span class="px-2 py-1 text-[10px] uppercase font-bold rounded {{ $request->status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white' }}">
                        {{ $request->status }}
                    </span>
                </div>
                
                <div class="mb-8">
                    <span class="text-blue-400 font-bold tracking-widest text-lg">MOTERA</span>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="h-8 w-12 bg-yellow-400 rounded opacity-50"></div>
                        <div class="text-white font-mono tracking-[0.2em] text-lg">•••• •••• •••• ••••</div>
                    </div>
                    
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase">Card Holder</p>
                            <p class="text-white font-medium uppercase tracking-wider">{{ $request->card_name }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase">Type</p>
                            <p class="text-white text-xs uppercase">{{ $request->type }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 py-12 text-center bg-gray-50 dark:bg-gray-900/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                <p class="text-gray-500">You don't have any cards yet.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $requests->links() }}
    </div>
</div>