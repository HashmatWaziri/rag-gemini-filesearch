<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_ai_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->unique()->constrained('placement_attempts')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('recommended_level')->nullable();
            $table->json('skill_levels')->nullable();
            $table->json('skill_summaries')->nullable();
            $table->string('confidence')->nullable();
            $table->text('rationale')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_ai_recommendations');
    }
};
