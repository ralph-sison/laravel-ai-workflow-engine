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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('name');
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('type'); // ai|http|transform|notification|condition|delay
            $table->json('config')->nullable();
            $table->string('on_error')->default('stop'); // stop|continue|retry
            $table->unsignedTinyInteger('retry_limit')->default(3);
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workflow_id')->references('id')->on('workflows')->cascadeOnDelete();
            $table->index(['workflow_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
