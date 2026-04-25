<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CardRequest extends Model
{
    use HasUuids;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
