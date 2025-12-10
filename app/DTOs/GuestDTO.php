<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class GuestDTO
{
    public function __construct(
        public ?string $id,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phone
    ) {}

    public static function fromObject(object $guestData): self
    {
        return new self(
            id: $guestData->id ?? null,
            firstName: $guestData->personal_info->first_name ?? null,
            lastName: $guestData->personal_info->last_name ?? null,
            email: $guestData->personal_info->email ?? null,
            phone: $guestData->personal_info->mobile_phone->number ?? null
        );
    }
}
