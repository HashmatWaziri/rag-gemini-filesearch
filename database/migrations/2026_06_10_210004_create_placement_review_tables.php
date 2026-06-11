<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->unique()->constrained('placement_attempts')->cascadeOnDelete();
            $table->json('section_scores')->nullable();
            $table->decimal('composite', 5, 2)->nullable();
            $table->string('suggested_level')->nullable();
            $table->boolean('variance_flagged')->default(false);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('placement_ai_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->string('section');
            $table->json('dimension_scores')->nullable();
            $table->longText('transcript')->nullable();
            $table->longText('feedback')->nullable();
            $table->string('confidence')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['placement_attempt_id', 'section']);
        });

        Schema::create('placement_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->unique()->constrained('placement_attempts')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('final_level')->nullable();
            $table->json('skill_levels')->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('narrative')->nullable();
            $table->timestamp('narrative_approved_at')->nullable();
            $table->foreignId('narrative_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('flags')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('placement_review_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_review_id')->constrained('placement_reviews')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('placement_result_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('email_to');
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_result_links');
        Schema::dropIfExists('placement_review_notes');
        Schema::dropIfExists('placement_reviews');
        Schema::dropIfExists('placement_ai_drafts');
        Schema::dropIfExists('placement_scores');
    }
};
