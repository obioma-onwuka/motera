<?php

namespace App\Actions\Compliance;

use App\Actions\BaseAction;
use App\Enums\KycStatus;
use App\Enums\KycTier;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\KycSubmission;
use App\Notifications\Compliance\KycStatusUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveKycAction extends BaseAction
{
    /**
     * Execute the KYC approval action.
     */
    public function execute(mixed ...$args): KycSubmission
    {
        Gate::authorize('review-kyc');

        /** @var KycSubmission $submission */
        $submission = $args[0];
        $adminNote = $args[1] ?? 'Approved by administrative staff.';

        $submission = DB::transaction(function () use ($submission, $adminNote) {
            $submission = KycSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if ($submission->status !== KycStatus::PENDING) {
                throw new RequestAlreadyProcessedException('This KYC submission has already been processed.');
            }

            $account = $submission->user->primaryAccount;

            if (! $account) {
                throw new \RuntimeException('User has no bank account to upgrade.');
            }

            $account->update(['tier' => KycTier::TIER_2]);

            $submission->update([
                'status' => KycStatus::APPROVED,
                'admin_note' => $adminNote,
                'processed_at' => now(),
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($submission)
                ->withProperties(['tier' => 'tier_2'])
                ->log('kyc.approved');

            return $submission;
        });

        DB::afterCommit(fn () => $submission->user->notify(new KycStatusUpdatedNotification($submission)));

        return $submission;
    }
}
