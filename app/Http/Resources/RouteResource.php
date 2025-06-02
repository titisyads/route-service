<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'driver_id' => $this->driver_id,
            'vehicle_id' => $this->vehicle_id,
            'start_location' => $this->start_location,
            'end_location' => $this->end_location,
            'distance_km' => $this->distance_km,
            'estimated_time_minutes' => $this->estimated_time_minutes,
            'status' => $this->status,
            'created_at' => $this->created_at ? $this->created_at->addHours(7)->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->addHours(7)->toDateTimeString() : null,
        ];
    }
}
