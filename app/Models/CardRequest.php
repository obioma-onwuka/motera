<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'type',
        'status',
        'card_name',
        'delivery_address',
        'fee',
        'metadata',
    ];

    protected $casts = [
        'fee' => 'decimal:4',
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
}
