<?php

declare(strict_types=1);

namespace App\Services\Glc\Placement;

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementIntegrityEventType;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAccessCode;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementSectionState;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

final class PlacementSessionService
{
    public const string COOKIE_NAME = 'glc_placement_session';

    public function start(PlacementAccessCode $code, string $name, string $email, int $age): PlacementAttempt
    {
        $attempt = PlacementAttempt::query()->create([
            'placement_access_code_id' => $code->id,
            'candidate_name' => $name,
            'candidate_email' => $email,
            'candidate_age' => $age,
            'status' => PlacementAttemptStatus::InProgress,
            'device_token' => Str::random(64),
            'current_section' => PlacementSection::Reading,
            'privacy_acknowledged_at' => now(),
            'last_activity_at' => now(),
            'started_at' => now(),
        ]);

        foreach (PlacementSection::ordered() as $section) {
            $attempt->sectionStates()->create([
                'section' => $section,
                'status' => PlacementSectionStatus::Locked,
                'time_limit_seconds' => $section->timeLimitSeconds(),
                'time_used_seconds' => 0,
            ]);
        }

        $code->update(['status' => PlacementAccessCodeStatus::InProgress]);

        return $attempt;
    }

    public function makeCookie(PlacementAttempt $attempt): Cookie
    {
        return cookie(
            name: self::COOKIE_NAME,
            value: $attempt->id.'|'.$attempt->device_token,
            minutes: config()->integer('glc.placement.resume_window_hours', 24) * 60,
        );
    }

    public function currentAttempt(Request $request): ?PlacementAttempt
    {
        $cookie = $request->cookie(self::COOKIE_NAME);

        if (! is_string($cookie) || ! str_contains($cookie, '|')) {
            return null;
        }

        [$id, $token] = explode('|', $cookie, 2);

        if (! ctype_digit($id) || mb_strlen($token) !== 64) {
            return null;
        }

        $attempt = PlacementAttempt::query()->find((int) $id);

        if (! $attempt instanceof PlacementAttempt || ! hash_equals($attempt->device_token, $token)) {
            return null;
        }

        return $attempt;
    }

    public function expectedRouteName(PlacementAttempt $attempt): string
    {
        if ($attempt->status === PlacementAttemptStatus::Terminated) {
            return 'placement.terminated';
        }

        if ($attempt->status === PlacementAttemptStatus::Submitted) {
            return 'placement.complete';
        }

        if (! $attempt->isWithinResumeWindow()) {
            return 'placement.expired';
        }

        if ($attempt->instructions_acknowledged_at === null) {
            return 'placement.instructions';
        }

        if ($this->sectionState($attempt, PlacementSection::Reading)->status === PlacementSectionStatus::Locked) {
            return 'placement.device-check';
        }

        return 'placement.test';
    }

    public function requireActiveAttempt(Request $request): PlacementAttempt
    {
        $attempt = $this->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            $this->abortJson(401, 'We could not find your test on this device. Please enter your access code again.', route('placement.entry'));
        }

        if ($attempt->status === PlacementAttemptStatus::Terminated) {
            $this->abortJson(409, 'This test was ended - please contact GLC.', route('placement.terminated'));
        }

        if ($attempt->status === PlacementAttemptStatus::Submitted) {
            $this->abortJson(409, 'This placement test has already been submitted.', route('placement.complete'));
        }

        if (! $attempt->isWithinResumeWindow()) {
            $this->abortJson(410, 'This test has expired - please contact GLC for a new access code.', route('placement.expired'));
        }

        $attempt->update(['last_activity_at' => now()]);

        return $attempt;
    }

    public function terminateForDualDevice(PlacementAttempt $attempt): void
    {
        $attempt->update([
            'status' => PlacementAttemptStatus::Terminated,
            'terminated_at' => now(),
            'termination_reason' => 'dual_device',
            'current_section' => null,
        ]);

        $attempt->integrityEvents()->create([
            'type' => PlacementIntegrityEventType::DualDevice,
            'metadata' => ['detected_at_route' => 'entry'],
            'occurred_at' => now(),
        ]);
    }

    public function finalizeSubmission(PlacementAttempt $attempt): void
    {
        $attempt->update([
            'status' => PlacementAttemptStatus::Submitted,
            'submitted_at' => now(),
            'current_section' => null,
            'last_activity_at' => now(),
        ]);

        $attempt->accessCode->update(['status' => PlacementAccessCodeStatus::Completed]);

        $attempt->review()->firstOrCreate([], ['status' => PlacementReviewStatus::Pending]);

        if (class_exists(\App\Services\Glc\Review\ScoringService::class)) {
            app(\App\Services\Glc\Review\ScoringService::class)->scoreAttempt($attempt);
        }

        if (class_exists(\App\Jobs\Glc\Placement\GeneratePlacementAiDraftsJob::class)) {
            dispatch(new \App\Jobs\Glc\Placement\GeneratePlacementAiDraftsJob($attempt->id));
        }
    }

    public function sectionState(PlacementAttempt $attempt, PlacementSection $section): PlacementSectionState
    {
        return $attempt->sectionStates()->where('section', $section)->firstOrFail();
    }

    private function abortJson(int $status, string $message, string $redirect): never
    {
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'redirect' => $redirect,
        ], $status));
    }
}
