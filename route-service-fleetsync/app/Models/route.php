<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class route extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'start_location',
        'end_location',
        'distance_km',
        'estimated_time_minutes',
        'status',
        'created_at',
        'updated_at',
    ];
}
