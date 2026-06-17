<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Admin\AccessCodeController;
use App\Http\Controllers\Glc\Admin\AiCostSettingsController;
use App\Http\Controllers\Glc\Admin\AiModelSettingsController;
use App\Http\Controllers\Glc\Admin\AuditLogController;
use App\Http\Controllers\Glc\Admin\BackupController;
use App\Http\Controllers\Glc\Admin\CurriculumIndexRebuildController;
use App\Http\Controllers\Glc\Admin\ExportController;
use App\Http\Controllers\Glc\Admin\RolePermissionController;
use App\Http\Controllers\Glc\Admin\SettingsController;
use App\Http\Controllers\Glc\Admin\SpeakingGuidelinesController;
use App\Http\Controllers\Glc\Admin\UserAnonymizeController;
use App\Http\Controllers\Glc\Admin\UserConsentController;
use App\Http\Controllers\Glc\Admin\UserController;
use App\Http\Controllers\Glc\Admin\UserImportController;
use App\Http\Controllers\Glc\Admin\WritingGuidelinesController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'glc.role:admin'])->group(function (): void {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::post('users/import', [UserImportController::class, 'store'])->name('users.import');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/consent', [UserConsentController::class, 'store'])->name('users.consent.store');
    Route::delete('users/{user}/consent', [UserConsentController::class, 'destroy'])->name('users.consent.destroy');
    Route::post('users/{user}/anonymize', [UserAnonymizeController::class, 'store'])->name('users.anonymize');

    Route::get('access-codes', [AccessCodeController::class, 'index'])->name('access-codes.index');
    Route::post('access-codes', [AccessCodeController::class, 'store'])->name('access-codes.store');
    Route::patch('access-codes/{accessCode}/revoke', [AccessCodeController::class, 'revoke'])->name('access-codes.revoke');

    Route::get('permissions', [RolePermissionController::class, 'index'])->name('permissions.index');
    Route::put('permissions', [RolePermissionController::class, 'update'])->name('permissions.update');

    Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/{bundle}', [ExportController::class, 'download'])->name('exports.download');
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('settings/ai-cost', [AiCostSettingsController::class, 'edit'])->name('settings.ai-cost.edit');
    Route::put('settings/ai-cost', [AiCostSettingsController::class, 'update'])->name('settings.ai-cost.update');
    Route::get('settings/ai', [AiModelSettingsController::class, 'edit'])->name('settings.ai.edit');
    Route::put('settings/ai/selection', [AiModelSettingsController::class, 'updateSelection'])->name('settings.ai.selection.update');
    Route::put('settings/ai/keys', [AiModelSettingsController::class, 'updateKey'])->name('settings.ai.keys.update');
    Route::get('settings/writing-guidelines', [WritingGuidelinesController::class, 'edit'])->name('settings.writing-guidelines.edit');
    Route::put('settings/writing-guidelines', [WritingGuidelinesController::class, 'update'])->name('settings.writing-guidelines.update');
    Route::delete('settings/writing-guidelines', [WritingGuidelinesController::class, 'destroy'])->name('settings.writing-guidelines.reset');
    Route::get('settings/speaking-guidelines', [SpeakingGuidelinesController::class, 'edit'])->name('settings.speaking-guidelines.edit');
    Route::put('settings/speaking-guidelines', [SpeakingGuidelinesController::class, 'update'])->name('settings.speaking-guidelines.update');
    Route::delete('settings/speaking-guidelines', [SpeakingGuidelinesController::class, 'destroy'])->name('settings.speaking-guidelines.reset');
    Route::post('curriculum-index/rebuild', [CurriculumIndexRebuildController::class, 'store'])->name('curriculum-index.rebuild');

    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('backups/download/{path}', [BackupController::class, 'download'])->where('path', '.*')->name('backups.download');
    Route::delete('backups/{path}', [BackupController::class, 'destroy'])->where('path', '.*')->name('backups.destroy');
    Route::post('backups/{path}/restore', [BackupController::class, 'restore'])->where('path', '.*')->name('backups.restore');
});
