<?php

declare(strict_types=1);

namespace App\Enums;

enum ZenotiAppointmentStatus: int
{
    case NO_SHOW = -2;
    case CANCELED_A = -1;
    case BOOKED = 0;
    case CANCELED_B = 1;
    case CHECKIN = 2;
    case CONFIRM = 4;
    case BREAK = 10;
    case NOT_SPECIFIED = 11;
    case AVAILABLE = 20;
    case VOIDED = 21;
    case VOID = -3;
    case REFUND = 8;
    case DELETED = 7;
    case REBOOKED = 1000; // Imaginary status code - no relation to Zenoti
    case PAID = 1001; // Imaginary status code - no relation to Zenoti

    public function label(): string
    {
        return match ($this) {
            ZenotiAppointmentStatus::NO_SHOW => 'no-show appointment',
            ZenotiAppointmentStatus::CANCELED_A, self::CANCELED_B => 'canceled appointment',
            ZenotiAppointmentStatus::BOOKED => 'booked appointment',
            ZenotiAppointmentStatus::CHECKIN => 'checkin',
            ZenotiAppointmentStatus::CONFIRM => 'confirm',
            ZenotiAppointmentStatus::BREAK => 'break',
            ZenotiAppointmentStatus::NOT_SPECIFIED => 'NotSpecified',
            ZenotiAppointmentStatus::AVAILABLE => 'available',
            ZenotiAppointmentStatus::VOIDED => 'voided',
            ZenotiAppointmentStatus::VOID => 'void',
            ZenotiAppointmentStatus::REFUND => 'refund',
            ZenotiAppointmentStatus::DELETED => 'deleted',
            ZenotiAppointmentStatus::REBOOKED => 'rebooked',
            ZenotiAppointmentStatus::PAID => 'paid',
        };
    }
}
