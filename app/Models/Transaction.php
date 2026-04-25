<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\TransactionStatus;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'reference',
        'type',
        'amount',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'status' => TransactionStatus::class,
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
