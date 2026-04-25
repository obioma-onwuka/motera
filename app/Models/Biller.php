<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Enums\BillCategory;

class Biller extends Model
{
    use HasUuids;

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

    public function payments()
    {
        return $this->hasMany(BillPayment::class);
    }
}
