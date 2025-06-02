<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(title="Route Service API", version="1.0")
 */
class RouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/routes",
     *     summary="List all routes",
     *     tags={"Routes"},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function index()
    {
        try {
            return response()->json(Route::all(), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch routes'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/routes",
     *     summary="Create a new route",
     *     tags={"Routes"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="driver_id", type="integer", example=1),
     *             @OA\Property(property="vehicle_id", type="integer", example=1),
     *             @OA\Property(property="start_location", type="string", example="Jakarta"),
     *             @OA\Property(property="end_location", type="string", example="Bandung"),
     *             @OA\Property(property="distance_km", type="number", format="float", example=150.5),
     *             @OA\Property(property="estimated_time_minutes", type="integer", example=180)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Route created"),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'driver_id' => 'required|integer',
                'vehicle_id' => 'required|integer',
                'start_location' => 'required|string|max:255',
                'end_location' => 'required|string|max:255',
                'distance_km' => 'required|numeric|min:0',
                'estimated_time_minutes' => 'required|integer|min:0',
            ]);

            $route = Route::create($validated);
            return response()->json($route, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create route'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/routes/{id}",
     *     summary="Get route by ID",
     *     tags={"Routes"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
    public function show($id)
    {
        try {
            $route = Route::findOrFail($id);
            return response()->json($route, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Route not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch route'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/routes/{id}",
     *     summary="Update a route",
     *     tags={"Routes"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="start_location", type="string", example="Jakarta"),
     *             @OA\Property(property="end_location", type="string", example="Bandung"),
     *             @OA\Property(property="distance_km", type="number", example=150.5),
     *             @OA\Property(property="estimated_time_minutes", type="integer", example=180),
     *             @OA\Property(property="status", type="string", example="in_progress")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Route updated"),
     *     @OA\Response(response=400, description="Invalid input"),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $route = Route::findOrFail($id);

            $validated = $request->validate([
                'start_location' => 'nullable|string|max:255',
                'end_location' => 'nullable|string|max:255',
                'distance_km' => 'nullable|numeric|min:0',
                'estimated_time_minutes' => 'nullable|integer|min:0',
                'status' => 'nullable|string|in:planned,in_progress,completed',
            ]);

            $route->update($validated);
            return response()->json($route, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Route not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update route'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/routes/{id}",
     *     summary="Delete a route",
     *     tags={"Routes"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Route deleted"),
     *     @OA\Response(response=404, description="Route not found")
     * )
     */
    public function destroy($id)
    {
        try {
            $route = Route::findOrFail($id);
            $route->delete();
            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Route not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete route'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/routes/{id}/status",
     *     summary="Update route status",
     *     tags={"Routes"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="completed")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated"),
     *     @OA\Response(response=400, description="Invalid status")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:planned,in_progress,completed',
            ]);

            $route = Route::findOrFail($id);
            $route->status = $request->status;
            $route->save();

            return response()->json($route, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Route not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update status'], 500);
        }
    }
}
