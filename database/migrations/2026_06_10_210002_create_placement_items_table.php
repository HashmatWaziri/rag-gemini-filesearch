<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_items', function (Blueprint $table): void {
            $table->id();
            $table->string('section')->index();
            $table->string('type');
            $table->foreignId('parent_id')->nullable()->constrained('placement_items')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('title')->nullable();
            $table->longText('body')->nullable();
            $table->json('options')->nullable();
            $table->unsignedTinyInteger('correct_option')->nullable();
            $table->string('media_path')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_items');
    }
};
