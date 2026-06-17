<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Glc\Admin\BackupManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->withoutVite();
    File::ensureDirectoryExists(storage_path('app/backups'));
});

it('runs a database backup and lists it', function (): void {
    Artisan::call('backup:run', ['--only-db' => true]);

    expect(Artisan::output())->not->toBe('');

    $backups = resolve(BackupManager::class)->list();

    expect($backups)->not->toBeEmpty();
});

it('restores a sqlite database from a spatie backup archive', function (): void {
    $database = storage_path('app/testing-restore-'.uniqid('', true).'.sqlite');
    File::put($database, '');

    config(['database.connections.sqlite.database' => $database]);
    DB::purge('sqlite');
    DB::reconnect('sqlite');

    $manager = resolve(BackupManager::class);

    Schema::create('backup_restore_probe', function ($table): void {
        $table->id();
        $table->string('label');
    });

    DB::table('backup_restore_probe')->insert(['label' => 'before-backup']);

    $manager->run(databaseOnly: true);

    $backup = $manager->list()[0]['path'];

    Schema::drop('backup_restore_probe');
    DB::disconnect('sqlite');

    expect(Schema::hasTable('backup_restore_probe'))->toBeFalse();

    $manager->restoreDatabase($backup);

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    expect(Schema::hasTable('backup_restore_probe'))->toBeTrue();
    expect(DB::table('backup_restore_probe')->value('label'))->toBe('before-backup');

    File::delete($database);
});

it('renders the backup management page for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.backups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/backups/index')
            ->has('backups')
            ->has('health'));
});

it('creates a backup from the admin UI', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.backups.store'), ['database_only' => true])
        ->assertRedirect(route('admin.backups.index'));

    expect(resolve(BackupManager::class)->list())->not->toBeEmpty();
});
