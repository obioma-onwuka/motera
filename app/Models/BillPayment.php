<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use HasUuids;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function biller()
    {
        return $this->belongsTo(Biller::class);
    }
}
