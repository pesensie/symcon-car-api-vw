<?php

class CarAPIVW extends IPSModule
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
            $this->SendDebug('WeConnect', 'Verbindung fehlgeschlagen.', 0);
            return;
        }

        $this->SendDebug('WeConnect', 'Verbindung erfolgreich, Token erhalten.', 0);
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
}
