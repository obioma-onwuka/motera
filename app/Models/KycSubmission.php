<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'address',
        'id_type',
        'id_number',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'status' => \App\Enums\KycStatus::class,
    ];

    public function documents()
    {
        return $this->hasMany(KycDocument::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
