<?php

namespace App\Actions\Compliance;

use App\Actions\BaseAction;
use App\Models\KycSubmission;
use Illuminate\Support\Facades\DB;
use App\Notifications\Compliance\KycStatusUpdatedNotification;

class RejectKycAction extends BaseAction
{
    /**
     * Execute the KYC rejection action.
     */
    public function execute(mixed ...$args): KycSubmission
    {
        /** @var KycSubmission $submission */
        $submission = $args[0];
        $reason = $args[1];

        return DB::transaction(function () use ($submission, $reason) {
            $submission->update([
                'status' => 'rejected',
                'admin_note' => $reason,
                'processed_at' => now(),
            ]);

            // Notify User
            $submission->user->notify(new KycStatusUpdatedNotification($submission));

            return $submission;
        });
    }
}
