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
            self::TabSwitch => 'Tab Switch',
            self::DualDevice => 'Dual Device Use',
            self::PasteAttempt => 'Paste Attempt',
        };
    }
}
