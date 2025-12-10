<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTOs\GHLContactDTO;
use App\Enums\ZenotiAppointmentStatus;
use App\Interfaces\EventInterface;
use App\Interfaces\ListenerInterface;
use App\Plugins\Logger;
use App\Services\DatabaseService;
use App\Services\GoHighLevelService;

class AppointmentGroupDeleteListener implements ListenerInterface
{
    private DatabaseService $dbService;

    public function __construct()
    {
        $this->dbService = DatabaseService::getInstance();
    }

    public function handle(EventInterface $event): void
    {
        $eventContext = $event->getEventContext();
        $eventPayload = $eventContext->eventPayload;

        $goHighLevelService = GoHighLevelService::getInstance();

        $appointmentGroupID = $eventPayload->data->appointment_group_id;
        $appointmentID = $eventPayload->data->appointment_id ?? null;

        if (!$appointmentID) {
            (Logger::getInstance($eventContext->eventType))->error('Appointment ID is missing in payload.');
            http_response_code(200);
            exit;
        }

        $dbAppointment = $this->dbService->query("SELECT * FROM {$this->dbService->tablePrefix}_appointments WHERE zenoti_appointment_group_id = :appointment_group_id AND ghl_contact_id IS NOT NULL LIMIT 1", ['appointment_group_id' => $appointmentGroupID])->fetch();

        if (!$dbAppointment) {
            (Logger::getInstance($eventContext->eventType))->error('Appointment details are not present in database.');
            http_response_code(200);
            exit;
        }

        $contactResponseGHLJSON = $goHighLevelService->getContact($dbAppointment->ghl_contact_id);
        $contactResponseGHL = json_decode($contactResponseGHLJSON);
        $ghlContactDTO = GHLContactDTO::fromContactObject($contactResponseGHL);

        $newTags = [];
        $newTags[] = ZenotiAppointmentStatus::CANCELED_A->label();

        $contactPostDataGHL = [];
        $contactPostDataGHL['tags'] = array_values(array_unique(array_merge($ghlContactDTO->tags, $newTags)));

        $contactUpdateResponseGHLJSON = $goHighLevelService->updateContact($dbAppointment->ghl_contact_id, $contactPostDataGHL);
        $appointmentDeleteResponseGHLJSON = $goHighLevelService->deleteAppointment($dbAppointment->ghl_appointment_id);

        $this->dbService->save([
            'event_type' => $eventContext->eventType,
            'zenoti_guest_id' => $dbAppointment->zenoti_guest_id,
            'ghl_contact_id' => $dbAppointment->ghl_contact_id,
            'zenoti_appointment_id' => $appointmentID,
            'zenoti_appointment_group_id' => $appointmentGroupID,
            'zenoti_appointment_status' => ZenotiAppointmentStatus::CANCELED_A->label(),
            'ghl_contact_post_data' => json_encode($contactPostDataGHL),
            'ghl_contact_response' => $contactUpdateResponseGHLJSON,
            'ghl_appointment_response' => $appointmentDeleteResponseGHLJSON,
            'ghl_appointment_id' => $dbAppointment->ghl_appointment_id,
            'event_payload' => json_encode($eventPayload)
        ], 'appointments');
    }
}
