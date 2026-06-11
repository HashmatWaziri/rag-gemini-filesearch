<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum AuditAction: string
{
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserDeleted = 'user_deleted';
    case UserAnonymized = 'user_anonymized';
    case UsersBulkImported = 'users_bulk_imported';
    case ConsentConfirmed = 'consent_confirmed';
    case ConsentRevoked = 'consent_revoked';
    case AccessCodeCreated = 'access_code_created';
    case AccessCodeRevoked = 'access_code_revoked';
    case AttemptTerminated = 'attempt_terminated';
    case ScoreOverridden = 'score_overridden';
    case LevelOverridden = 'level_overridden';
    case NarrativeApproved = 'narrative_approved';
    case ReviewApproved = 'review_approved';
    case ResultSent = 'result_sent';
    case PlacementContentChanged = 'placement_content_changed';
    case CurriculumPublished = 'curriculum_published';
    case CurriculumArchived = 'curriculum_archived';
    case CurriculumReplaced = 'curriculum_replaced';
    case CurriculumDeleted = 'curriculum_deleted';
    case StudentAssigned = 'student_assigned';
    case DataExported = 'data_exported';
    case SettingsUpdated = 'settings_updated';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
