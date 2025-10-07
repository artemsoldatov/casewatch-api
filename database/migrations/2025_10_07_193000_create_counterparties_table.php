<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('external_ref');
            // PII: encrypted at rest via the model cast
            $table->text('name');
            $table->string('kind'); // individual|business
            $table->string('country', 2);
            $table->string('chain'); // BTC|ETH|BSC|TRON
            $table->string('wallet_address');
            $table->timestamps();

            $table->unique(['organization_id', 'external_ref']);
            $table->index(['organization_id', 'chain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparties');
    }
};
