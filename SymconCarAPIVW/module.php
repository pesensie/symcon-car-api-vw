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

        $list = $this->SendAPIRequest('/garage', $token);

        if (isset($list['children'])) {
            $this->SendDebug('Fahrzeugliste', print_r($list['children'], true), 0);
        } else {
            $this->SendDebug('Fahrzeugliste', 'Keine Daten erhalten oder ungültige Antwort.', 0);
        }
    }

    private function LoginAndGetToken($username, $password)
    {
        $postFields = json_encode([
            'username' => $username,
            'password' => $password
        ]);

        $ch = curl_init('https://myvwid.apps.emea.vwapps.io/oidc/v1/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            return $data['access_token'];
        }

        return false;
    }

    private function SendAPIRequest($endpoint, $accessToken)
    {
        $url = 'https://myvwid.apps.emea.vwapps.io/api/v2' . $endpoint;

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
