<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'amount',
        'reference',
        'status',
        'bank_name',
        'account_number',
        'account_name',
        'admin_note',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
