<?php

declare(strict_types=1);

namespace App\Services;

class GoHighLevelService
{
    private string $apiVersion, $apiURL, $pit, $invoiceCustomField, $therapistNameCustomField, $appointmentStartCustomField, $locationID, $staffID, $calendarID;
    private static ?self $instance = null;

    private function __construct()
    {
        $this->apiVersion = $_ENV['GHL_API_VERSION'];
        $this->apiURL = $_ENV['GHL_API_URL'];
        $this->pit = $_ENV['GHL_PIT'];
        $this->invoiceCustomField = $_ENV['GHL_INVOICE_CUSTOM_FIELD'];
        $this->therapistNameCustomField = $_ENV['GHL_THERAPIST_CUSTOM_FIELD'];
        $this->appointmentStartCustomField = $_ENV['GHL_APPOINTMENT_START_CUSTOM_FIELD'];
        $this->locationID = $_ENV['GHL_LOCATION_ID'];
        $this->staffID = $_ENV['GHL_STAFF_ID'];
        $this->calendarID = $_ENV['GHL_CALENDAR_ID'];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getInvoiceCustomField(): string
    {
        return $this->invoiceCustomField;
    }

    public function getTherapistNameCustomField(): string
    {
        return $this->therapistNameCustomField;
    }

    public function getAppointmentStartCustomField(): string
    {
        return $this->appointmentStartCustomField;
    }

    public function getLocationID(): string
    {
        return $this->locationID;
    }

    public function getStaffID(): string
    {
        return $this->staffID;
    }

    public function getCalendarID(): string
    {
        return $this->calendarID;
    }

    public function getContacts(array $queryParams = []): string
    {
        $queryParams = $queryParams + [
            'locationId' => $this->locationID
        ];

        $query = http_build_query($queryParams);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/contacts/?{$query}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function createContact(array $postData): string
    {
        $postData = $postData + [
            'locationId' => $this->locationID
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/contacts/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                'Content-Type: application/json',
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function searchContacts(array $searchCriteria): string
    {
        $searchCriteria = $searchCriteria + [
            'page' => 1,
            'pageLimit' => 200,
            'locationId' => $this->locationID
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/contacts/search",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($searchCriteria),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                'Content-Type: application/json',
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function getContact(string $contactID): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/contacts/{$contactID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function updateContact(string $contactID, array $postData): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/contacts/{$contactID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                'Content-Type: application/json',
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function deleteContact(string $contactID): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/contacts/{$contactID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function deleteAppointment(string $eventID): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/calendars/events/{$eventID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function updateAppointment(string $eventID, array $postData): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/calendars/events/appointments/{$eventID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                'Content-Type: application/json',
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function createAppointment(array $postData): string
    {
        $postData = $postData + [
            'locationId' => $this->locationID,
            'calendarId' => $this->calendarID
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/calendars/events/appointments",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Authorization: Bearer {$this->pit}",
                'Content-Type: application/json',
                "Version: {$this->apiVersion}"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
