<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTOs\GuestDTO;
use App\Interfaces\EventInterface;
use App\Interfaces\ListenerInterface;
use App\Plugins\Logger;
use App\Services\DatabaseService;

class UpdateOrCreateGuestListener implements ListenerInterface
{
    private DatabaseService $dbService;

    public function __construct()
    {
        $this->dbService = DatabaseService::getInstance();
    }

    public function handle(EventInterface $event): void
    {
        $eventContext = $event->getEventContext();

        if (!isset($eventContext->eventPayload->data)) {
            (Logger::getInstance($eventContext->eventType))->error('Zenoti guest is not set in payload.');
            http_response_code(200);
            exit;
        }

        $guestDTO = GuestDTO::fromObject($eventContext->eventPayload->data);
        $eventContext->guestDTO = $guestDTO;

        $guestPhone = isset($guestDTO->phone) ? toGHLFormat($guestDTO->phone) : null;

        $dbGuest = $this->dbService->query("SELECT * FROM {$this->dbService->tablePrefix}_guests WHERE zenoti_guest_id = :guest_id LIMIT 1", ['guest_id' => $guestDTO->id])->fetch();

        if (!$dbGuest) {
            $this->dbService->save([
                'zenoti_guest_id' => $guestDTO->id,
                'first_name' => $guestDTO->firstName,
                'last_name' => $guestDTO->lastName,
                'email' => $guestDTO->email,
                'phone' => $guestPhone,
                'payload_source' => 'Zenoti webhook',
                'payload' => json_encode($eventContext->eventPayload)
            ], 'guests');
        } else {
            $this->dbService->update([
                'first_name' => $guestDTO->firstName,
                'last_name' => $guestDTO->lastName,
                'email' => $guestDTO->email,
                'phone' => $guestPhone,
                'payload_source' => 'Zenoti webhook',
                'payload' => json_encode($eventContext->eventPayload)
            ], 'guests', ['zenoti_guest_id' => $guestDTO->id]);
        }
    }
}
