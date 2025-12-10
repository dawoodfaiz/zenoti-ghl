<?php

declare(strict_types=1);

namespace App;

use App\Events\AppointmentGroupStatusEvent;
use App\Events\GuestCreatedEvent;
use App\Events\InvoiceClosedEvent;
use App\Events\AppointmentGroupCreatedEvent;
use App\Events\AppointmentGroupUpdatedEvent;
use App\Events\AppointmentGroupDeleteEvent;
use App\Interfaces\EventInterface;

class EventRouter
{
    public function route(object $payload): ?EventInterface
    {
        return match ($payload->event_type ?? null) {
            'Guest.Created' => new GuestCreatedEvent($payload),
            'AppointmentGroup.Status' => new AppointmentGroupStatusEvent($payload),
            'Invoice.Closed' => new InvoiceClosedEvent($payload),
            'AppointmentGroup.Created' => new AppointmentGroupCreatedEvent($payload),
            'AppointmentGroup.Updated' => new AppointmentGroupUpdatedEvent($payload),
            'AppointmentGroup.Delete' => new AppointmentGroupDeleteEvent($payload),
            default => null
        };
    }
}
