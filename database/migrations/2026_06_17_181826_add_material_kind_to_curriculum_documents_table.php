<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_documents', function (Blueprint $table): void {
            $table->string('material_kind')->default('other')->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_documents', function (Blueprint $table): void {
            $table->dropIndex(['material_kind']);
            $table->dropColumn('material_kind');
        });
    }
};
