<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\AccountStatus;
use App\Enums\KycTier;

class BankAccount extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_number',
        'ledger_balance',
        'available_balance',
        'currency',
        'status',
        'tier',
        'is_restricted',
        'restriction_reason',
    ];

    protected $casts = [
        'status' => AccountStatus::class,
        'tier' => KycTier::class,
        'ledger_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'is_restricted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
