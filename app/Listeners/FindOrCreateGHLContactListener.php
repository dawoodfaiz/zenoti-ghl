<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTOs\GHLContactDTO;
use App\Interfaces\EventInterface;
use App\Interfaces\ListenerInterface;
use App\Plugins\Logger;
use App\Services\DatabaseService;
use App\Services\GoHighLevelService;

class FindOrCreateGHLContactListener implements ListenerInterface
{
    private DatabaseService $dbService;

    public function __construct()
    {
        $this->dbService = DatabaseService::getInstance();
    }

    public function handle(EventInterface $event): void
    {
        $eventContext = $event->getEventContext();
        $guestDTO = $eventContext->guestDTO;
        $guestPhone = isset($guestDTO->phone) ? toGHLFormat($guestDTO->phone) : null;

        $goHighLevelService = GoHighLevelService::getInstance();

        $searchCriteria = [];
        $searchCriteria['filters'] = [
            [
                'group' => 'OR',
                'filters' => [
                    [
                        'field' => 'email',
                        'operator' => 'eq',
                        'value' => $guestDTO->email,
                    ],
                    [
                        'field' => 'phone',
                        'operator' => 'eq',
                        'value' => $guestPhone,
                    ]
                ]
            ]
        ];

        $contactResponseGHLJSON = $goHighLevelService->searchContacts($searchCriteria);
        $contactResponseGHL = json_decode($contactResponseGHLJSON);

        $contactPostDataGHL = [];
        $contactSourceGHL = 'lookup contact';
        if (!isset($contactResponseGHL->contacts) || count($contactResponseGHL->contacts) == 0) {
            $contactPostDataGHL = [
                'firstName' => $guestDTO->firstName,
                'lastName' => $guestDTO->lastName,
                'name' => "{$guestDTO->firstName} {$guestDTO->lastName}",
                'email' => $guestDTO->email,
                'phone' => $guestPhone,
            ];

            $contactResponseGHLJSON = $goHighLevelService->createContact($contactPostDataGHL);
            $contactResponseGHL = json_decode($contactResponseGHLJSON);

            if (!isset($contactResponseGHL->contact)) {
                (Logger::getInstance($eventContext->eventType))->error('GoHighLevel contact error: ' . $contactResponseGHLJSON);
                http_response_code(200);
                exit;
            } else {
                $ghlContactDTO = GHLContactDTO::fromContactObject($contactResponseGHL);
                $eventContext->ghlContactDTO = $ghlContactDTO;
                $contactSourceGHL = 'create contact';
            }
        } else {
            $ghlContactDTO = GHLContactDTO::fromLookupObject($contactResponseGHL);
            $eventContext->ghlContactDTO = $ghlContactDTO;
        }

        $this->dbService->update([
            'ghl_contact_id' => $ghlContactDTO->id,
            'ghl_post_data' => json_encode($contactPostDataGHL),
            'ghl_response' => $contactResponseGHLJSON,
            'ghl_contact_source' => $contactSourceGHL,
        ], 'guests', ['zenoti_guest_id' => $guestDTO->id]);
    }
}
