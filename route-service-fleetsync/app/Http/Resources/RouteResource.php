<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Route",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="driver_id", type="integer", example=1),
 *     @OA\Property(property="vehicle_id", type="integer", example=1),
 *     @OA\Property(property="start_location", type="string", example="Jakarta"),
 *     @OA\Property(property="end_location", type="string", example="Bandung"),
 *     @OA\Property(property="distance_km", type="number", format="float", example=150.5),
 *     @OA\Property(property="estimated_time_minutes", type="integer", example=180),
 *     @OA\Property(property="status", type="string", example="planned"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
