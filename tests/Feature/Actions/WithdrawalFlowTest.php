<?php

use App\Actions\Withdrawals\ApproveWithdrawalAction;
use App\Actions\Withdrawals\RejectWithdrawalAction;
use App\Actions\Withdrawals\RequestWithdrawalAction;
use App\Enums\RequestStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\Transaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('requesting a withdrawal holds available balance and leaves the ledger untouched', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $account = $user->primaryAccount;
    $account->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $withdrawal = app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]);

    $account->refresh();

    expect((float) $account->available_balance)->toBe(500.0)
        ->and((float) $account->ledger_balance)->toBe(1000.0);

    expect($withdrawal->status)->toBe(RequestStatus::PENDING)
        ->and($withdrawal->reference)->toStartWith('MTR-WTH-');

    $this->assertModelExists($withdrawal);
});

test('approving a withdrawal settles the ledger without double-debiting available balance', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $account = $user->primaryAccount;
    $account->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $withdrawal = app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    app(ApproveWithdrawalAction::class)->execute($withdrawal, 'Approved by operations');

    $account->refresh();

    expect((float) $account->available_balance)->toBe(500.0)
        ->and((float) $account->ledger_balance)->toBe(500.0);

    expect($withdrawal->fresh()->status)->toBe(RequestStatus::APPROVED)
        ->and($withdrawal->fresh()->admin_note)->toBe('Approved by operations');

    $transaction = Transaction::where('type', 'withdrawal')
        ->where('bank_account_id', $account->id)
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe(TransactionStatus::SUCCESSFUL)
        ->and($transaction->reference)->toStartWith('MTR-WTH-');

    $entries = $transaction->ledgerEntries;

    expect($entries)->toHaveCount(2);

    $debit = $entries->firstWhere('type', 'debit');
    $contra = $entries->firstWhere('type', 'credit');

    expect($debit)->not->toBeNull()
        ->and($debit->bank_account_id)->toBe($account->id)
        ->and((float) $debit->balance_after)->toBe(500.0)
        ->and($contra)->not->toBeNull()
        ->and($contra->bank_account_id)->toBeNull();

    // The settlement transaction balances to zero.
    expect((float) $entries->where('type', 'debit')->sum('amount'))
        ->toBe((float) $entries->where('type', 'credit')->sum('amount'));
});

test('rejecting a pending withdrawal releases the hold', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $account = $user->primaryAccount;
    $account->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $withdrawal = app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    app(RejectWithdrawalAction::class)->execute($withdrawal, 'Account details do not match');

    $account->refresh();

    expect((float) $account->available_balance)->toBe(1000.0)
        ->and((float) $account->ledger_balance)->toBe(1000.0);

    expect($withdrawal->fresh()->status)->toBe(RequestStatus::REJECTED)
        ->and($withdrawal->fresh()->admin_note)->toBe('Account details do not match');
});

test('a withdrawal request cannot be approved twice', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $account = $user->primaryAccount;
    $account->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $withdrawal = app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    $action = app(ApproveWithdrawalAction::class);
    $action->execute($withdrawal, 'First approval');

    expect(fn () => $action->execute($withdrawal, 'Second approval'))
        ->toThrow(RequestAlreadyProcessedException::class);

    expect((float) $account->fresh()->ledger_balance)->toBe(500.0);
});

test('an approved withdrawal cannot be rejected', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $user->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $withdrawal = app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    app(ApproveWithdrawalAction::class)->execute($withdrawal, 'Approved');

    expect(fn () => app(RejectWithdrawalAction::class)->execute($withdrawal, 'Too late'))
        ->toThrow(RequestAlreadyProcessedException::class);

    expect($withdrawal->fresh()->status)->toBe(RequestStatus::APPROVED);
});

test('an admin without approve-withdrawals permission cannot approve or reject withdrawals', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $user->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $withdrawal = app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]);

    $admin = createAdmin('Support Admin');
    $this->actingAs($admin);

    expect(fn () => app(ApproveWithdrawalAction::class)->execute($withdrawal, 'Nope'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(RejectWithdrawalAction::class)->execute($withdrawal, 'Nope'))
        ->toThrow(AuthorizationException::class);

    expect($withdrawal->fresh()->status)->toBe(RequestStatus::PENDING);
});

test('a withdrawal request beyond the available balance throws InsufficientFundsException', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $user->primaryAccount->update(['available_balance' => 100, 'ledger_balance' => 100]);

    expect(fn () => app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]))->toThrow(InsufficientFundsException::class);
});

test('a withdrawal request with the wrong pin throws InvalidPinException', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $user->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    expect(fn () => app(RequestWithdrawalAction::class)->execute($user, [
        'amount' => '500',
        'pin' => '0000',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ]))->toThrow(InvalidPinException::class);
});

test('withdrawal requests are rate limited to 3 per minute per user', function () {
    $user = createCustomer(['transaction_pin' => Hash::make('1234')]);
    $user->primaryAccount->update(['available_balance' => 1000, 'ledger_balance' => 1000]);

    $action = app(RequestWithdrawalAction::class);
    $data = fn () => [
        'amount' => '10',
        'pin' => '1234',
        'bank_name' => 'GTBank',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
    ];

    foreach (range(1, 3) as $attempt) {
        $action->execute($user, $data());
    }

    expect(fn () => $action->execute($user, $data()))
        ->toThrow(ThrottleRequestsException::class);
});
