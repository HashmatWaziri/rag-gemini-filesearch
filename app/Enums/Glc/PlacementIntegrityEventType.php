<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementIntegrityEventType: string
{
    case TabSwitch = 'tab_switch';
    case DualDevice = 'dual_device';
    case PasteAttempt = 'paste_attempt';

    public function label(): string
    {
        return match ($this) {
            self::TabSwitch => 'Left the test page during a section',
            self::DualDevice => 'Same access code opened on two devices',
            self::PasteAttempt => 'Tried to paste text into an answer',
        };
    }
}
