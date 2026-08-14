<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill bank_accounts.tier from the old int-backed enum values
     * ('1', '2', '3') to the string-backed KycTier values ('tier_1', ...).
     */
    public function up(): void
    {
        DB::table('bank_accounts')->where('tier', '1')->update(['tier' => 'tier_1']);
        DB::table('bank_accounts')->where('tier', '2')->update(['tier' => 'tier_2']);
        DB::table('bank_accounts')->where('tier', '3')->update(['tier' => 'tier_3']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('bank_accounts')->where('tier', 'tier_1')->update(['tier' => '1']);
        DB::table('bank_accounts')->where('tier', 'tier_2')->update(['tier' => '2']);
        DB::table('bank_accounts')->where('tier', 'tier_3')->update(['tier' => '3']);
    }
};
