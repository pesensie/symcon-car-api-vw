<?php

class SymconCarAPIVW extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('VIN', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function TestConnection()
    {
        $token = $this->LoginAndGetToken();
        if ($token === false) {
            $this->SendDebug('Login', 'Fehlgeschlagen', 0);
            return;
        }

        $this->SendDebug('Login', 'Token erhalten', 0);
        $this->FetchVehicleData($token);
    }

    private function LoginAndGetToken()
    {
        $username = $this->ReadPropertyString('Username');
        $password = $this->ReadPropertyString('Password');

        $postFields = json_encode([
            'grant_type' => 'password',
            'username'   => $username,
            'password'   => $password,
            'client_id'  => 'a24e9f36-1160-4b9f-9d34-52d54ffb82ea'
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

    private function FetchVehicleData(string $accessToken)
    {
        $vin = $this->ReadPropertyString('VIN');
        if ($vin === '') {
            $this->SendDebug('VIN', 'Keine VIN definiert', 0);
            return;
        }

        $url = "https://myvwid.apps.emea.vwapps.io/api/v2/vehicles/$vin/status";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['battery']['stateOfCharge']['content'])) {
            $soc = $data['battery']['stateOfCharge']['content'];
            $this->SendDebug('SoC', $soc . '%', 0);
        }

        if (isset($data['battery']['remainingRange']['content'])) {
            $range = $data['battery']['remainingRange']['content'];
            $this->SendDebug('Reichweite', $range . ' km', 0);
        }
    }
}
