<?php

use App\Actions\Compliance\ApproveKycAction;
use App\Actions\Compliance\RejectKycAction;
use App\Actions\Compliance\SubmitKycAction;
use App\Enums\KycStatus;
use App\Enums\KycTier;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\KycSubmission;
use App\Models\User;
use App\Notifications\Compliance\KycStatusUpdatedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    seedRolesAndPermissions();
});

function validKycData(): array
{
    return [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'date_of_birth' => '1990-01-01',
        'address' => '123 Main Street',
        'id_type' => 'passport',
        'id_number' => 'P123456789',
        'documents' => [
            'identity' => UploadedFile::fake()->create('identity.jpg', 100),
            'selfie' => UploadedFile::fake()->create('selfie.jpg', 100),
        ],
    ];
}

it('stores documents on the local disk and creates a pending submission', function () {
    Storage::fake('local');

    $user = createCustomer();

    $submission = app(SubmitKycAction::class)->execute($user, validKycData());

    expect($submission->status)->toBe(KycStatus::PENDING)
        ->and($submission->user_id)->toBe($user->id)
        ->and($submission->documents()->count())->toBe(2);

    foreach ($submission->documents as $document) {
        expect($document->file_path)->toStartWith("kyc/{$user->id}/");

        Storage::disk('local')->assertExists($document->file_path);
    }

    $this->assertDatabaseCount('kyc_submissions', 1);
});

it('prevents a second submission while one is pending', function () {
    Storage::fake('local');

    $user = createCustomer();

    KycSubmission::factory()->for($user)->create(['status' => KycStatus::PENDING]);

    expect(fn () => app(SubmitKycAction::class)->execute($user, validKycData()))
        ->toThrow(ValidationException::class);
});

it('approves a pending submission, upgrades the account tier and notifies the user', function () {
    Notification::fake();

    $user = createCustomer();
    $submission = KycSubmission::factory()->for($user)->create(['status' => KycStatus::PENDING]);
    $admin = createAdmin('Compliance Admin');

    $this->actingAs($admin);

    app(ApproveKycAction::class)->execute($submission, 'Documents verified.');

    $submission->refresh();

    expect($submission->status)->toBe(KycStatus::APPROVED)
        ->and($submission->processed_at)->not->toBeNull();

    expect($user->primaryAccount()->first()->tier)->toBe(KycTier::TIER_2);

    Notification::assertSentTo($user, KycStatusUpdatedNotification::class);
});

it('throws RequestAlreadyProcessedException when approving twice', function () {
    Notification::fake();

    $user = createCustomer();
    $submission = KycSubmission::factory()->for($user)->create(['status' => KycStatus::PENDING]);
    $admin = createAdmin('Compliance Admin');

    $this->actingAs($admin);

    app(ApproveKycAction::class)->execute($submission, 'Approved once.');

    expect(fn () => app(ApproveKycAction::class)->execute($submission, 'Approved again.'))
        ->toThrow(RequestAlreadyProcessedException::class);
});

it('rejects a pending submission and records the reason', function () {
    Notification::fake();

    $user = createCustomer();
    $submission = KycSubmission::factory()->for($user)->create(['status' => KycStatus::PENDING]);
    $admin = createAdmin('Compliance Admin');

    $this->actingAs($admin);

    app(RejectKycAction::class)->execute($submission, 'Document unreadable.');

    $submission->refresh();

    expect($submission->status)->toBe(KycStatus::REJECTED)
        ->and($submission->admin_note)->toBe('Document unreadable.')
        ->and($submission->processed_at)->not->toBeNull();
});

it('fails approval when the user has no bank account', function () {
    $user = User::factory()->create();
    $submission = KycSubmission::factory()->for($user)->create(['status' => KycStatus::PENDING]);
    $admin = createAdmin('Compliance Admin');

    $this->actingAs($admin);

    expect(fn () => app(ApproveKycAction::class)->execute($submission, 'Approved.'))
        ->toThrow(RuntimeException::class, 'User has no bank account to upgrade.');
});

it('forbids admins without the review-kyc permission', function () {
    $user = createCustomer();
    $submission = KycSubmission::factory()->for($user)->create(['status' => KycStatus::PENDING]);
    $admin = createAdmin('Support Admin');

    $this->actingAs($admin);

    expect(fn () => app(ApproveKycAction::class)->execute($submission, 'Nope.'))
        ->toThrow(AuthorizationException::class);
});
