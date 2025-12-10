<?php

declare(strict_types=1);

namespace App;

use App\Interfaces\EventInterface;

class EventDispatcher
{
    public function dispatch(EventInterface $event): void
    {
        foreach ($event->listeners() as $listener) {
            $listener->handle($event);
        }
    }
}
