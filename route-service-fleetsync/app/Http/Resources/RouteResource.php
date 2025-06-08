<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use GuzzleHttp\Client;

class RouteResource extends JsonResource
{
    public function toArray($request)
    {
        $driverData = $this->fetchDriverData($this->driver_id);
        $vehicleData = $this->fetchVehicleData($this->vehicle_id);

        return [
            'id' => $this->id,
            'driver_id' => $this->driver_id,
            'driver' => $driverData,
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => $vehicleData,
            'start_location' => $this->start_location,
            'end_location' => $this->end_location,
            'status' => $this->status,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function fetchDriverData($driverId)
    {
        try {
            $client = new Client(['base_uri' => 'http://localhost:3001']);
            $response = $client->get("/api/drivers/{$driverId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return null; // Handle error gracefully
        }
    }

    private function fetchVehicleData($vehicleId)
    {
        try {
            $client = new Client(['base_uri' => 'http://localhost:8000']);
            $response = $client->get("/api/vehicles/{$vehicleId}");
            return json_decode($response->getBody()->getContents(), true)['data'] ?? null;
        } catch (\Exception $e) {
            return null; // Handle error gracefully
        }
    }
}
