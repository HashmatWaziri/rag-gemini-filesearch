<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Staff\PublicResultController;
use Illuminate\Support\Facades\Route;

Route::prefix('placement/result')->name('placement.result.')->group(function (): void {
    Route::get('{token}', [PublicResultController::class, 'show'])->name('show');
    Route::get('{token}/download', [PublicResultController::class, 'download'])->name('download');
});
