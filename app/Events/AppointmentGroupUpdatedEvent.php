<?php

declare(strict_types=1);

namespace App\Events;

use App\DTOs\EventContext;
use App\Interfaces\EventInterface;
use App\Listeners\AppointmentGroupUpdatedListener;
use App\Listeners\GenerateZenotiAccessTokenListener;

class AppointmentGroupUpdatedEvent implements EventInterface
{
    protected EventContext $eventContext;

    public function __construct(object $eventPayload)
    {
        $this->eventContext = new EventContext($eventPayload);
        $this->eventContext->eventType = $eventPayload->event_type;
    }

    public function getEventContext(): EventContext
    {
        return $this->eventContext;
    }

    public function listeners(): array
    {
        return [
            new GenerateZenotiAccessTokenListener(),
            new AppointmentGroupUpdatedListener()
        ];
    }
}
