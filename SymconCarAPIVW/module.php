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
            $this->SendDebug('Login', 'Verbindung erfolgreich hergestellt.', 0);
        } else {
            $this->SendDebug('Login', 'Verbindung fehlgeschlagen: ' . print_r($data, true), 0);
        }
    }
}
