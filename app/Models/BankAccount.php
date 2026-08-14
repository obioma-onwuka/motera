<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\KycTier;
use App\Exceptions\AccountRestrictedException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BankAccount extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Throw if the account is restricted from performing transactions.
     */
    public function assertNotRestricted(): void
    {
        if ($this->is_restricted) {
            throw new AccountRestrictedException($this->restriction_reason ?: 'This account is restricted from performing transactions.');
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
