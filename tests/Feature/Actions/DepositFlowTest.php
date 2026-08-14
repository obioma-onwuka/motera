<?php

use App\Actions\Deposits\ApproveDepositAction;
use App\Actions\Deposits\RejectDepositAction;
use App\Actions\Deposits\RequestDepositAction;
use App\Enums\RequestStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\BankAccount;
use App\Models\DepositRequest;
use App\Models\Transaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('requesting a deposit stores the proof on the local disk', function () {
    Storage::fake('local');

    $user = createCustomer();

    $proof = UploadedFile::fake()->image('proof.jpg');

    $deposit = app(RequestDepositAction::class)->execute($user, [
        'amount' => '500',
        'proof' => $proof,
    ]);

    expect($deposit->status)->toBe(RequestStatus::PENDING)
        ->and($deposit->reference)->toStartWith('MTR-DEP-')
        ->and($deposit->proof_path)->not->toBeNull();

    Storage::disk('local')->assertExists($deposit->proof_path);

    $this->assertModelExists($deposit);
});

test('approving a deposit credits the account once and balances the ledger', function () {
    $account = BankAccount::factory()->withBalance(1000)->create();

    $deposit = DepositRequest::factory()->create([
        'user_id' => $account->user_id,
        'bank_account_id' => $account->id,
        'amount' => '500',
        'status' => RequestStatus::PENDING,
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    app(ApproveDepositAction::class)->execute($deposit, 'Proof verified');

    $account->refresh();

    expect((float) $account->available_balance)->toBe(1500.0)
        ->and((float) $account->ledger_balance)->toBe(1500.0);

    expect($deposit->fresh()->status)->toBe(RequestStatus::APPROVED)
        ->and($deposit->fresh()->admin_note)->toBe('Proof verified');

    $transaction = Transaction::where('type', 'deposit')
        ->where('bank_account_id', $account->id)
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe(TransactionStatus::SUCCESSFUL)
        ->and($transaction->reference)->toStartWith('MTR-DEP-');

    $entries = $transaction->ledgerEntries;

    expect($entries)->toHaveCount(2);

    $credit = $entries->firstWhere('type', 'credit');
    $contra = $entries->firstWhere('type', 'debit');

    expect($credit)->not->toBeNull()
        ->and($credit->bank_account_id)->toBe($account->id)
        ->and((float) $credit->balance_after)->toBe(1500.0)
        ->and($contra)->not->toBeNull()
        ->and($contra->bank_account_id)->toBeNull();

    // The deposit transaction balances to zero: debits equal credits.
    expect((float) $entries->where('type', 'debit')->sum('amount'))
        ->toBe((float) $entries->where('type', 'credit')->sum('amount'));
});

test('a deposit cannot be approved twice', function () {
    $account = BankAccount::factory()->withBalance(1000)->create();

    $deposit = DepositRequest::factory()->create([
        'user_id' => $account->user_id,
        'bank_account_id' => $account->id,
        'amount' => '500',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    $action = app(ApproveDepositAction::class);
    $action->execute($deposit, 'First approval');

    expect(fn () => $action->execute($deposit, 'Second approval'))
        ->toThrow(RequestAlreadyProcessedException::class);

    // The account was credited only once.
    expect((float) $account->fresh()->available_balance)->toBe(1500.0);
});

test('rejecting a deposit without a note throws ValidationException', function () {
    $account = BankAccount::factory()->withBalance(1000)->create();

    $deposit = DepositRequest::factory()->create([
        'user_id' => $account->user_id,
        'bank_account_id' => $account->id,
        'amount' => '500',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    expect(fn () => app(RejectDepositAction::class)->execute($deposit, ''))
        ->toThrow(ValidationException::class);

    expect($deposit->fresh()->status)->toBe(RequestStatus::PENDING);
});

test('rejecting a deposit with a note marks it rejected without touching balances', function () {
    $account = BankAccount::factory()->withBalance(1000)->create();

    $deposit = DepositRequest::factory()->create([
        'user_id' => $account->user_id,
        'bank_account_id' => $account->id,
        'amount' => '500',
    ]);

    $admin = createAdmin('Operations Admin');
    $this->actingAs($admin);

    app(RejectDepositAction::class)->execute($deposit, 'Proof is illegible');

    expect($deposit->fresh()->status)->toBe(RequestStatus::REJECTED)
        ->and($deposit->fresh()->admin_note)->toBe('Proof is illegible');

    expect((float) $account->fresh()->available_balance)->toBe(1000.0)
        ->and((float) $account->fresh()->ledger_balance)->toBe(1000.0);
});

test('an admin without approve-deposits permission cannot approve or reject deposits', function () {
    $account = BankAccount::factory()->withBalance(1000)->create();

    $deposit = DepositRequest::factory()->create([
        'user_id' => $account->user_id,
        'bank_account_id' => $account->id,
        'amount' => '500',
    ]);

    $admin = createAdmin('Support Admin');
    $this->actingAs($admin);

    expect(fn () => app(ApproveDepositAction::class)->execute($deposit, 'Nope'))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(RejectDepositAction::class)->execute($deposit, 'Nope'))
        ->toThrow(AuthorizationException::class);

    expect($deposit->fresh()->status)->toBe(RequestStatus::PENDING);
});

test('deposit requests are rate limited to 5 per minute per user', function () {
    Storage::fake('local');

    $user = createCustomer();

    $action = app(RequestDepositAction::class);

    foreach (range(1, 5) as $attempt) {
        $action->execute($user, [
            'amount' => '100',
            'proof' => UploadedFile::fake()->image("proof-{$attempt}.jpg"),
        ]);
    }

    expect(fn () => $action->execute($user, [
        'amount' => '100',
        'proof' => UploadedFile::fake()->image('proof-6.jpg'),
    ]))->toThrow(ThrottleRequestsException::class);
});
