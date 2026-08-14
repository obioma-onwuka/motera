<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PostgreSQL does not auto-index FK columns. Add indexes for the
     * columns the hot paths filter/sort by.
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('bank_account_id');
            $table->index('status');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->index(['bank_account_id', 'created_at']);
        });

        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('bank_account_id');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('bank_account_id');
        });

        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });

        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->index('kyc_submission_id');
        });

        Schema::table('bill_payments', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('bank_account_id');
            $table->index('biller_id');
        });

        Schema::table('card_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index('bank_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['bank_account_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropIndex(['bank_account_id', 'created_at']);
        });

        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['bank_account_id']);
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['bank_account_id']);
        });

        Schema::table('kyc_submissions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropIndex(['kyc_submission_id']);
        });

        Schema::table('bill_payments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['bank_account_id']);
            $table->dropIndex(['biller_id']);
        });

        Schema::table('card_requests', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['bank_account_id']);
        });
    }
};
