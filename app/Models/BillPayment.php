<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'biller_id',
        'amount',
        'reference',
        'customer_identifier',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function biller(): BelongsTo
    {
        return $this->belongsTo(Biller::class);
    }
}
