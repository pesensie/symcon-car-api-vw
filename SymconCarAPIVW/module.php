<?php

class SymconCarAPIVW extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('Brand', 'vw');
        $this->RegisterPropertyString('VIN', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }

    public function TestConnection()
    {
        $username = $this->ReadPropertyString('Username');
        $password = $this->ReadPropertyString('Password');
        $brand = strtolower($this->ReadPropertyString('Brand'));

        $clientId = $this->GetClientID($brand);

        $postFields = http_build_query([
            'grant_type' => 'password',
            'username'   => $username,
            'password'   => $password,
            'client_id'  => $clientId,
            'scope'      => 'openid profile mbb dealers cars vin'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://identity.vwgroup.io/oidc/v1/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            $this->SendDebug('❌ Fehler', 'Keine Antwort vom Server', 0);
            return;
        }

        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $this->SendDebug('✅ Login erfolgreich', 'Token: ' . substr($data['access_token'], 0, 40) . '...', 0);
        } else {
            $this->SendDebug('❌ Login fehlgeschlagen', print_r($data, true), 0);
        }
    }

    private function GetClientID(string $brand): string
    {
        switch ($brand) {
            case 'audi':
                return '7f3a14e1-3b42-4b82-94d6-ea9c00f3f9b4';
            case 'skoda':
                return 'cd2e2b3c-bad1-47b7-a0c3-4f1803bffb03';
            case 'seat':
                return 'd9e5ba2e-1d35-4e4c-9a98-7610d4c6e5e1';
            case 'cupra':
                return 'e84f3a06-5e4f-4a2e-9cf5-2bd94e924f25';
            case 'vw':
            default:
                return 'f6e4d3c5-1f72-4a9b-ae4e-cab281c0f2ec';
        }
    }
}
?>