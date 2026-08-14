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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('account_number')->unique();
            $table->decimal('ledger_balance', 20, 2)->default(0.00);
            $table->decimal('available_balance', 20, 2)->default(0.00);
            $table->string('currency')->default('USD');
            $table->string('status')->default('active'); // active, suspended, restricted, closed
            $table->string('tier')->default('tier_1'); // tier_1, tier_2, tier_3
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
