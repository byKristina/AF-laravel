<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::all();
        return response()->json($locations, 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if (Auth::user()->role->name != 'admin') {

            return response()->json(['error' => 'Unauthorized. Only admin can add new location.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:25|unique:locations,name',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {

            $location = Location::create($request->all());
            return response()->json($location, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        if (Auth::user()->role->name != 'admin') {

            return response()->json(['error' => 'Unauthorized. Only admin can update location.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:25|unique:locations,name,' . $location->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $location->update($request->all());
            return response()->json($location, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        if (Auth::user()->role->name != 'admin') {

            return response()->json(['error' => 'Unauthorized. Only admin can delete location.'], 401);
        }
        try {
            $location->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
