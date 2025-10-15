<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('chain');
            $table->string('tx_hash');
            $table->uuid('from_counterparty_id')->nullable();
            $table->uuid('to_counterparty_id')->nullable();
            // amounts in integer minor units, never float
            $table->bigInteger('amount_cents');
            $table->string('currency', 8)->default('USD');
            $table->timestampTz('occurred_at');
            $table->timestamps();

            $table->index(['organization_id', 'occurred_at']);
            $table->index('from_counterparty_id');
            $table->index('to_counterparty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
