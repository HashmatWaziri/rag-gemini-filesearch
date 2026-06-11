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
            self::Placement => 'Placement Data',
            self::Curriculum => 'Curriculum Metadata',
            self::Students => 'Student Records',
            self::Tutor => 'Tutor Data',
            self::Audit => 'Audit Log',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Placement => 'Placement items, attempts, answers, scores and reviews as JSON and CSV files.',
            self::Curriculum => 'Curriculum documents and course hierarchy as JSON, plus a manifest of original files with storage paths.',
            self::Students => 'Enrolled students with guardian and consent state, plus course assignments, as JSON and CSV files.',
            self::Tutor => 'Tutor conversations, messages, violations and writing submissions as JSON files.',
            self::Audit => 'Full audit log of sensitive staff actions as CSV.',
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
