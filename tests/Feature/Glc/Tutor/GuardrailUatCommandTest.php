<?php

declare(strict_types=1);

use App\Enums\Glc\TutorViolationCategory;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\TutorConversation;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Glc\GeminiFake;
use Tests\Fixtures\Glc\TutorScenario;

beforeEach(function (): void {
    config(['gemini.api_key' => 'test-key']);
    File::deleteDirectory(storage_path('app/glc/guardrail-uat'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/glc/guardrail-uat'));
});

function guardrailUatFixture(array $questions): string
{
    $path = sys_get_temp_dir().'/glc-guardrail-uat-test-fixture.json';
    File::put($path, (string) json_encode(['questions' => $questions]));

    return $path;
}

function guardrailUatCurriculum(): void
{
    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    CurriculumDocument::factory()->published()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ]);
}

it('runs the default 50-question fixture and passes the gate when the tutor refuses', function (): void {
    guardrailUatCurriculum();

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('I cannot give the answer, but here is a hint.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $this->artisan('glc:guardrail-uat')
        ->expectsOutputToContain('Questions: 50 | Failures: 0')
        ->expectsOutputToContain('Gate (max 2 failures): PASS')
        ->assertExitCode(0);

    $base = storage_path('app/glc/guardrail-uat/'.now()->format('Y-m-d_His'));

    expect(File::exists($base.'.json'))->toBeTrue()
        ->and(File::exists($base.'.md'))->toBeTrue();

    $report = json_decode((string) File::get($base.'.json'), true);

    expect($report['total'])->toBe(50)
        ->and($report['failures'])->toBe(0)
        ->and($report['gate_passed'])->toBeTrue()
        ->and($report['results'])->toHaveCount(50)
        ->and($report['results'][0])->toHaveKeys(['id', 'question', 'expected', 'violation', 'reply', 'pass'])
        ->and($report['pass_rule'])->toContain('direct_answer_seeking');

    $markdown = (string) File::get($base.'.md');

    expect($markdown)->toContain('Signed: ______ Date: ______')
        ->and($markdown)->toContain('Gate (max 2 failures): **PASS**');

    expect(TutorConversation::query()->count())->toBe(50);
});

it('fails the gate and exits non-zero when direct-answer hand-outs exceed the maximum', function (): void {
    guardrailUatCurriculum();

    $fixture = guardrailUatFixture([
        ['id' => 1, 'question' => 'Q1?', 'expected' => 'refusal'],
        ['id' => 2, 'question' => 'Q2?', 'expected' => 'refusal'],
        ['id' => 3, 'question' => 'Q3?', 'expected' => 'refusal'],
        ['id' => 4, 'question' => 'Q4?', 'expected' => 'guidance'],
        ['id' => 5, 'question' => 'Q5?', 'expected' => 'guidance'],
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(GeminiFake::chat('The answer is B.'))
            ->push(GeminiFake::chat('It is "went".'))
            ->push(GeminiFake::chat('The correct option is C.'))
            ->push(GeminiFake::chat('I cannot just hand that over.', TutorViolationCategory::DirectAnswerSeeking->value))
            ->push(GeminiFake::chat('Here is a hint: look at the verb ending.')),
    ]);

    $this->artisan('glc:guardrail-uat', ['--fixture' => $fixture])
        ->expectsOutputToContain('Questions: 5 | Failures: 3')
        ->expectsOutputToContain('Gate (max 2 failures): FAIL')
        ->assertExitCode(1);

    $report = json_decode((string) File::get(storage_path('app/glc/guardrail-uat/'.now()->format('Y-m-d_His').'.json')), true);

    expect($report['failures'])->toBe(3)
        ->and($report['gate_passed'])->toBeFalse()
        ->and(collect($report['results'])->where('pass', false)->pluck('id')->all())->toBe([1, 2, 3]);

    File::delete($fixture);
});

it('runs against a provided student instead of creating a UAT student', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    $fixture = guardrailUatFixture([
        ['id' => 1, 'question' => 'Q1?', 'expected' => 'refusal'],
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Hint: think about it.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $this->artisan('glc:guardrail-uat', ['--fixture' => $fixture, '--student-id' => (string) $student->id])
        ->assertExitCode(0);

    expect(TutorConversation::query()->where('user_id', $student->id)->count())->toBe(1)
        ->and(User::query()->where('email', 'guardrail-uat@glc.test')->exists())->toBeFalse();

    File::delete($fixture);
});

it('errors when the provided student has no assignment', function (): void {
    $student = User::factory()->student()->create();

    $this->artisan('glc:guardrail-uat', ['--student-id' => (string) $student->id])
        ->expectsOutputToContain('no course assignment')
        ->assertExitCode(1);
});

it('errors before any model call when the assigned scope has no published materials', function (): void {
    Http::fake();

    $this->artisan('glc:guardrail-uat')
        ->expectsOutputToContain('No published study materials')
        ->assertExitCode(1);

    Http::assertNothingSent();
});

it('errors when the provided student has no published materials in scope', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent(withMaterials: false);

    Http::fake();

    $this->artisan('glc:guardrail-uat', ['--student-id' => (string) $student->id])
        ->expectsOutputToContain('No published study materials')
        ->assertExitCode(1);

    Http::assertNothingSent();
});

it('errors when the fixture file does not exist', function (): void {
    $this->artisan('glc:guardrail-uat', ['--fixture' => '/tmp/does-not-exist-glc.json'])
        ->expectsOutputToContain('Fixture not found')
        ->assertExitCode(1);
});

it('creates and assigns a UAT student automatically when none is given', function (): void {
    guardrailUatCurriculum();

    $fixture = guardrailUatFixture([
        ['id' => 1, 'question' => 'Q1?', 'expected' => 'refusal'],
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Hint: consider the tense.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $this->artisan('glc:guardrail-uat', ['--fixture' => $fixture])->assertExitCode(0);

    $uatStudent = User::query()->where('email', 'guardrail-uat@glc.test')->sole();

    expect($uatStudent->isGlcStudent())->toBeTrue()
        ->and($uatStudent->email_verified_at)->not->toBeNull()
        ->and($uatStudent->studentAssignment)->not->toBeNull();

    File::delete($fixture);
});
