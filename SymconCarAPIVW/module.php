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
            $accessToken = $data['access_token'];
            $this->SendDebug('✅ Login erfolgreich', 'Token: ' . substr($accessToken, 0, 40) . '...', 0);

            // Token im Buffer speichern
            $this->SetBuffer('AccessToken', $accessToken);

            // Direkt Fahrzeugdaten abrufen
            $this->FetchVehicleData($accessToken);
        } else {
            $this->SendDebug('❌ Login fehlgeschlagen', print_r($data, true), 0);
        }
    }

    private function FetchVehicleData(string $accessToken)
    {
        $vin = $this->ReadPropertyString('VIN');
        $brand = strtolower($this->ReadPropertyString('Brand'));
        $baseUrl = $this->GetAPIBaseURL($brand);

        // VIN automatisch erkennen, falls leer
        if ($vin == '') {
            $url = $baseUrl . '/vehicles';
            $result = $this->SendAPIRequest($url, $accessToken);

            if (isset($result['data'][0]['vin'])) {
                $vin = $result['data'][0]['vin'];
                $this->SendDebug('🌟 VIN erkannt', $vin, 0);
            } else {
                $this->SendDebug('❌ Keine Fahrzeuge gefunden', print_r($result, true), 0);
                return;
            }
        }

        // Fahrzeugstatus abrufen
        $statusUrl = $baseUrl . "/vehicles/$vin/status";
        $status = $this->SendAPIRequest($statusUrl, $accessToken);

        if (isset($status['batteryStatus']['stateOfCharge'])) {
            $soc = $status['batteryStatus']['stateOfCharge'];
            $this->SendDebug('🔋 SoC', $soc . '%', 0);
        }

        if (isset($status['chargingStatus']['remainingRange']['value'])) {
            $range = $status['chargingStatus']['remainingRange']['value'];
            $this->SendDebug('🚗 Reichweite', $range . ' km', 0);
        }

        if (isset($status['chargingStatus']['chargingState'])) {
            $chargingState = $status['chargingStatus']['chargingState'];
            $this->SendDebug('🔌 Ladevorgang', $chargingState, 0);
        }
    }

    private function SendAPIRequest(string $url, string $accessToken): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Accept: application/json",
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function GetAPIBaseURL(string $brand): string
    {
        switch ($brand) {
            case 'audi':
                return 'https://mal-1a.prd.eu.dp.audi.com/connect';
            case 'skoda':
                return 'https://mal-1a.prd.eu.dp.skoda-auto.com/connect';
            case 'seat':
            case 'cupra':
                return 'https://mal-1a.prd.eu.dp.seat.com/connect';
            case 'vw':
            default:
                return 'https://mal-1a.prd.eu.dp.vwg/connect';
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
