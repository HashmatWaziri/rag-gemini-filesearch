<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Helpers\Format;
use ZipArchive;

final class BackupManager
{
    private const string DISK = 'backups';

    /**
     * @return list<array{
     *     path: string,
     *     name: string,
     *     size: int,
     *     size_label: string,
     *     date: string,
     *     age: string,
     * }>
     */
    public function list(): array
    {
        $destination = $this->destination();

        return collect($destination->backups())
            ->map(fn (Backup $backup): array => [
                'path' => $backup->path(),
                'name' => basename($backup->path()),
                'size' => $backup->sizeInBytes(),
                'size_label' => Format::humanReadableSize($backup->sizeInBytes()),
                'date' => $backup->date()->toIso8601String(),
                'age' => $backup->date()->diffForHumans(),
            ])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function run(bool $databaseOnly = false): void
    {
        $options = $databaseOnly ? ['--only-db' => true] : [];

        $exitCode = Artisan::call('backup:run', $options);

        if ($exitCode !== 0) {
            throw new RuntimeException(mb_trim(Artisan::output()) ?: 'Backup command failed.');
        }
    }

    public function delete(string $path): void
    {
        $this->assertBackupPath($path);

        if (! $this->disk()->delete($path)) {
            throw new RuntimeException('Could not delete the backup file.');
        }
    }

    public function absolutePath(string $path): string
    {
        $this->assertBackupPath($path);

        return $this->disk()->path($path);
    }

    public function restoreDatabase(string $path): void
    {
        $this->assertBackupPath($path);

        $archivePath = $this->disk()->path($path);
        $tempDir = storage_path('app/backup-restore-'.bin2hex(random_bytes(8)));

        if (! File::makeDirectory($tempDir, 0755, true) && ! File::isDirectory($tempDir)) {
            throw new RuntimeException('Could not create a temporary restore directory.');
        }

        try {
            $zip = new ZipArchive;

            if ($zip->open($archivePath) !== true) {
                throw new RuntimeException('Could not open the backup archive.');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            $dumpPath = $this->findDatabaseDump($tempDir);

            if ($dumpPath === null) {
                throw new RuntimeException('No database dump was found in this backup.');
            }

            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            match ($driver) {
                'sqlite' => $this->restoreSqlite($dumpPath),
                'mysql', 'mariadb' => $this->restoreMysql($dumpPath, $connection),
                default => throw new RuntimeException("Database restore is not supported for the {$driver} driver."),
            };
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    public function healthSummary(): array
    {
        $backups = $this->list();
        $newest = $backups[0] ?? null;

        return [
            'count' => count($backups),
            'newest' => $newest,
            'disk' => self::DISK,
            'scheduled' => 'Daily at 01:30 (database + application files)',
        ];
    }

    private function restoreSqlite(string $dumpPath): void
    {
        $databasePath = config('database.connections.sqlite.database');

        if (! is_string($databasePath) || $databasePath === '') {
            throw new RuntimeException('SQLite database path is not configured.');
        }

        if ($this->isSqliteDatabaseFile($dumpPath)) {
            if (File::exists($databasePath) && ! copy($databasePath, $databasePath.'.before-restore-'.now()->format('Y-m-d-His'))) {
                throw new RuntimeException('Could not create a safety copy of the current database.');
            }

            File::ensureDirectoryExists(dirname($databasePath));

            if (! copy($dumpPath, $databasePath)) {
                throw new RuntimeException('Could not restore the SQLite database file.');
            }

            return;
        }

        if (str_ends_with(mb_strtolower($dumpPath), '.sql')) {
            $this->importSqliteDump($dumpPath, $databasePath);

            return;
        }

        throw new RuntimeException('Unsupported SQLite backup format.');
    }

    private function isSqliteDatabaseFile(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        return is_string($header) && str_starts_with($header, 'SQLite format 3');
    }

    private function importSqliteDump(string $dumpPath, string $databasePath): void
    {
        $backupCopy = $databasePath.'.before-restore-'.now()->format('Y-m-d-His');

        if (File::exists($databasePath) && ! copy($databasePath, $backupCopy)) {
            throw new RuntimeException('Could not create a safety copy of the current database.');
        }

        if (File::exists($databasePath)) {
            File::delete($databasePath);
        }

        File::ensureDirectoryExists(dirname($databasePath));
        touch($databasePath);

        $sql = File::get($dumpPath);

        DB::connection('sqlite')->unprepared($sql);
    }

    private function restoreMysql(string $dumpPath, string $connection): void
    {
        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            throw new RuntimeException('Database connection configuration is missing.');
        }

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        $mysql = $this->resolveMysqlBinary();

        $command = sprintf(
            '%s --host=%s --port=%s --user=%s %s %s < %s',
            escapeshellarg($mysql),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password !== '' ? '--password='.escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($dumpPath),
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('MySQL restore failed: '.implode("\n", $output));
        }
    }

    private function resolveMysqlBinary(): string
    {
        $configured = config('database.connections.mysql.dump.dump_binary_path');

        if (is_string($configured) && $configured !== '') {
            return mb_rtrim($configured, '\\/').DIRECTORY_SEPARATOR.'mysql';
        }

        return PHP_OS_FAMILY === 'Windows' ? 'mysql' : '/usr/bin/mysql';
    }

    private function findDatabaseDump(string $directory): ?string
    {
        $dbDumpsDir = $directory.DIRECTORY_SEPARATOR.'db-dumps';

        if (! File::isDirectory($dbDumpsDir)) {
            return $this->firstDumpFile($directory);
        }

        return $this->firstDumpFile($dbDumpsDir);
    }

    private function firstDumpFile(string $directory): ?string
    {
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            $extension = mb_strtolower($file->getExtension());

            if (in_array($extension, ['sql', 'sqlite', 'db', 'archive'], true)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function assertBackupPath(string $path): void
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            throw new RuntimeException('Invalid backup path.');
        }

        if (! $this->disk()->exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }
    }

    private function destination(): BackupDestination
    {
        return BackupDestination::create(self::DISK, config('backup.backup.name'));
    }

    private function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
