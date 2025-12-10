<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class GHLContactDTO
{
    public function __construct(
        public string $id,
        public array $tags,
        public array $customFields,
        public object $contact
    ) {}

    public static function fromLookupObject(object $contactData): self
    {
        return new self(
            id: $contactData->contacts[0]->id ?? null,
            tags: $contactData->contacts[0]->tags ?? [],
            customFields: $contactData->contacts[0]->customFields ?? [],
            contact: $contactData
        );
    }

    public static function fromContactObject(object $contactData): self
    {
        return new self(
            id: $contactData->contact->id ?? null,
            tags: $contactData->contact->tags ?? [],
            customFields: $contactData->contact->customFields ?? [],
            contact: $contactData
        );
    }
}
