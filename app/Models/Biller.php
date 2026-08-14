<?php

namespace App\Models;

use App\Enums\BillCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Biller extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'category',
        'logo_url',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'category' => BillCategory::class,
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }
}
