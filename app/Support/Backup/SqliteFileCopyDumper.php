<?php

declare(strict_types=1);

namespace App\Support\Backup;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\DbDumper\Databases\Sqlite;

final class SqliteFileCopyDumper extends Sqlite
{
    public function dumpToFile(string $dumpFile): void
    {
        $source = $this->getDbName();

        if ($source === ':memory:') {
            $this->dumpMemoryDatabase($dumpFile);

            return;
        }

        if ($source === '' || ! is_file($source)) {
            throw new RuntimeException('SQLite database file was not found for backup.');
        }

        if (! copy($source, $dumpFile)) {
            throw new RuntimeException('Could not copy the SQLite database file for backup.');
        }
    }

    private function dumpMemoryDatabase(string $dumpFile): void
    {
        $path = str_replace("'", "''", str_replace('\\', '/', $dumpFile));

        DB::connection()->getPdo()->exec("VACUUM INTO '{$path}'");

        if (! is_file($dumpFile)) {
            throw new RuntimeException('Could not export the in-memory SQLite database for backup.');
        }
    }
}
