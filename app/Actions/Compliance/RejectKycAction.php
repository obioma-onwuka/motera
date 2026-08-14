<?php

namespace App\Actions\Compliance;

use App\Actions\BaseAction;
use App\Enums\KycStatus;
use App\Exceptions\RequestAlreadyProcessedException;
use App\Models\KycSubmission;
use App\Notifications\Compliance\KycStatusUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RejectKycAction extends BaseAction
{
    /**
     * Execute the KYC rejection action.
     */
    public function execute(mixed ...$args): KycSubmission
    {
        Gate::authorize('review-kyc');

        /** @var KycSubmission $submission */
        $submission = $args[0];
        $reason = $args[1];

        $submission = DB::transaction(function () use ($submission, $reason) {
            $submission = KycSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if ($submission->status !== KycStatus::PENDING) {
                throw new RequestAlreadyProcessedException('This KYC submission has already been processed.');
            }

            $submission->update([
                'status' => KycStatus::REJECTED,
                'admin_note' => $reason,
                'processed_at' => now(),
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($submission)
                ->log('kyc.rejected');

            return $submission;
        });

        DB::afterCommit(fn () => $submission->user->notify(new KycStatusUpdatedNotification($submission)));

        return $submission;
    }
}
