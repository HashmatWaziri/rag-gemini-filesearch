<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_access_code_id')->constrained('placement_access_codes')->cascadeOnDelete();
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->unsignedTinyInteger('candidate_age');
            $table->string('status')->default('in_progress')->index();
            $table->string('device_token', 64)->index();
            $table->string('current_section')->nullable();
            $table->timestamp('privacy_acknowledged_at')->nullable();
            $table->timestamp('instructions_acknowledged_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('placement_section_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->string('section');
            $table->string('status')->default('locked');
            $table->unsignedInteger('time_limit_seconds');
            $table->unsignedInteger('time_used_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_resumed_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();

            $table->unique(['placement_attempt_id', 'section']);
        });

        Schema::create('placement_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->foreignId('placement_item_id')->constrained('placement_items')->cascadeOnDelete();
            $table->json('response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->unsignedTinyInteger('recording_attempts')->default(0);
            $table->timestamps();

            $table->unique(['placement_attempt_id', 'placement_item_id']);
        });

        Schema::create('placement_audio_plays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->foreignId('placement_item_id')->constrained('placement_items')->cascadeOnDelete();
            $table->timestamp('played_at');
            $table->timestamps();

            $table->unique(['placement_attempt_id', 'placement_item_id']);
        });

        Schema::create('placement_integrity_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('placement_attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->string('type')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_integrity_events');
        Schema::dropIfExists('placement_audio_plays');
        Schema::dropIfExists('placement_answers');
        Schema::dropIfExists('placement_section_states');
        Schema::dropIfExists('placement_attempts');
    }
};
