<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTOs\EventContext;

interface EventInterface
{
    public function getEventContext(): EventContext;

    public function listeners(): array;
}
