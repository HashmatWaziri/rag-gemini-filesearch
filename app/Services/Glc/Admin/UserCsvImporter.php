<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

final class UserCsvImporter
{
    private const array REQUIRED_COLUMNS = ['name', 'email', 'password', 'role'];

    private const array OPTIONAL_COLUMNS = ['age', 'guardian_name', 'guardian_email'];

    /**
     * @return array{created: int, errors: list<array{row: int, message: string}>}
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return ['created' => 0, 'errors' => [['row' => 1, 'message' => 'The uploaded file could not be read.']]];
        }

        $header = $this->readHeader($handle);

        if ($header === null) {
            fclose($handle);

            return ['created' => 0, 'errors' => [[
                'row' => 1,
                'message' => 'The header row must contain the columns: '.implode(', ', [...self::REQUIRED_COLUMNS, ...self::OPTIONAL_COLUMNS]).'.',
            ]]];
        }

        $created = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = $this->mapRow($header, $row);
            $validator = Validator::make($data, UserRules::rules($data));

            if ($validator->fails()) {
                $errors[] = ['row' => $line, 'message' => implode(' ', $validator->errors()->all())];

                continue;
            }

            User::query()->create([
                ...$validator->validated(),
                'email_verified_at' => now(),
            ]);

            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * @param  resource  $handle
     * @return list<string>|null
     */
    private function readHeader($handle): ?array
    {
        $raw = fgetcsv($handle);

        if ($raw === false) {
            return null;
        }

        $header = array_map(fn (?string $column): string => mb_strtolower(mb_trim((string) $column)), $raw);

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (! in_array($column, $header, true)) {
                return null;
            }
        }

        return $header;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return array_all($row, fn (?string $value): bool => mb_trim((string) $value) === '');
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $row
     * @return array<string, string|null>
     */
    private function mapRow(array $header, array $row): array
    {
        $data = [];

        foreach ($header as $index => $column) {
            if (! in_array($column, [...self::REQUIRED_COLUMNS, ...self::OPTIONAL_COLUMNS], true)) {
                continue;
            }

            $value = mb_trim((string) ($row[$index] ?? ''));
            $data[$column] = $value === '' ? null : $value;
        }

        return $data;
    }
}
