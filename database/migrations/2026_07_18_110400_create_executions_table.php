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
        Schema::create('executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->uuid('triggered_by')->nullable(); // user id, null for automated triggers
            $table->string('trigger_type')->default('manual'); // manual|webhook|schedule
            $table->string('status')->default('pending'); // pending|running|success|failed|cancelled
            $table->json('payload')->nullable();    // inbound data that triggered the run
            $table->json('context')->nullable();    // data envelope passed between steps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->foreign('workflow_id')->references('id')->on('workflows')->cascadeOnDelete();
            $table->foreign('triggered_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['workflow_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('executions');
    }
};
