<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->longText('summary')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tutor_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tutor_conversation_id')->constrained('tutor_conversations')->cascadeOnDelete();
            $table->string('role');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tutor_violations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_conversation_id')->nullable()->constrained('tutor_conversations')->nullOnDelete();
            $table->foreignId('tutor_message_id')->nullable()->constrained('tutor_messages')->nullOnDelete();
            $table->string('category')->index();
            $table->text('excerpt')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('writing_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_conversation_id')->nullable()->constrained('tutor_conversations')->nullOnDelete();
            $table->longText('text');
            $table->json('feedback')->nullable();
            $table->json('highlights')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_submissions');
        Schema::dropIfExists('tutor_violations');
        Schema::dropIfExists('tutor_messages');
        Schema::dropIfExists('tutor_conversations');
    }
};
