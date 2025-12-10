<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DTOs\GHLContactDTO;
use App\Enums\GHLAppointmentStatus;
use App\Enums\ZenotiAppointmentStatus;
use App\Interfaces\EventInterface;
use App\Interfaces\ListenerInterface;
use App\Plugins\Logger;
use App\Services\DatabaseService;
use App\Services\GoHighLevelService;

class AppointmentGroupStatusListener implements ListenerInterface
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
        $dbAppointment = $this->dbService->query("SELECT * FROM {$this->dbService->tablePrefix}_appointments WHERE zenoti_appointment_group_id = :appointment_group_id AND ghl_contact_id IS NOT NULL LIMIT 1", ['appointment_group_id' => $appointmentGroupID])->fetch();

        if (!$dbAppointment) {
            (Logger::getInstance($eventContext->eventType))->error('Appointment is not present in database.');
            http_response_code(200);
            exit;
        }

        $appointmentGroupStatus = $eventPayload->data->appointment_group_status;
        $contactIDGHL = $dbAppointment->ghl_contact_id;
        $appointmentIDGHL = $dbAppointment->ghl_appointment_id;

        $contactResponseGHLJSON = $goHighLevelService->getContact($contactIDGHL);
        $contactResponseGHL = json_decode($contactResponseGHLJSON);
        $ghlContactDTO = GHLContactDTO::fromContactObject($contactResponseGHL);

        $contactUpdateResponseGHL = null;
        $contactPostDataGHL = [];
        $mappedStatusZenoti = null;
        if ($mappedStatusZenoti = ZenotiAppointmentStatus::tryFrom($appointmentGroupStatus)?->label()) {
            $newTags = [];
            $newTags[] = $mappedStatusZenoti;

            $contactPostDataGHL['tags'] = array_values(array_unique(array_merge($ghlContactDTO->tags, $newTags)));

            $contactUpdateResponseGHL = $goHighLevelService->updateContact($contactIDGHL, $contactPostDataGHL);
        }

        $appointmentUpdateResponseGHL = null;
        $appointmentPostDataGHL = [];
        if ($mappedStatusGHL = GHLAppointmentStatus::tryFrom($appointmentGroupStatus)?->label()) {
            $appointmentPostDataGHL['appointmentStatus'] = $mappedStatusGHL;

            $appointmentUpdateResponseGHL = $goHighLevelService->updateAppointment($appointmentIDGHL, $appointmentPostDataGHL);
        }

        $this->dbService->save([
            'event_type' => $eventContext->eventType,
            'zenoti_guest_id' => $dbAppointment->zenoti_guest_id,
            'ghl_contact_id' => $contactIDGHL,
            'zenoti_appointment_id' => $dbAppointment->zenoti_appointment_id,
            'zenoti_appointment_group_id' => $appointmentGroupID,
            'zenoti_appointment_group_status' => $mappedStatusZenoti,
            'ghl_contact_post_data' => json_encode($contactPostDataGHL),
            'ghl_contact_response' => $contactUpdateResponseGHL,
            'ghl_appointment_post_data' => json_encode($appointmentPostDataGHL),
            'ghl_appointment_response' => $appointmentUpdateResponseGHL,
            'ghl_appointment_id' => $appointmentIDGHL,
            'event_payload' => json_encode($eventPayload)
        ], 'appointments');
    }
}
