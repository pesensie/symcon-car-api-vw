<?php

class SymconCarAPIVW extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('ClientId', '');
        $this->RegisterPropertyString('ClientSecret', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function TestConnection()
    {
        $clientId = $this->ReadPropertyString('ClientId');
        $clientSecret = $this->ReadPropertyString('ClientSecret');

        $token = $this->GetHighMobilityToken($clientId, $clientSecret);

        if ($token === false) {
            $this->SendDebug('High Mobility', 'Verbindung fehlgeschlagen.', 0);
            return;
        }

        $this->SendDebug('High Mobility', 'Verbindung erfolgreich, Token erhalten.', 0);
    }

    private function GetHighMobilityToken($clientId, $clientSecret)
    {
        $postFields = json_encode([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials'
        ]);

        $ch = curl_init('https://sandbox.api.high-mobility.com/v1/auth/access_token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
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
