<?php

declare(strict_types=1);

namespace App\Services;

class ZenotiService
{
    private string $apiURL, $accountName, $userName, $password, $grantType, $appID, $appSecret, $apiKey, $centerID;
    private static ?self $instance = null;

    private function __construct()
    {
        $this->apiURL = $_ENV['ZENOTI_API_URL'];
        $this->accountName = $_ENV['ZENOTI_ACCOUNT_NAME'];
        $this->userName = $_ENV['ZENOTI_USER_NAME'];
        $this->password = $_ENV['ZENOTI_PASSWORD'];
        $this->grantType = $_ENV['ZENOTI_GRANT_TYPE'];
        $this->appID = $_ENV['ZENOTI_APP_ID'];
        $this->appSecret = $_ENV['ZENOTI_APP_SECRET'];
        $this->apiKey = $_ENV['ZENOTI_API_KEY'];
        $this->centerID = $_ENV['ZENOTI_CENTER_ID'];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getGrantType(): string
    {
        return $this->grantType;
    }

    public function getAppID(): string
    {
        return $this->appID;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function getCenterID(): string
    {
        return $this->centerID;
    }

    public function generateAccessToken(array $accessTokenPostData): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/tokens",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($accessTokenPostData),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function retrieveInvoiceDetails(string $accessToken, string $invoiceID): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/invoices/{$invoiceID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'Content-type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function searchGuest(string $accessToken, array $queryParams): string
    {
        $queryParams = $queryParams + ['center_id' => $this->centerID];

        $query = http_build_query($queryParams);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/guests/search?$query",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'Content-type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function retrieveGuestDetails(string $accessToken, string $guestID): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/guests/{$guestID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'Content-type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function retrieveAppoinmentDetails(string $appointmentID): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$this->apiURL}/appointments/{$appointmentID}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: apikey ' . $this->apiKey,
                'Accept: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
