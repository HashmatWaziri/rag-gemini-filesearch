<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_document_id')->constrained('curriculum_documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('material_kind');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('format');
            $table->longText('extracted_text')->nullable();
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('gemini_file_name')->nullable();
            $table->string('gemini_document_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['curriculum_document_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_document_versions');
    }
};
