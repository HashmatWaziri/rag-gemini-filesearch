<?php

declare(strict_types=1);

use App\Http\Controllers\Glc\Placement\AnswerController;
use App\Http\Controllers\Glc\Placement\EntryController;
use App\Http\Controllers\Glc\Placement\HeartbeatController;
use App\Http\Controllers\Glc\Placement\IntegrityEventController;
use App\Http\Controllers\Glc\Placement\ListeningAudioController;
use App\Http\Controllers\Glc\Placement\MicCheckController;
use App\Http\Controllers\Glc\Placement\OnboardingController;
use App\Http\Controllers\Glc\Placement\SectionController;
use App\Http\Controllers\Glc\Placement\SessionStatusController;
use App\Http\Controllers\Glc\Placement\SpeakingController;
use App\Http\Controllers\Glc\Placement\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('placement')->name('placement.')->group(function (): void {
    Route::get('/', [EntryController::class, 'show'])->name('entry');
    Route::post('/validate-code', [EntryController::class, 'validateCode'])
        ->middleware('throttle:placement-onboarding')->name('code.validate');
    Route::post('/start', [EntryController::class, 'start'])
        ->middleware('throttle:placement-onboarding')->name('start');

    Route::get('/blocked', [SessionStatusController::class, 'blocked'])->name('blocked');
    Route::get('/expired', [SessionStatusController::class, 'expired'])->name('expired');
    Route::get('/terminated', [SessionStatusController::class, 'terminated'])->name('terminated');

    Route::get('/instructions', [OnboardingController::class, 'instructions'])->name('instructions');
    Route::post('/instructions', [OnboardingController::class, 'acknowledgeInstructions'])
        ->middleware('throttle:placement-onboarding')->name('instructions.acknowledge');
    Route::get('/device-check', [OnboardingController::class, 'deviceCheck'])->name('device-check');
    Route::post('/device-check', [OnboardingController::class, 'confirmDeviceCheck'])
        ->middleware('throttle:placement-onboarding')->name('device-check.confirm');
    Route::post('/device-check/transcribe', [MicCheckController::class, 'transcribe'])
        ->middleware('throttle:placement-media')->name('device-check.transcribe');

    Route::get('/test', [SectionController::class, 'show'])->name('test');
    Route::post('/section/complete', [SectionController::class, 'complete'])
        ->middleware('throttle:placement-section')->name('section.complete');

    Route::post('/answers', [AnswerController::class, 'storeObjective'])
        ->middleware('throttle:placement-autosave')->name('answers.store');
    Route::post('/writing', [AnswerController::class, 'storeWriting'])
        ->middleware('throttle:placement-autosave')->name('writing.save');
    Route::post('/heartbeat', [HeartbeatController::class, 'store'])
        ->middleware('throttle:placement-autosave')->name('heartbeat');

    Route::post('/listening/play/{item}', [ListeningAudioController::class, 'registerPlay'])
        ->middleware('throttle:placement-media')->name('listening.play');
    Route::get('/listening/stream/{item}', [ListeningAudioController::class, 'stream'])
        ->name('listening.stream');

    Route::post('/speaking', [SpeakingController::class, 'store'])
        ->middleware('throttle:placement-media')->name('speaking.upload');

    Route::post('/integrity', [IntegrityEventController::class, 'store'])
        ->middleware('throttle:placement-integrity')->name('integrity.store');

    Route::post('/submit', [SubmissionController::class, 'submit'])
        ->middleware('throttle:placement-section')->name('submit');
    Route::get('/complete', [SubmissionController::class, 'complete'])->name('complete');
});
