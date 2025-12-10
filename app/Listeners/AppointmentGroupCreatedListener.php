<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTOs\GHLContactDTO;
use App\DTOs\GuestDTO;
use App\Enums\ZenotiAppointmentStatus;
use App\Interfaces\EventInterface;
use App\Interfaces\ListenerInterface;
use App\Plugins\Logger;
use App\Services\DatabaseService;
use App\Services\GoHighLevelService;
use App\Services\ZenotiService;

class AppointmentGroupCreatedListener implements ListenerInterface
{
    private DatabaseService $dbService;

    public function __construct()
    {
        $this->dbService = DatabaseService::getInstance();
    }

    public function handle(EventInterface $event): void
    {
        $servicesToBeExcluded = ['Follow up', 'Semaglutide Treatments Follow up'];

        $eventContext = $event->getEventContext();
        $eventPayload = $eventContext->eventPayload;

        $goHighLevelService = GoHighLevelService::getInstance();
        $zenotiService = ZenotiService::getInstance();

        if (empty($eventPayload->data->appointments)) {
            (Logger::getInstance($eventContext->eventType))->error('Zenoti appointments are not set in payload.');
            http_response_code(200);
            exit;
        }

        $guestID = $eventPayload->data->guest->id;
        $dbGuest = $this->dbService->query("SELECT * FROM {$this->dbService->tablePrefix}_guests WHERE zenoti_guest_id = :guest_id LIMIT 1", ['guest_id' => $guestID])->fetch();

        if ($dbGuest) {
            $contactIDGHL = $dbGuest->ghl_contact_id;

            $contactResponseGHLJSON = $goHighLevelService->getContact($contactIDGHL);
            $contactResponseGHL = json_decode($contactResponseGHLJSON);
            $ghlContactDTO = GHLContactDTO::fromContactObject($contactResponseGHL);
        } else {
            $guestResponseZenotiJSON = $zenotiService->retrieveGuestDetails($eventContext->accessTokenZenoti, $guestID);
            $guestResponseZenoti = json_decode($guestResponseZenotiJSON);

            if (!isset($guestResponseZenoti->id)) {
                (Logger::getInstance($eventContext->eventType))->error('Zenoti guest is not found.');
                http_response_code(200);
                exit;
            }

            $guestDTO = GuestDTO::fromObject($guestResponseZenoti);
            $guestPhone = isset($guestDTO->phone) ? toGHLFormat($guestDTO->phone) : null;

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

            if (!isset($contactResponseGHL->contacts) || count($contactResponseGHL->contacts) == 0) {
                (Logger::getInstance($eventContext->eventType))->error('GoHighLevel contact error: ' . $contactResponseGHLJSON);
                http_response_code(200);
                exit;
            }

            $ghlContactDTO = GHLContactDTO::fromLookupObject($contactResponseGHL);

            $this->dbService->save([
                'zenoti_guest_id' => $guestDTO->id,
                'first_name' => $guestDTO->firstName,
                'last_name' => $guestDTO->lastName,
                'email' => $guestDTO->email,
                'phone' => $guestPhone,
                'ghl_contact_id' => $ghlContactDTO->id
            ], 'guests');
        }

        $appointmentID = $eventPayload->data->appointments[0]->id;
        $appointmentResponseZenotiJSON = $zenotiService->retrieveAppoinmentDetails($appointmentID);
        $appointmentResponseZenoti = json_decode($appointmentResponseZenotiJSON, true);

        $newTags = [];
        $mappedStatusZenoti = null;
        if (isset($appointmentResponseZenoti[0]['status'])) {
            if ($mappedStatusZenoti = ZenotiAppointmentStatus::tryFrom($appointmentResponseZenoti[0]['status'])?->label()) {
                $newTags[] = $mappedStatusZenoti;
            }
        }

        $appointmentGroupID = $eventPayload->data->appointment_group_id;
        $dbAppointments = $this->dbService->query("SELECT * FROM {$this->dbService->tablePrefix}_appointments WHERE zenoti_guest_id = :guest_id AND zenoti_appointment_group_id != :appointment_group_id", ['guest_id' => $guestID, 'appointment_group_id' => $appointmentGroupID])->fetchAll();

        $appointmentServiceName = $eventPayload->data->appointments[0]->service_name;
        if (count($dbAppointments) > 0 && !in_array($appointmentServiceName, $servicesToBeExcluded)) {
            $newTags[] = ZenotiAppointmentStatus::REBOOKED->label();
        }

        $appointmentTherapistName = $eventPayload->data->appointments[0]->therapist_name;
        $appointmentStartTime = toESTConversion($eventPayload->data->appointments[0]->start_time);

        $contactPostDataGHL = [];
        $contactPostDataGHL['customFields'] = [
            [
                'id' => $goHighLevelService->getTherapistNameCustomField(),
                'value' => $appointmentTherapistName
            ],
            [
                'id' => $goHighLevelService->getAppointmentStartCustomField(),
                'value' => $appointmentStartTime
            ]
        ];
        $contactPostDataGHL['tags'] = array_values(array_unique(array_merge($ghlContactDTO->tags, $newTags)));

        $contactUpdateResponseGHL = $goHighLevelService->updateContact($ghlContactDTO->id, $contactPostDataGHL);

        $appointmentPostDataGHL = [];
        $appointmentPostDataGHL = [
            'contactId' => $ghlContactDTO->id,
            'title' => $appointmentServiceName,
            'startTime' => $appointmentStartTime,
            'address' => $appointmentTherapistName,
            'ignoreFreeSlotValidation' => true,
            'assignedUserId' => $goHighLevelService->getStaffID(),
            'locationId' => $goHighLevelService->getLocationID()
        ];

        $appointmentResponseGHLJSON = $goHighLevelService->createAppointment($appointmentPostDataGHL);
        $appointmentResponseGHL = json_decode($appointmentResponseGHLJSON);

        $appointmentIDGHL = null;
        if (isset($appointmentResponseGHL->id)) {
            $appointmentIDGHL = $appointmentResponseGHL->id;
        }

        $this->dbService->save([
            'event_type' => $eventContext->eventType,
            'zenoti_guest_id' => $guestID,
            'ghl_contact_id' => $ghlContactDTO->id,
            'zenoti_appointment_id' => $appointmentID,
            'zenoti_appointment_group_id' => $appointmentGroupID,
            'zenoti_appointment_status' => $mappedStatusZenoti,
            'ghl_contact_post_data' => json_encode($contactPostDataGHL),
            'ghl_contact_response' => $contactUpdateResponseGHL,
            'ghl_appointment_post_data' => json_encode($appointmentPostDataGHL),
            'ghl_appointment_response' => $appointmentResponseGHLJSON,
            'ghl_appointment_id' => $appointmentIDGHL,
            'event_payload' => json_encode($eventPayload)
        ], 'appointments');
    }
}
