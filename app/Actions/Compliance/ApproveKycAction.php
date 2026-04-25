<?php

namespace App\Actions\Compliance;

use App\Actions\BaseAction;
use App\Models\KycSubmission;
use Illuminate\Support\Facades\DB;
use App\Notifications\Compliance\KycStatusUpdatedNotification;

class ApproveKycAction extends BaseAction
{
    /**
     * Execute the KYC approval action.
     */
    public function execute(mixed ...$args): KycSubmission
    {
        /** @var KycSubmission $submission */
        $submission = $args[0];
        $adminNote = $args[1] ?? 'Approved by administrative staff.';

        return DB::transaction(function () use ($submission, $adminNote) {
            $submission->update([
                'status' => \App\Enums\KycStatus::APPROVED,
                'admin_note' => $adminNote,
                'processed_at' => now(),
            ]);

            // Upgrade primary bank account to Tier 2
            $submission->user->primaryAccount?->update([
                'tier' => \App\Enums\KycTier::TIER_2
            ]);

            // Notify User
            $submission->user->notify(new KycStatusUpdatedNotification($submission));

            return $submission;
        });
    }
}
