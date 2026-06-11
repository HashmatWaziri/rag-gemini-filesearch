<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;
use App\Services\Glc\Curriculum\GeminiFileSearchService;

function bindFakeFileSearchService(bool $configured, array $result = []): void
{
    app()->instance(GeminiFileSearchService::class, new class($configured, $result)
    {
        public function __construct(
            private readonly bool $configured,
            private readonly array $result,
        ) {}

        public function isConfigured(): bool
        {
            return $this->configured;
        }

        /**
         * @return array{total: int, succeeded: int, failed: int}
         */
        public function rebuildStore(): array
        {
            return $this->result;
        }
    });
}

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->post(route('admin.curriculum-index.rebuild'))->assertRedirectToRoute('login');

    $supervisor = User::factory()->academicSupervisor()->create();

    $this->actingAs($supervisor)
        ->post(route('admin.curriculum-index.rebuild'))
        ->assertForbidden();
});

it('explains that the tool is unavailable while the service has not landed', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.curriculum-index.rebuild'))
        ->assertRedirectToRoute('admin.settings.edit')
        ->assertSessionHas('glc_status', "This tool isn't available yet.");

    expect(AuditLog::query()->where('action', AuditAction::CurriculumIndexRebuilt)->exists())->toBeFalse();
})->skip(class_exists(GeminiFileSearchService::class), 'GeminiFileSearchService has landed; the fallback no longer applies.');

it('explains when the AI service is not configured and does not audit', function (): void {
    $admin = User::factory()->admin()->create();

    bindFakeFileSearchService(configured: false);

    $this->actingAs($admin)
        ->post(route('admin.curriculum-index.rebuild'))
        ->assertRedirectToRoute('admin.settings.edit')
        ->assertSessionHas(
            'glc_status',
            "The AI service isn't set up on this environment yet — documents stay safe and can be re-published later.",
        );

    expect(AuditLog::query()->where('action', AuditAction::CurriculumIndexRebuilt)->exists())->toBeFalse();
})->skip(! class_exists(GeminiFileSearchService::class), 'GeminiFileSearchService has not landed yet.');

it('re-publishes documents, flashes a plain summary, and audits the rebuild', function (): void {
    $admin = User::factory()->admin()->create();

    bindFakeFileSearchService(configured: true, result: ['total' => 5, 'succeeded' => 4, 'failed' => 1]);

    $this->actingAs($admin)
        ->post(route('admin.curriculum-index.rebuild'))
        ->assertRedirectToRoute('admin.settings.edit')
        ->assertSessionHas(
            'glc_status',
            'Re-published 4 of 5 documents to the AI Tutor. 1 failed — open Curriculum to retry.',
        );

    $log = AuditLog::query()->where('action', AuditAction::CurriculumIndexRebuilt)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details)->toMatchArray(['total' => 5, 'succeeded' => 4, 'failed' => 1]);
})->skip(! class_exists(GeminiFileSearchService::class), 'GeminiFileSearchService has not landed yet.');

it('omits the failure hint when every document re-publishes cleanly', function (): void {
    $admin = User::factory()->admin()->create();

    bindFakeFileSearchService(configured: true, result: ['total' => 3, 'succeeded' => 3, 'failed' => 0]);

    $this->actingAs($admin)
        ->post(route('admin.curriculum-index.rebuild'))
        ->assertSessionHas('glc_status', 'Re-published 3 of 3 documents to the AI Tutor.');
})->skip(! class_exists(GeminiFileSearchService::class), 'GeminiFileSearchService has not landed yet.');
