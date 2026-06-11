<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Staff\NarrativeController;
use App\Http\Controllers\Glc\Staff\PlacementContentController;
use App\Http\Controllers\Glc\Staff\PlacementContentPdfPreviewController;
use App\Http\Controllers\Glc\Staff\PlacementMediaController;
use App\Http\Controllers\Glc\Staff\ResultPdfController;
use App\Http\Controllers\Glc\Staff\ResultSendController;
use App\Http\Controllers\Glc\Staff\ReviewAssignmentController;
use App\Http\Controllers\Glc\Staff\ReviewController;
use App\Http\Controllers\Glc\Staff\ReviewDecisionController;
use App\Http\Controllers\Glc\Staff\ReviewNoteController;
use App\Http\Controllers\Glc\Staff\ReviewQueueController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->name('staff.')->middleware(['auth', 'glc.role:teacher,academic_supervisor,admin'])->group(function (): void {
    Route::get('review', [ReviewQueueController::class, 'index'])->name('review.index');
    Route::get('review/{review}', [ReviewController::class, 'show'])->name('review.show');

    Route::post('review/{review}/claim', [ReviewAssignmentController::class, 'claim'])->name('review.claim');
    Route::post('review/{review}/assign', [ReviewAssignmentController::class, 'assign'])->name('review.assign');

    Route::put('review/{review}/decision', [ReviewDecisionController::class, 'update'])->name('review.decision');
    Route::post('review/{review}/approve', [ReviewDecisionController::class, 'approve'])->name('review.approve');

    Route::post('review/{review}/notes', [ReviewNoteController::class, 'store'])->name('review.notes.store');

    Route::put('review/{review}/narrative', [NarrativeController::class, 'update'])->name('review.narrative.update');
    Route::post('review/{review}/narrative/draft', [NarrativeController::class, 'draft'])->name('review.narrative.draft');
    Route::post('review/{review}/narrative/approve', [NarrativeController::class, 'approve'])->name('review.narrative.approve');

    Route::get('review/{review}/pdf', [ResultPdfController::class, 'show'])->name('review.pdf');
    Route::post('review/{review}/send', [ResultSendController::class, 'store'])->name('review.send');

    Route::get('placement-items/{item}/audio', [PlacementMediaController::class, 'itemAudio'])->name('items.audio');
    Route::get('review/{review}/recording', [PlacementMediaController::class, 'recording'])->name('review.recording');

    Route::middleware('glc.role:academic_supervisor,admin')->group(function (): void {
        Route::get('placement-content', [PlacementContentController::class, 'index'])->name('content.index');
        Route::post('placement-content/items', [PlacementContentController::class, 'store'])->name('content.items.store');
        Route::put('placement-content/items/{item}', [PlacementContentController::class, 'update'])->name('content.items.update');
        Route::delete('placement-content/items/{item}', [PlacementContentController::class, 'destroy'])->name('content.items.destroy');
        Route::post('placement-content/pdf-preview', PlacementContentPdfPreviewController::class)->name('content.pdf.preview');
    });
});
