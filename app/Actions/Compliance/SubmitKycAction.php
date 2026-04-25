<?php

namespace App\Actions\Compliance;

use App\Actions\BaseAction;
use App\Models\KycSubmission;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubmitKycAction extends BaseAction
{
    /**
     * Execute the KYC submission action.
     */
    public function execute(mixed ...$args): KycSubmission
    {
        /** @var User $user */
        $user = $args[0];
        /** @var array $data */
        $data = $args[1];

        return DB::transaction(function () use ($user, $data) {
            // 1. Create Submission
            $submission = KycSubmission::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'address' => $data['address'],
                'id_type' => $data['id_type'],
                'id_number' => $data['id_number'],
                'status' => 'pending',
            ]);

            // 2. Handle Documents
            foreach ($data['documents'] as $type => $file) {
                if ($file) {
                    $path = $file->store("kyc/{$user->id}", 'public');
                    
                    KycDocument::create([
                        'kyc_submission_id' => $submission->id,
                        'document_type' => $type,
                        'file_path' => $path,
                    ]);
                }
            }

            return $submission;
        });
    }
}
