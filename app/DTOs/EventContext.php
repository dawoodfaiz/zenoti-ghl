<?php

declare(strict_types=1);

namespace App\DTOs;

final class EventContext
{
    public ?string $eventType = null;
    public ?string $accessTokenGHL = null;
    public ?string $accessTokenZenoti = null;
    public ?GuestDTO $guestDTO = null;
    public ?GHLContactDTO $ghlContactDTO = null;

    public function __construct(
        public object $eventPayload
    ) {}
}
