<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Curriculum\ArchiveDocumentController;
use App\Http\Controllers\Glc\Curriculum\BulkUploadController;
use App\Http\Controllers\Glc\Curriculum\CourseController;
use App\Http\Controllers\Glc\Curriculum\CourseLessonController;
use App\Http\Controllers\Glc\Curriculum\CourseLevelController;
use App\Http\Controllers\Glc\Curriculum\CourseUnitController;
use App\Http\Controllers\Glc\Curriculum\DocumentController;
use App\Http\Controllers\Glc\Curriculum\LessonMaterialsUploadController;
use App\Http\Controllers\Glc\Curriculum\LessonUploadCapacityController;
use App\Http\Controllers\Glc\Curriculum\PublishDocumentController;
use App\Http\Controllers\Glc\Curriculum\ReindexDocumentController;
use App\Http\Controllers\Glc\Curriculum\ReplaceDocumentController;
use App\Http\Controllers\Glc\Curriculum\RestoreDocumentVersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff/curriculum')->name('curriculum.')->middleware(['auth'])->group(function (): void {
    Route::get('/', [DocumentController::class, 'index'])->name('index');

    Route::get('/lessons/{lesson}/upload-capacity', LessonUploadCapacityController::class)
        ->name('lessons.upload-capacity');

    Route::middleware('throttle:curriculum-upload')->group(function (): void {
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::post('/documents/bulk', BulkUploadController::class)->name('documents.bulk');
        Route::post('/documents/lesson-materials', LessonMaterialsUploadController::class)->name('documents.lesson-materials');
    });

    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::post('/documents/{document}/publish', PublishDocumentController::class)->name('documents.publish');
    Route::post('/documents/{document}/archive', ArchiveDocumentController::class)->name('documents.archive');
    Route::post('/documents/{document}/replace', ReplaceDocumentController::class)->name('documents.replace');
    Route::post('/documents/{document}/reindex', ReindexDocumentController::class)->name('documents.reindex');
    Route::post('/documents/{document}/versions/{version}/restore', RestoreDocumentVersionController::class)
        ->whereNumber('version')
        ->name('documents.versions.restore');

    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    Route::post('/levels', [CourseLevelController::class, 'store'])->name('levels.store');
    Route::put('/levels/{level}', [CourseLevelController::class, 'update'])->name('levels.update');
    Route::delete('/levels/{level}', [CourseLevelController::class, 'destroy'])->name('levels.destroy');

    Route::post('/units', [CourseUnitController::class, 'store'])->name('units.store');
    Route::put('/units/{unit}', [CourseUnitController::class, 'update'])->name('units.update');
    Route::delete('/units/{unit}', [CourseUnitController::class, 'destroy'])->name('units.destroy');

    Route::post('/lessons', [CourseLessonController::class, 'store'])->name('lessons.store');
    Route::put('/lessons/{lesson}', [CourseLessonController::class, 'update'])->name('lessons.update');
    Route::delete('/lessons/{lesson}', [CourseLessonController::class, 'destroy'])->name('lessons.destroy');
});
