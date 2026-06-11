<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\AuditLog;
use App\Models\Glc\PlacementItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

function silentWavBytes(): string
{
    $data = str_repeat(chr(0x80), 4000);

    return 'RIFF'.pack('V', 36 + mb_strlen($data)).'WAVE'
        .'fmt '.pack('V', 16).pack('v', 1).pack('v', 1)
        .pack('V', 8000).pack('V', 8000).pack('v', 1).pack('v', 8)
        .'data'.pack('V', mb_strlen($data)).$data;
}

it('blocks teachers from placement content management', function (): void {
    actingAs(User::factory()->teacher()->create())
        ->get(route('staff.content.index'))
        ->assertForbidden();

    actingAs(User::factory()->teacher()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'reading',
            'type' => 'passage',
            'title' => 'Blocked',
            'body' => 'Blocked',
        ])
        ->assertForbidden();
});

it('blocks students from placement content management', function (): void {
    actingAs(User::factory()->student()->create())
        ->get(route('staff.content.index'))
        ->assertForbidden();
});

it('redirects guests from placement content management to login', function (): void {
    $this->get(route('staff.content.index'))->assertRedirect(route('login'));
});

it('renders the content page for supervisors with all five sections', function (): void {
    PlacementItem::factory()->passage()->create(['position' => 1]);
    PlacementItem::factory()->writingPrompt()->create();

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.content.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('glc/staff/content-index')
            ->has('sections.reading', 1)
            ->has('sections.writing', 1)
            ->has('sections.grammar_vocabulary')
            ->has('sections.listening')
            ->has('sections.speaking')
        );
});

it('creates a reading passage with audit trail', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->post(route('staff.content.items.store'), [
            'section' => 'reading',
            'type' => 'passage',
            'title' => 'The Night Market',
            'body' => 'Every Friday evening the streets fill with stalls.',
        ])
        ->assertRedirect();

    $item = PlacementItem::query()->where('title', 'The Night Market')->firstOrFail();

    expect($item->section)->toBe(PlacementSection::Reading)
        ->and($item->type)->toBe(PlacementItemType::Passage)
        ->and($item->is_active)->toBeTrue();

    $log = AuditLog::query()->where('action', AuditAction::PlacementContentChanged)->firstOrFail();
    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['event'])->toBe('created');
});

it('creates a child MCQ under a passage', function (): void {
    $passage = PlacementItem::factory()->passage()->create();

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'reading',
            'type' => 'question',
            'parent_id' => $passage->id,
            'body' => 'What happens every Friday?',
            'options' => ['A market', 'A parade', 'A concert', 'A race'],
            'correct_option' => 0,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($passage->children()->count())->toBe(1)
        ->and($passage->children()->first()->correct_option)->toBe(0);
});

it('rejects questions without four options or a correct option', function (): void {
    actingAs(User::factory()->admin()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'grammar_vocabulary',
            'type' => 'question',
            'body' => 'Incomplete question',
            'options' => ['One', 'Two'],
            'correct_option' => 0,
        ])
        ->assertSessionHasErrors('options');
});

it('rejects reading questions without a parent passage', function (): void {
    actingAs(User::factory()->admin()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'reading',
            'type' => 'question',
            'body' => 'Orphan question',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_option' => 1,
        ])
        ->assertSessionHasErrors('parent_id');
});

it('updates and deletes items with audit rows', function (): void {
    $admin = User::factory()->admin()->create();
    $question = PlacementItem::factory()->create([
        'section' => PlacementSection::GrammarVocabulary,
        'parent_id' => null,
    ]);

    actingAs($admin)
        ->put(route('staff.content.items.update', $question), [
            'body' => 'Updated body ____.',
            'options' => ['go', 'goes', 'going', 'gone'],
            'correct_option' => 2,
        ])
        ->assertRedirect();

    expect($question->refresh()->correct_option)->toBe(2)
        ->and($question->body)->toBe('Updated body ____.');

    actingAs($admin)
        ->delete(route('staff.content.items.destroy', $question))
        ->assertRedirect();

    expect(PlacementItem::query()->find($question->id))->toBeNull();

    $events = AuditLog::query()
        ->where('action', AuditAction::PlacementContentChanged)
        ->pluck('details')
        ->pluck('event');

    expect($events)->toContain('updated')->toContain('deleted');
});

it('uploads a WAV listening clip to glc placement audio storage', function (): void {
    Storage::fake('local');

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'listening',
            'type' => 'audio_clip',
            'title' => 'Clip one',
            'audio' => UploadedFile::fake()->createWithContent('clip.wav', silentWavBytes()),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $clip = PlacementItem::query()->where('type', PlacementItemType::AudioClip)->firstOrFail();

    expect($clip->media_path)->toStartWith('glc/placement/audio/');
    Storage::disk('local')->assertExists($clip->media_path);
});

it('rejects non-audio uploads for listening clips', function (): void {
    Storage::fake('local');

    actingAs(User::factory()->admin()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'listening',
            'type' => 'audio_clip',
            'title' => 'Bad clip',
            'audio' => UploadedFile::fake()->createWithContent('notes.txt', 'plain text'),
        ])
        ->assertSessionHasErrors('audio');
});

it('blocks a second active writing prompt', function (): void {
    PlacementItem::factory()->writingPrompt()->create();

    actingAs(User::factory()->admin()->create())
        ->post(route('staff.content.items.store'), [
            'section' => 'writing',
            'type' => 'prompt',
            'body' => 'Another prompt',
            'settings' => ['min_words' => 150, 'max_words' => 250],
        ])
        ->assertSessionHasErrors('body');
});

it('serves listening clip audio to staff including teachers', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('glc/placement/audio/clip.wav', silentWavBytes());

    $clip = PlacementItem::factory()->audioClip()->create([
        'media_path' => 'glc/placement/audio/clip.wav',
    ]);

    actingAs(User::factory()->teacher()->create())
        ->get(route('staff.items.audio', $clip))
        ->assertOk();
});

it('previews extracted PDF text before any content is created', function (): void {
    $pdfBytes = Pdf::loadHTML('<p>The night market opens every Friday evening near the river.</p>')->output();

    $response = actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.content.pdf.preview'), [
            'pdf' => UploadedFile::fake()->createWithContent('passage.pdf', $pdfBytes),
        ]);

    $response->assertOk();
    expect($response->json('text'))->toContain('night market');

    expect(PlacementItem::query()->count())->toBe(0);
});

it('rejects non-PDF files for the PDF preview', function (): void {
    actingAs(User::factory()->admin()->create())
        ->post(route('staff.content.pdf.preview'), [
            'pdf' => UploadedFile::fake()->createWithContent('notes.txt', 'hello'),
        ])
        ->assertSessionHasErrors('pdf');
});

it('keeps the active fixed form in fixed positions for the content page', function (): void {
    PlacementItem::factory()->passage()->create(['position' => 2, 'title' => 'Second']);
    PlacementItem::factory()->passage()->create(['position' => 1, 'title' => 'First']);
    PlacementItem::factory()->passage()->create(['position' => 3, 'title' => 'Inactive', 'is_active' => false]);

    actingAs(User::factory()->admin()->create())
        ->get(route('staff.content.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sections.reading', 2)
            ->where('sections.reading.0.title', 'First')
            ->where('sections.reading.1.title', 'Second')
        );
});
