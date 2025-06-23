<?php

class SymconCarAPIVW extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function TestConnection()
    {
        $username = $this->ReadPropertyString('Username');
        $password = $this->ReadPropertyString('Password');

        $token = $this->LoginAndGetToken($username, $password);

        if ($token === false) {
            $this->SendDebug('Login', 'Verbindung fehlgeschlagen.', 0);
            return;
        }

        $this->SendDebug('Login', 'Verbindung erfolgreich, Token erhalten.', 0);

        $vehicles = $this->GetVehicleList($token);

        if (is_array($vehicles)) {
            $this->SendDebug('Fahrzeugliste', print_r($vehicles, true), 0);
        } else {
            $this->SendDebug('Fahrzeugliste', 'Keine Daten erhalten oder ungültige Antwort.', 0);
        }
    }

    private function LoginAndGetToken($username, $password)
    {
        $postFields = http_build_query([
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => 'a24e9f36-1160-4b9f-9d34-52d54ffb82ea',
            'scope' => 'openid profile mbb dealers cars vin'
        ]);

        $ch = curl_init('https://identity.vwgroup.io/oidc/v1/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            return $data['access_token'];
        }

        return false;
    }

    private function GetVehicleList($accessToken)
    {
        $url = 'https://mal-1a.prd.eu.dp.vwg/connect/vehicles';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
