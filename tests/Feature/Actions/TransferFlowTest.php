<?php

use App\Actions\Transfers\InitiateInternalTransferAction;
use App\Enums\TransactionStatus;
use App\Exceptions\AccountRestrictedException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidPinException;
use App\Notifications\TransferReceivedNotification;
use App\Notifications\TransferSentNotification;
use App\Services\NameMasker;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('an internal transfer moves balances and records balanced ledger entries', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $recipient = createCustomer(['transaction_pin' => Hash::make('1234')]);

    Notification::fake();

    $transaction = app(InitiateInternalTransferAction::class)->execute($sender, [
        'recipient_account_number' => $recipient->primaryAccount->account_number,
        'amount' => '500',
        'description' => 'Rent payment',
        'pin' => '1234',
    ]);

    $senderAccount = $sender->primaryAccount->fresh();
    $recipientAccount = $recipient->primaryAccount->fresh();

    expect((float) $senderAccount->available_balance)->toBe(500.0)
        ->and((float) $senderAccount->ledger_balance)->toBe(500.0)
        ->and((float) $recipientAccount->available_balance)->toBe(500.0)
        ->and((float) $recipientAccount->ledger_balance)->toBe(500.0);

    expect($transaction->status)->toBe(TransactionStatus::SUCCESSFUL)
        ->and($transaction->reference)->toStartWith('MTR-TRF-');

    $entries = $transaction->ledgerEntries;

    expect($entries)->toHaveCount(2);

    $debit = $entries->firstWhere('type', 'debit');
    $credit = $entries->firstWhere('type', 'credit');

    expect($debit)->not->toBeNull()
        ->and($debit->bank_account_id)->toBe($senderAccount->id)
        ->and((float) $debit->balance_after)->toBe(500.0)
        ->and($credit)->not->toBeNull()
        ->and($credit->bank_account_id)->toBe($recipientAccount->id)
        ->and((float) $credit->balance_after)->toBe(500.0);

    // The transaction balances to zero: debits equal credits.
    expect((float) $entries->where('type', 'debit')->sum('amount'))
        ->toBe((float) $entries->where('type', 'credit')->sum('amount'));

    // The recipient name is masked in the transaction metadata.
    expect($transaction->metadata['recipient_name'])->toBe(NameMasker::mask($recipient->name))
        ->and($transaction->metadata['recipient_name'])->not->toBe($recipient->name);

    Notification::assertSentTo($sender, TransferSentNotification::class);
    Notification::assertSentTo($recipient, TransferReceivedNotification::class);
});

test('a transfer beyond the available balance throws InsufficientFundsException', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update(['available_balance' => 100, 'ledger_balance' => 100]);

    $recipient = createCustomer(['transaction_pin' => Hash::make('1234')]);

    expect(fn () => app(InitiateInternalTransferAction::class)->execute($sender, [
        'recipient_account_number' => $recipient->primaryAccount->account_number,
        'amount' => '500',
        'pin' => '1234',
    ]))->toThrow(InsufficientFundsException::class);

    expect((float) $sender->primaryAccount->fresh()->available_balance)->toBe(100.0);
});

test('a user cannot transfer to their own account', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    expect(fn () => app(InitiateInternalTransferAction::class)->execute($sender, [
        'recipient_account_number' => $sender->primaryAccount->account_number,
        'amount' => '500',
        'pin' => '1234',
    ]))->toThrow(ValidationException::class);
});

test('transferring to a nonexistent account throws ValidationException', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    expect(fn () => app(InitiateInternalTransferAction::class)->execute($sender, [
        'recipient_account_number' => '9999999999',
        'amount' => '500',
        'pin' => '1234',
    ]))->toThrow(ValidationException::class);
});

test('a restricted sender cannot initiate a transfer', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update([
        'available_balance' => 1000,
        'ledger_balance' => 1000,
        'is_restricted' => true,
        'restriction_reason' => 'Compliance review',
    ]);

    $recipient = createCustomer(['transaction_pin' => Hash::make('1234')]);

    expect(fn () => app(InitiateInternalTransferAction::class)->execute($sender, [
        'recipient_account_number' => $recipient->primaryAccount->account_number,
        'amount' => '500',
        'pin' => '1234',
    ]))->toThrow(AccountRestrictedException::class);
});

test('a transfer with the wrong pin throws InvalidPinException', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $recipient = createCustomer(['transaction_pin' => Hash::make('1234')]);

    expect(fn () => app(InitiateInternalTransferAction::class)->execute($sender, [
        'recipient_account_number' => $recipient->primaryAccount->account_number,
        'amount' => '100',
        'pin' => '0000',
    ]))->toThrow(InvalidPinException::class);

    expect((float) $sender->primaryAccount->fresh()->available_balance)->toBe(1000.0);
});

test('transfers are rate limited to 5 per minute per user', function () {
    $sender = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $sender->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $recipient = createCustomer(['transaction_pin' => Hash::make('1234')]);

    Notification::fake();

    $action = app(InitiateInternalTransferAction::class);
    $data = fn () => [
        'recipient_account_number' => $recipient->primaryAccount->account_number,
        'amount' => '10',
        'pin' => '1234',
    ];

    foreach (range(1, 5) as $attempt) {
        $action->execute($sender, $data());
    }

    expect(fn () => $action->execute($sender, $data()))
        ->toThrow(ThrottleRequestsException::class);
});
