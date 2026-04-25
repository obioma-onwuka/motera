<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->foreignUuid('bank_account_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('bank_account_id');
            $table->decimal('balance_after', 20, 2)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['user_id', 'bank_account_id']);
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['reference', 'balance_after']);
        });
    }
};
