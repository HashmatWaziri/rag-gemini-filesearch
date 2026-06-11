<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Admin\AccessCodeController;
use App\Http\Controllers\Glc\Admin\AuditLogController;
use App\Http\Controllers\Glc\Admin\CurriculumIndexRebuildController;
use App\Http\Controllers\Glc\Admin\ExportController;
use App\Http\Controllers\Glc\Admin\SettingsController;
use App\Http\Controllers\Glc\Admin\UserAnonymizeController;
use App\Http\Controllers\Glc\Admin\UserConsentController;
use App\Http\Controllers\Glc\Admin\UserController;
use App\Http\Controllers\Glc\Admin\UserImportController;
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

    Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/{bundle}', [ExportController::class, 'download'])->name('exports.download');
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('curriculum-index/rebuild', [CurriculumIndexRebuildController::class, 'store'])->name('curriculum-index.rebuild');
});
