<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->nullable()->index();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_email')->nullable();
            $table->timestamp('guardian_consent_confirmed_at')->nullable();
            $table->foreignId('guardian_consent_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('guardian_consent_confirmed_by');
            $table->dropColumn([
                'role',
                'age',
                'guardian_name',
                'guardian_email',
                'guardian_consent_confirmed_at',
            ]);
        });
    }
};
