<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\AuditAction;

final class AuditActionLabels
{
    public static function for(AuditAction $action): string
    {
        return match ($action) {
            AuditAction::UserCreated => 'Added a user',
            AuditAction::UserUpdated => 'Updated a user',
            AuditAction::UserDeleted => 'Deleted a user',
            AuditAction::UserAnonymized => "Removed a student's personal details",
            AuditAction::UsersBulkImported => 'Imported users from a file',
            AuditAction::ConsentConfirmed => 'Confirmed guardian consent',
            AuditAction::ConsentRevoked => 'Removed guardian consent',
            AuditAction::AccessCodeCreated => 'Created an access code',
            AuditAction::AccessCodeRevoked => 'Cancelled an access code',
            AuditAction::AttemptTerminated => 'Ended a placement test attempt',
            AuditAction::ScoreOverridden => 'Changed a placement score',
            AuditAction::LevelOverridden => 'Changed a placement level',
            AuditAction::NarrativeApproved => 'Approved a parent summary',
            AuditAction::ReviewApproved => 'Approved a placement review',
            AuditAction::ResultSent => 'Sent a placement test result',
            AuditAction::PlacementContentChanged => 'Changed placement test content',
            AuditAction::CurriculumPublished => 'Published a curriculum document',
            AuditAction::CurriculumArchived => 'Archived a curriculum document',
            AuditAction::CurriculumReplaced => 'Replaced a curriculum document',
            AuditAction::CurriculumDeleted => 'Deleted a curriculum document',
            AuditAction::CurriculumIndexRebuilt => 'Re-published all documents to the AI Tutor',
            AuditAction::StudentAssigned => 'Assigned a student to a course',
            AuditAction::DataExported => 'Downloaded a data export',
            AuditAction::SettingsUpdated => 'Changed settings',
            default => $action->label(),
        };
    }
}
