<?php

use App\Actions\Bills\PayBillAction;
use App\Actions\Cards\RequestCardAction;
use App\Enums\TransactionStatus;
use App\Exceptions\AccountRestrictedException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidPinException;
use App\Models\Biller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Cache::flush();
    seedRolesAndPermissions();
});

/**
 * Create a customer with an account funded to the given amount, optionally
 * protected by a transaction PIN (default 1234).
 */
function customerWithBalance(float $amount, ?string $pin = '1234'): User
{
    $user = createCustomer($pin ? ['transaction_pin' => Hash::make($pin)] : []);

    $user->primaryAccount()->first()->update([
        'ledger_balance' => $amount,
        'available_balance' => $amount,
    ]);

    return $user;
}

it('pays a bill, debits the account and records balancing ledger entries', function () {
    $user = customerWithBalance(1000);
    $biller = Biller::factory()->create();

    $payment = app(PayBillAction::class)->execute($user, [
        'biller_id' => $biller->id,
        'amount' => '100',
        'customer_identifier' => 'CUST-001',
        'pin' => '1234',
    ]);

    $account = $user->primaryAccount()->first();

    expect((float) $account->available_balance)->toBe(900.0)
        ->and((float) $account->ledger_balance)->toBe(900.0);

    $transaction = Transaction::where('reference', $payment->reference)->firstOrFail();

    expect($transaction->type)->toBe('bill_payment')
        ->and($transaction->status)->toBe(TransactionStatus::SUCCESSFUL)
        ->and($transaction->reference)->toStartWith('MTR-BILL-')
        ->and($payment->status)->toBe('successful');

    $entries = $transaction->ledgerEntries;
    $debit = $entries->firstWhere('type', 'debit');
    $credit = $entries->firstWhere('type', 'credit');

    expect($entries)->toHaveCount(2)
        ->and((float) $debit->amount)->toBe(100.0)
        ->and((float) $credit->amount)->toBe(100.0)
        ->and($debit->bank_account_id)->toBe($account->id)
        ->and($credit->bank_account_id)->toBeNull();
});

it('rejects payment to an inactive biller', function () {
    $user = customerWithBalance(1000);
    $biller = Biller::factory()->create(['is_active' => false]);

    expect(fn () => app(PayBillAction::class)->execute($user, [
        'biller_id' => $biller->id,
        'amount' => '100',
        'customer_identifier' => 'CUST-001',
        'pin' => '1234',
    ]))->toThrow(ValidationException::class);
});

it('rejects a bill payment when the balance is insufficient', function () {
    $user = customerWithBalance(50);
    $biller = Biller::factory()->create();

    expect(fn () => app(PayBillAction::class)->execute($user, [
        'biller_id' => $biller->id,
        'amount' => '100',
        'customer_identifier' => 'CUST-001',
        'pin' => '1234',
    ]))->toThrow(InsufficientFundsException::class);
});

it('rejects a bill payment with an incorrect pin', function () {
    $user = customerWithBalance(1000);
    $biller = Biller::factory()->create();

    expect(fn () => app(PayBillAction::class)->execute($user, [
        'biller_id' => $biller->id,
        'amount' => '100',
        'customer_identifier' => 'CUST-001',
        'pin' => '9999',
    ]))->toThrow(InvalidPinException::class);
});

it('rejects a bill payment from a restricted account', function () {
    $user = customerWithBalance(1000);

    $user->primaryAccount()->first()->update([
        'is_restricted' => true,
        'restriction_reason' => 'Compliance hold',
    ]);

    $biller = Biller::factory()->create();

    expect(fn () => app(PayBillAction::class)->execute($user, [
        'biller_id' => $biller->id,
        'amount' => '100',
        'customer_identifier' => 'CUST-001',
        'pin' => '1234',
    ]))->toThrow(AccountRestrictedException::class);
});

it('charges the card fee for a physical card request', function () {
    $user = customerWithBalance(1000);

    $cardRequest = app(RequestCardAction::class)->execute($user, [
        'type' => 'physical',
        'card_name' => 'Motera Platinum',
        'delivery_address' => '10 Admiralty Way',
        'pin' => '1234',
    ]);

    $account = $user->primaryAccount()->first();

    expect((float) $account->available_balance)->toBe(0.0)
        ->and((float) $account->ledger_balance)->toBe(0.0);

    $transaction = Transaction::where('user_id', $user->id)->firstOrFail();

    expect($transaction->type)->toBe('card_fee')
        ->and((float) $transaction->amount)->toBe(1000.0)
        ->and($transaction->status)->toBe(TransactionStatus::SUCCESSFUL)
        ->and((float) $cardRequest->fee)->toBe(1000.0)
        ->and($cardRequest->status)->toBe('pending')
        ->and($cardRequest->metadata['transaction_reference'])->toBe($transaction->reference);

    $entries = $transaction->ledgerEntries;

    expect($entries)->toHaveCount(2)
        ->and((float) $entries->where('type', 'debit')->sum('amount'))->toBe(1000.0)
        ->and((float) $entries->where('type', 'credit')->sum('amount'))->toBe(1000.0)
        ->and($entries->firstWhere('type', 'credit')->bank_account_id)->toBeNull();
});

it('creates a free virtual card request without a pin or transaction', function () {
    $user = createCustomer(); // no transaction pin set

    $cardRequest = app(RequestCardAction::class)->execute($user, [
        'type' => 'virtual',
        'card_name' => 'Motera Virtual',
    ]);

    expect((float) $cardRequest->fee)->toBe(0.0)
        ->and($cardRequest->status)->toBe('pending')
        ->and(Transaction::where('user_id', $user->id)->exists())->toBeFalse();
});

it('throws InvalidPinException for a physical card request without a pin field', function () {
    $user = createCustomer(); // no transaction pin set

    expect(fn () => app(RequestCardAction::class)->execute($user, [
        'type' => 'physical',
        'card_name' => 'Motera Gold',
        'delivery_address' => '5 Freedom Way',
    ]))->toThrow(InvalidPinException::class);
});

it('rejects a physical card request when the balance cannot cover the fee', function () {
    $user = customerWithBalance(500);

    expect(fn () => app(RequestCardAction::class)->execute($user, [
        'type' => 'physical',
        'card_name' => 'Motera Gold',
        'delivery_address' => '5 Freedom Way',
        'pin' => '1234',
    ]))->toThrow(InsufficientFundsException::class);
});
