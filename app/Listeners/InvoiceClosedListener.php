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

class InvoiceClosedListener implements ListenerInterface
{
    private DatabaseService $dbService;

    public function __construct()
    {
        $this->dbService = DatabaseService::getInstance();
    }

    public function handle(EventInterface $event): void
    {
        $servicesToBeExcluded = ['Consultation', 'IV Consultation', 'IV Therapy Consultation', 'Neurotoxins Consultation', 'Semaglutide Treatments Consultation', 'Skincare Consult', ' Weight Loss & Wellness Consultation'];

        $eventContext = $event->getEventContext();
        $eventPayload = $eventContext->eventPayload;

        $goHighLevelService = GoHighLevelService::getInstance();
        $zenotiService = ZenotiService::getInstance();

        $ghlInvoiceCustomField = $goHighLevelService->getInvoiceCustomField();

        $invoiceID = $eventPayload->data->invoice->id;
        $invoiceNo = $eventPayload->data->invoice->invoice_number;
        $dbClosedInvoices = $this->dbService->query("SELECT * FROM {$this->dbService->tablePrefix}_closed_invoices WHERE zenoti_invoice_id = :invoice_id AND zenoti_invoice_no = :invoice_no AND event_type = :event_type", ['invoice_id' => $invoiceID, 'invoice_no' => $invoiceNo, 'event_type' => $eventContext->eventType])->rowCount();

        if ($dbClosedInvoices > 0) {
            (Logger::getInstance($eventContext->eventType))->info("Invoice ID: {$invoiceID} with invoice number: {$invoiceNo} is already processed.");
            http_response_code(200);
            exit;
        }

        $guestID = $eventPayload->data->invoice->guest->id;
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
                            'value' => $eventPayload->data->invoice->guest->email,
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

        $previousInvoiceAmount = 0;
        foreach ($ghlContactDTO->customFields as $customField) {
            if ($customField->id === $ghlInvoiceCustomField) {
                $previousInvoiceAmount += $customField->value;
                break;
            }
        }

        $transactionAmount = 0;
        if (!empty($eventPayload->data->invoice->transactions)) {
            foreach ($eventPayload->data->invoice->transactions as $transaction) {
                $transactionAmount += $transaction->total_amount_paid;
            }
        }

        $totalAmountPaid = $previousInvoiceAmount + $transactionAmount;

        $isTagEligible = true;
        if (isset($eventPayload->data->invoice->appointments[0]->id)) {
            $serviceName = $eventPayload->data->invoice->appointments[0]->service_name;

            if (in_array($serviceName, $servicesToBeExcluded)) {
                $isTagEligible = false;
            }
        }

        $newTags = [];
        if ($isTagEligible) {
            $newTags[] = ZenotiAppointmentStatus::PAID->label();
        }

        $contactPostDataGHL = [];
        $contactPostDataGHL['tags'] = array_values(array_unique(array_merge($ghlContactDTO->tags, $newTags)));
        $contactPostDataGHL['customFields'] = [
            [
                'id' => $ghlInvoiceCustomField,
                'value' => $totalAmountPaid
            ]
        ];

        $contactUpdateResponseGHL = $goHighLevelService->updateContact($ghlContactDTO->id, $contactPostDataGHL);

        $this->dbService->save([
            'event_type' => $eventContext->eventType,
            'zenoti_guest_id' => $guestID,
            'zenoti_invoice_id' => $invoiceID,
            'zenoti_invoice_no' => $invoiceNo,
            'total_amount_paid' => $totalAmountPaid,
            'ghl_contact_id' => $ghlContactDTO->id,
            'ghl_contact_post_data' => json_encode($contactPostDataGHL),
            'ghl_contact_response' => $contactUpdateResponseGHL,
            'event_payload' => json_encode($eventPayload)
        ], 'closed_invoices');
    }
}
