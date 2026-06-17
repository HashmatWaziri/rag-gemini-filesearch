<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_usage_daily', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('active_minutes')->default(0);
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedInteger('conversation_starts')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['date']);
        });

        Schema::create('tutor_progress_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_progress_reports');
        Schema::dropIfExists('tutor_usage_daily');
    }
};
