<?php

declare(strict_types=1);

namespace App\Enums;

enum GHLAppointmentStatus: int
{
    case CONFIRMED = 4;
    case CANCELLED_A = -1;
    case CANCELLED_B = 1;
    case CANCELLED_C = 7;
    case NOSHOW = -2;

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'confirmed',
            self::CANCELLED_A, self::CANCELLED_B, self::CANCELLED_C => 'cancelled',
            self::NOSHOW => 'noshow',
        };
    }
}
