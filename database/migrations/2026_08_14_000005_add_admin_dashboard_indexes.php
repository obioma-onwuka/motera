<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the admin dashboard analytics queries:
     * - 30-day ledger volume:     ledger_entries.created_at (range scan + group)
     * - weekly deposit/withdrawal: transactions(status, created_at)
     * - user growth:              users.created_at
     * - tier donut:               bank_accounts.tier
     */
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->index('tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex(['tier']);
        });
    }
};
