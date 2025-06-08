<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Http\Resources\RouteResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Route Service API",
 *     description="API documentation for Route Service",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 */
class RouteController extends Controller
{
    /**
     * Create a new Guzzle HTTP client for the Vehicle Service
     *
     * @return \GuzzleHttp\Client
     */
    private function getVehicleServiceClient()
    {
        return new \GuzzleHttp\Client(['base_uri' => 'http://localhost:8000']);
    }

    /**
     * Create a new Guzzle HTTP client for the Driver Service
     *
     * @return \GuzzleHttp\Client
     */
    private function getDriverServiceClient()
    {
        return new \GuzzleHttp\Client(['base_uri' => 'http://localhost:3001']);
    }

    /**
     * @OA\Post(
     *     path="/api/routes",
     *     summary="Create a new route",
     *     tags={"Routes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"driver_id", "vehicle_id", "start_location", "end_location", "start_time"},
     *             @OA\Property(property="driver_id", type="integer", example=1),
     *             @OA\Property(property="vehicle_id", type="integer", example=1),
     *             @OA\Property(property="start_location", type="string", example="Jakarta"),
     *             @OA\Property(property="end_location", type="string", example="Bandung"),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2025-06-08T08:00:00Z"),
     *             @OA\Property(property="notes", type="string", example="Express delivery", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Route created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Route created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="driver_id", type="integer", example=1),
     *                 @OA\Property(property="driver", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="license_number", type="string", example="LIC123456"),
     *                     @OA\Property(property="name", type="string", example="Joko Nawar"),
     *                     @OA\Property(property="email", type="string", example="JagoNawar@yopmail.com"),
     *                     @OA\Property(property="status", type="string", example="on_duty")
     *                 ),
     *                 @OA\Property(property="vehicle_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="Car"),
     *                     @OA\Property(property="plate_number", type="string", example="B 1234 ABC"),
     *                     @OA\Property(property="status", type="string", example="InUse")
     *                 ),
     *                 @OA\Property(property="start_location", type="string", example="Jakarta"),
     *                 @OA\Property(property="end_location", type="string", example="Bandung"),
     *                 @OA\Property(property="status", type="string", example="Scheduled"),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2025-06-08T08:00:00Z"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="notes", type="string", example="Express delivery", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Driver or Vehicle not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Driver not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create route",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to create route")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'driver_id' => 'required|integer',
                'vehicle_id' => 'required|integer',
                'start_location' => 'required|string|max:255',
                'end_location' => 'required|string|max:255',
                'start_time' => 'required|date_format:Y-m-d H:i:s',
                'notes' => 'nullable|string',
            ]);
            Log::debug('Validated Data', $validated);

            // Verify driver availability
            $driverClient = $this->getDriverServiceClient();
            Log::debug('Driver Client Base URI', ['base_uri' => 'http://localhost:8001']);
            try {
                $driverResponse = $driverClient->get("http://localhost:8001/api/drivers/{$validated['driver_id']}");
                $driver = json_decode($driverResponse->getBody()->getContents(), true);
                Log::debug('Driver Service Response', ['response' => $driverResponse->getBody()->getContents()]);
                Log::debug('Parsed Driver Data', ['driver' => $driver]);
            } catch (\Exception $e) {
                Log::error('Failed to fetch driver: ' . $e->getMessage());
                return response()->json(['error' => 'Driver not found'], 404);
            }

            if (!isset($driver['data']['id']) || strtolower($driver['data']['status']) !== 'available') {
                Log::debug('Driver validation failed', ['driver' => $driver]);
                return response()->json(['error' => 'Driver not available or not found'], 400);
            }

            // Verify vehicle availability
            // Verify vehicle availability
            $vehicleClient = $this->getVehicleServiceClient();
            Log::debug('Vehicle Client Base URI', ['base_uri' => 'http://localhost:8000']);
            try {
                $vehicleResponse = $vehicleClient->get("http://localhost:8000/api/vehicles/{$validated['vehicle_id']}");
                $vehicle = json_decode($vehicleResponse->getBody()->getContents(), true);
                Log::debug('Vehicle Service Response', ['response' => $vehicleResponse->getBody()->getContents()]);
                Log::debug('Parsed Vehicle Data', ['vehicle' => $vehicle]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getResponse()->getStatusCode() === 404) {
                    Log::error('Vehicle not found: ' . $e->getMessage());
                    return response()->json(['error' => 'Vehicle not found'], 404);
                }
                Log::error('Vehicle service unavailable: ' . $e->getMessage());
                return response()->json(['error' => 'Vehicle service unavailable'], 503);
            } catch (\Exception $e) {
                Log::error('Unexpected error fetching vehicle: ' . $e->getMessage());
                return response()->json(['error' => 'Unexpected error fetching vehicle'], 500);
            }

            if (!isset($vehicle['data']['id'])) {
                Log::debug('Vehicle not found', ['vehicle_id' => $validated['vehicle_id']]);
                return response()->json(['error' => 'Vehicle not found'], 404);
            }
            if (strtolower($vehicle['data']['status']) !== 'available') {
                Log::debug('Vehicle not available', ['vehicle' => $vehicle]);
                return response()->json(['error' => 'Vehicle is not available'], 400);
            }

            // Create route
            $route = Route::create($validated);

            // Update driver and vehicle status
            try {
                $driverClient->put("http://localhost:8001/api/drivers/{$validated['driver_id']}", [
                    'json' => [
                        'license_number' => $driver['data']['license_number'],
                        'name' => $driver['data']['name'],
                        'email' => $driver['data']['email'],
                        'status' => 'on_duty',
                        'assigned_vehicle' => (string) $validated['vehicle_id']
                    ]
                ]);
                Log::info('Driver status updated to "on_duty" for ID: ' . $validated['driver_id']); // Tambahkan log untuk konfirmasi

                $vehicleClient->put("http://localhost:8000/api/vehicles/{$validated['vehicle_id']}", [
                    'json' => [
                        'type' => $vehicle['data']['type'],
                        'plate_number' => $vehicle['data']['plate_number'],
                        'status' => 'InUse'
                    ]
                ]);
                Log::info('Vehicle status updated to "InUse" for ID: ' . $validated['vehicle_id']); // Tambahkan log untuk konfirmasi
            } catch (\Exception $e) {
                // Rollback route creation if update fails
                $route->delete();
                Log::error('Failed to update driver or vehicle status: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to update driver or vehicle status'], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Route created successfully',
                'data' => new RouteResource($route)
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            Log::error('Failed to create route: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create route'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/routes/{id}",
     *     summary="Get a specific route",
     *     tags={"Routes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Route ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="driver_id", type="integer", example=1),
     *                 @OA\Property(property="driver", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="license_number", type="string", example="LIC123456"),
     *                     @OA\Property(property="name", type="string", example="Joko Nawar"),
     *                     @OA\Property(property="email", type="string", example="JagoNawar@yopmail.com"),
     *                     @OA\Property(property="status", type="string", example="on_duty")
     *                 ),
     *                 @OA\Property(property="vehicle_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="Car"),
     *                     @OA\Property(property="plate_number", type="string", example="B 1234 ABC"),
     *                     @OA\Property(property="status", type="string", example="InUse")
     *                 ),
     *                 @OA\Property(property="start_location", type="string", example="Jakarta"),
     *                 @OA\Property(property="end_location", type="string", example="Bandung"),
     *                 @OA\Property(property="status", type="string", example="Scheduled"),
     *                 @OA\Property(property="start_time", type="string", format="date-time"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="notes", type="string", example="Express delivery", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized access")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Route not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch route",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to fetch route")
     *         )
     *     )
     * )
     */
    public function show(Request $request, Route $route): JsonResponse
    {
        try {
            // Assuming middleware handles ADMIN/MANAGER check
            // For DRIVER, check if they're assigned to this route
            $user = $request->user();
            if ($user->role === 'DRIVER' && $route->driver_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => new RouteResource($route)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch route: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch route'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/routes/active",
     *     summary="Get active routes",
     *     tags={"Routes"},
     *     @OA\Response(
     *         response=200,
     *         description="List of active routes",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="driver_id", type="integer", example=1),
     *                     @OA\Property(property="driver", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="license_number", type="string", example="LIC123456"),
     *                         @OA\Property(property="name", type="string", example="Joko Nawar"),
     *                         @OA\Property(property="email", type="string", example="JagoNawar@yopmail.com"),
     *                         @OA\Property(property="status", type="string", example="on_duty")
     *                     ),
     *                     @OA\Property(property="vehicle_id", type="integer", example=1),
     *                     @OA\Property(property="vehicle", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="type", type="string", example="Car"),
     *                         @OA\Property(property="plate_number", type="string", example="B 1234 ABC"),
     *                         @OA\Property(property="status", type="string", example="InUse")
     *                     ),
     *                     @OA\Property(property="start_location", type="string", example="Jakarta"),
     *                     @OA\Property(property="end_location", type="string", example="Bandung"),
     *                     @OA\Property(property="status", type="string", example="InProgress"),
     *                     @OA\Property(property="start_time", type="string", format="date-time"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="notes", type="string", example="Express delivery", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch active routes",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to fetch active routes")
     *         )
     *     )
     * )
     */
    public function active(): JsonResponse
    {
        try {
            $routes = Route::whereIn('status', ['Scheduled', 'InProgress'])->get();
            return response()->json([
                'status' => 'success',
                'data' => RouteResource::collection($routes)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch active routes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch active routes'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/routes/{id}/status",
     *     summary="Update route status",
     *     tags={"Routes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Route ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"Scheduled", "InProgress", "Completed", "Cancelled"},
     *                 example="InProgress"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Route status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Route status updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="driver_id", type="integer", example=1),
     *                 @OA\Property(property="driver", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="license_number", type="string", example="LIC123456"),
     *                     @OA\Property(property="name", type="string", example="Joko Nawar"),
     *                     @OA\Property(property="email", type="string", example="JagoNawar@yopmail.com"),
     *                     @OA\Property(property="status", type="string", example="available")
     *                 ),
     *                 @OA\Property(property="vehicle_id", type="integer", example=1),
     *                 @OA\Property(property="vehicle", type="object", nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="Car"),
     *                     @OA\Property(property="plate_number", type="string", example="B 1234 ABC"),
     *                     @OA\Property(property="status", type="string", example="Available")
     *                 ),
     *                 @OA\Property(property="start_location", type="string", example="Jakarta"),
     *                 @OA\Property(property="end_location", type="string", example="Bandung"),
     *                 @OA\Property(property="status", type="string", example="InProgress"),
     *                 @OA\Property(property="start_time", type="string", format="date-time"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="notes", type="string", example="Express delivery", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Route not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update route status",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to update route status")
     *         )
     *     )
     * )
     */
    public function updateStatus(Request $request, Route $route): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:Scheduled,InProgress,Completed,Cancelled'
            ]);

            // If route is completed or cancelled, update driver and vehicle status
            if (in_array($validated['status'], ['Completed', 'Cancelled'])) {
                $driverClient = $this->getDriverServiceClient();
                $vehicleClient = $this->getVehicleServiceClient();

                // Get current driver data
                try {
                    $driverResponse = $driverClient->get('/api/drivers/' . $route->driver_id);
                    $driver = json_decode($driverResponse->getBody()->getContents(), true);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch driver: ' . $e->getMessage());
                    return response()->json(['error' => 'Failed to fetch driver'], 500);
                }

                // Get current vehicle data
                try {
                    $vehicleResponse = $vehicleClient->get('/api/vehicles/' . $route->vehicle_id);
                    $vehicle = json_decode($vehicleResponse->getBody()->getContents(), true);
                } catch (\Exception $e) {
                    Log::error('Failed to fetch vehicle: ' . $e->getMessage());
                    return response()->json(['error' => 'Failed to fetch vehicle'], 500);
                }

                // Update driver status to available
                try {
                    $driverClient->put('/api/drivers/' . $route->driver_id, [
                        'json' => [
                            'license_number' => $driver['license_number'],
                            'name' => $driver['name'],
                            'email' => $driver['email'],
                            'status' => 'available',
                            'assigned_vehicle' => null
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to update driver status: ' . $e->getMessage());
                    return response()->json(['error' => 'Failed to update driver status'], 500);
                }

                // Update vehicle status to Available
                try {
                    $vehicleClient->put('/api/vehicles/' . $route->vehicle_id, [
                        'json' => [
                            'type' => $vehicle['data']['type'],
                            'plate_number' => $vehicle['data']['plate_number'],
                            'status' => 'Available'
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to update vehicle status: ' . $e->getMessage());
                    return response()->json(['error' => 'Failed to update vehicle status'], 500);
                }

                // Update end_time if completed
                if ($validated['status'] === 'Completed') {
                    $validated['end_time'] = now();
                }
            }

            $route->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Route status updated successfully',
                'data' => new RouteResource($route)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            Log::error('Failed to update route status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update route status'], 500);
        }
    }
}
