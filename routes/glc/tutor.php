<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Tutor\BlockedController;
use App\Http\Controllers\Glc\Tutor\ConversationController;
use App\Http\Controllers\Glc\Tutor\MessageController;
use App\Http\Controllers\Glc\Tutor\StaffTutorController;
use App\Http\Controllers\Glc\Tutor\StudentAssignmentController;
use App\Http\Controllers\Glc\Tutor\StudentLinkController;
use App\Http\Controllers\Glc\Tutor\StudentRosterController;
use App\Http\Controllers\Glc\Tutor\WritingController;
use Illuminate\Support\Facades\Route;

Route::prefix('tutor')->name('tutor.')->middleware(['auth', 'glc.role:student'])->group(function (): void {
    Route::get('/blocked', BlockedController::class)->name('blocked');

    Route::middleware('glc.tutor')->group(function (): void {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');

        Route::get('/writing', [WritingController::class, 'index'])->name('writing.index');
        Route::post('/writing', [WritingController::class, 'store'])->name('writing.store');
        Route::get('/writing/{submission}', [WritingController::class, 'show'])->name('writing.show');
    });
});

Route::prefix('staff')->name('staff.')->middleware(['auth', 'glc.role:teacher,academic_supervisor,admin'])->group(function (): void {
    Route::get('/students', [StudentRosterController::class, 'index'])->name('students.index');
    Route::post('/students/{student}/link', [StudentLinkController::class, 'store'])->name('students.link');
    Route::delete('/students/{student}/link', [StudentLinkController::class, 'destroy'])->name('students.unlink');
    Route::put('/students/{student}/assignment', [StudentAssignmentController::class, 'update'])->name('students.assignment.update');

    Route::get('/tutor', [StaffTutorController::class, 'index'])->name('tutor.index');
    Route::get('/tutor/students/{student}', [StaffTutorController::class, 'student'])->name('tutor.students.show');
    Route::get('/tutor/conversations/{conversation}', [StaffTutorController::class, 'conversation'])->name('tutor.conversations.show');
    Route::get('/tutor/writing/{submission}', [StaffTutorController::class, 'writing'])->name('tutor.writing.show');
});
