<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('course_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('course_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_level_id')->constrained('course_levels')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('course_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_unit_id')->constrained('course_units')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('curriculum_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('course_level_id')->constrained('course_levels')->cascadeOnDelete();
            $table->foreignId('course_unit_id')->constrained('course_units')->cascadeOnDelete();
            $table->foreignId('course_lesson_id')->nullable()->constrained('course_lessons')->nullOnDelete();
            $table->string('title');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('format');
            $table->longText('extracted_text')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('gemini_file_name')->nullable();
            $table->string('gemini_document_name')->nullable();
            $table->string('index_status')->default('pending')->index();
            $table->text('index_error')->nullable();
            $table->timestamps();
        });

        Schema::create('student_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('course_level_id')->constrained('course_levels')->cascadeOnDelete();
            $table->foreignId('course_unit_id')->constrained('course_units')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('teacher_student', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_student');
        Schema::dropIfExists('student_assignments');
        Schema::dropIfExists('curriculum_documents');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('course_units');
        Schema::dropIfExists('course_levels');
        Schema::dropIfExists('courses');
    }
};
