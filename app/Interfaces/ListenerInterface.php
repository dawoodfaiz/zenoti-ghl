<?php

declare(strict_types=1);

namespace App\Interfaces;

interface ListenerInterface
{
    public function handle(EventInterface $event): void;
}
