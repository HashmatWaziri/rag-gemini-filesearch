<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

enum CurriculumPermission: string
{
    case View = 'view';
    case Upload = 'upload';
    case Publish = 'publish';
    case Archive = 'archive';
    case Replace = 'replace';
    case Reindex = 'reindex';
    case Delete = 'delete';
    case RestoreVersion = 'restore_version';

    public function spatieName(): string
    {
        return 'curriculum_'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::View => 'View curriculum',
            self::Upload => 'Upload documents',
            self::Publish => 'Publish documents',
            self::Archive => 'Archive documents',
            self::Replace => 'Replace document files',
            self::Reindex => 'Reindex documents',
            self::Delete => 'Permanently delete documents',
            self::RestoreVersion => 'Restore previous versions',
        };
    }
}
