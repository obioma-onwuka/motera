<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kyc_submission_id',
        'document_type',
        'file_path',
    ];

    public function submission()
    {
        return $this->belongsTo(KycSubmission::class, 'kyc_submission_id');
    }
}
