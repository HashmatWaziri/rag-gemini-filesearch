<?php

declare(strict_types=1);

use App\Models\Glc\TutorProgressReport;
use App\Models\User;
use App\Services\Glc\Tutor\TutorProgressSummaryAgent;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config(['glc.tutor.progress_analytics_enabled' => true]);
});

it('returns not found for progress report route when analytics are disabled', function (): void {
    config(['glc.tutor.progress_analytics_enabled' => false]);

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    actingAs($teacher)
        ->post(route('staff.tutor.students.progress-report.store', $student))
        ->assertNotFound();
});

it('queues a progress report for an assigned student', function (): void {
    Queue::fake();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    actingAs($teacher)
        ->post(route('staff.tutor.students.progress-report.store', $student))
        ->assertRedirect();

    $report = TutorProgressReport::query()->sole();

    expect($report->user_id)->toBe($student->id)
        ->and($report->generated_by)->toBe($teacher->id)
        ->and($report->status)->toBe('pending');

    Queue::assertPushed(App\Jobs\Glc\Tutor\GenerateTutorProgressReportJob::class);
});

it('generates a completed progress report using the AI agent', function (): void {
    config(['ai.providers.gemini.key' => 'test-key']);

    TutorProgressSummaryAgent::fake([[
        'summary' => 'The student practiced grammar regularly.',
        'strengths' => ['Consistent engagement'],
        'focus_areas' => ['Articles'],
        'engagement_note' => 'Active several times this month.',
    ]])->preventStrayPrompts();

    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $report = app(App\Services\Glc\Tutor\TutorProgressReportService::class)
        ->queue($teacher, $student);

    app(App\Services\Glc\Tutor\TutorProgressReportService::class)->generate($report->fresh());

    $report = $report->fresh();

    expect($report->status)->toBe('completed')
        ->and($report->payload)->toMatchArray([
            'summary' => 'The student practiced grammar regularly.',
            'strengths' => ['Consistent engagement'],
            'focus_areas' => ['Articles'],
        ]);
});

it('blocks teachers from generating reports for unassigned students', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    actingAs($teacher)
        ->post(route('staff.tutor.students.progress-report.store', $student))
        ->assertForbidden();
});
