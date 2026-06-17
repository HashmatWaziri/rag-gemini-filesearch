<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

enum ExportBundle: string
{
    case Placement = 'placement';
    case Curriculum = 'curriculum';
    case Students = 'students';
    case Tutor = 'tutor';
    case Audit = 'audit';

    public function label(): string
    {
        return match ($this) {
            self::Placement => 'Placement test data',
            self::Curriculum => 'Curriculum documents',
            self::Students => 'Student records',
            self::Tutor => 'AI Tutor records',
            self::Audit => 'Activity log',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Placement => 'Every placement test question, attempt, answer, score and staff review. CSV files open in Excel; JSON files open in any text editor.',
            self::Curriculum => 'A list of every curriculum document — its course, level, unit, lesson, status, file details, and Gemini sync status — plus the full course structure. Readable without this system.',
            self::Students => 'Every enrolled student with guardian and consent details, plus their course assignments. CSV files open in Excel.',
            self::Tutor => 'Every AI Tutor conversation and message, flagged messages, and writing submissions, as plain JSON files.',
            self::Audit => 'The complete activity log — who did what, and when — as a CSV file that opens in Excel.',
        };
    }

    /**
     * @return list<string>
     */
    public function contents(): array
    {
        return match ($this) {
            self::Placement => [
                'placement/items.json + .csv',
                'placement/attempts.json + .csv',
                'placement/answers.json + .csv',
                'placement/scores.json + .csv',
                'placement/reviews.json + .csv',
            ],
            self::Curriculum => [
                'curriculum/documents.json',
                'curriculum/hierarchy.json',
                'curriculum/files-manifest.csv',
            ],
            self::Students => [
                'students/students.json + .csv',
                'students/assignments.json + .csv',
            ],
            self::Tutor => [
                'tutor/conversations.json',
                'tutor/messages.json',
                'tutor/violations.json',
                'tutor/writing-submissions.json',
                'tutor/usage-daily.json',
                'tutor/progress-reports.json',
            ],
            self::Audit => [
                'audit/audit-log.csv',
            ],
        };
    }

    public function fileName(): string
    {
        return sprintf('glc-%s-export-%s.zip', $this->value, now()->format('Y-m-d-His'));
    }
}
