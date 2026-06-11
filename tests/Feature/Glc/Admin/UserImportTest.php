<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\UserRole;
use App\Models\Glc\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function importCsv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('users.csv', $content);
}

beforeEach(function (): void {
    $this->withoutVite();
});

it('blocks non-admins from importing users', function (): void {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->post(route('admin.users.import'), ['file' => importCsv('name,email,password,role')])
        ->assertForbidden();
});

it('imports valid rows with verified emails and audits the batch', function (): void {
    $admin = User::factory()->admin()->create();

    $csv = implode("\n", [
        'name,email,password,role,age,guardian_name,guardian_email',
        'Teacher Tan,tan@glc.test,secret-password-123,teacher,30,,',
        'Minor Lim,lim@glc.test,secret-password-123,student,14,Parent Lim,parent.lim@glc.test',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => importCsv($csv)])
        ->assertRedirectToRoute('admin.users.index')
        ->assertSessionHas('glc_import_result', fn (array $result): bool => $result['created'] === 2 && $result['errors'] === []);

    $teacher = User::query()->where('email', 'tan@glc.test')->firstOrFail();
    $minor = User::query()->where('email', 'lim@glc.test')->firstOrFail();

    expect($teacher->role)->toBe(UserRole::Teacher)
        ->and($teacher->email_verified_at)->not->toBeNull()
        ->and($minor->guardian_name)->toBe('Parent Lim')
        ->and($minor->requiresGuardianConsent())->toBeTrue();

    $log = AuditLog::query()->where('action', AuditAction::UsersBulkImported)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details)->toMatchArray(['created' => 2, 'failed' => 0]);
});

it('imports valid rows and reports per-row errors with row numbers', function (): void {
    $admin = User::factory()->admin()->create();
    User::factory()->teacher()->create(['email' => 'taken@glc.test']);

    $csv = implode("\n", [
        'name,email,password,role,age,guardian_name,guardian_email',
        'Valid Teacher,valid@glc.test,secret-password-123,teacher,30,,',
        'Bad Email,not-an-email,secret-password-123,teacher,30,,',
        'Duplicate,taken@glc.test,secret-password-123,teacher,30,,',
        'Bad Role,badrole@glc.test,secret-password-123,wizard,30,,',
        'Minor No Guardian,minor@glc.test,secret-password-123,student,13,,',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => importCsv($csv)])
        ->assertRedirectToRoute('admin.users.index');

    $result = session('glc_import_result');

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toHaveCount(4)
        ->and(array_column($result['errors'], 'row'))->toBe([3, 4, 5, 6]);

    expect(User::query()->where('email', 'valid@glc.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'not-an-email')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'badrole@glc.test')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'minor@glc.test')->exists())->toBeFalse();

    $minorError = collect($result['errors'])->firstWhere('row', 6);

    expect($minorError['message'])
        ->toContain('guardian name')
        ->toContain('guardian email');

    $log = AuditLog::query()->where('action', AuditAction::UsersBulkImported)->firstOrFail();

    expect($log->details)->toMatchArray(['created' => 1, 'failed' => 4]);
});

it('rejects duplicate emails within the same file', function (): void {
    $admin = User::factory()->admin()->create();

    $csv = implode("\n", [
        'name,email,password,role',
        'First,dupe@glc.test,secret-password-123,teacher',
        'Second,dupe@glc.test,secret-password-123,teacher',
    ]);

    $this->actingAs($admin)->post(route('admin.users.import'), ['file' => importCsv($csv)]);

    $result = session('glc_import_result');

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['row'])->toBe(3);
});

it('reports a header error when required columns are missing', function (): void {
    $admin = User::factory()->admin()->create();

    $csv = implode("\n", [
        'name,email',
        'Someone,someone@glc.test',
    ]);

    $this->actingAs($admin)->post(route('admin.users.import'), ['file' => importCsv($csv)]);

    $result = session('glc_import_result');

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0]['row'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('header row');
});

it('requires a csv file', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.import'), [])
        ->assertSessionHasErrors('file');

    $this->actingAs($admin)
        ->post(route('admin.users.import'), ['file' => UploadedFile::fake()->create('users.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('file');
});
