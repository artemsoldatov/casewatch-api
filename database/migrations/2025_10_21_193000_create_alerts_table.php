<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('counterparty_id');
            $table->string('type'); // STRUCTURING|LAYERING|RAPID_MOVEMENT|HIGH_RISK_JURISDICTION
            $table->string('severity'); // LOW|MEDIUM|HIGH
            $table->unsignedInteger('score'); // 0-100
            $table->string('status')->default('OPEN'); // OPEN|IN_REVIEW|ESCALATED|CLEARED
            $table->string('assigned_to')->nullable(); // analyst handle, free-form
            // the rule findings that produced this alert
            $table->jsonb('rationale');
            $table->string('dedup_key')->unique();
            $table->timestampTz('opened_at')->useCurrent();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
