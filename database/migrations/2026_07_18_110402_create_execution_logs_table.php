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
        Schema::create('execution_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('execution_id');
            $table->uuid('step_id');
            $table->string('status'); // pending|running|success|failed|skipped
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->foreign('execution_id')->references('id')->on('executions')->cascadeOnDelete();
            $table->foreign('step_id')->references('id')->on('workflow_steps')->cascadeOnDelete();
            $table->index(['execution_id', 'step_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('execution_logs');
    }
};
