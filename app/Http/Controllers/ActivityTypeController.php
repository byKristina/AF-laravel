<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class ActivityTypeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getAllActivityTypes', 'getOneActivityType']]);
    }
    public function getAllActivityTypes()
    {
        $activityTypes = ActivityType::all();

        return response()->json($activityTypes, 200);
    }

    public function getOneActivityType($id)
    {
        $activityType = ActivityType::find($id);

        if (!$activityType) {
            return response()->json(['message' => 'Activity type not found'], 404);
        }

        return response()->json($activityType, 200);
    }

    public function store(Request $request)
    {
        if (Auth::user()->role->name != 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admin can add new activity type.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:30|unique:activity_types,name',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        try {
            $activityType = new ActivityType();
            $activityType->name = $request->name;

            $imageFile = $request->file("image");
            $imageName = $imageFile->getClientOriginalName();
            $imageName = time() . "_" . $imageName;
            $imageFile->move(public_path("images"), $imageName);
            $activityType->image = $imageName;
            $activityType->save();
            return response()->json($activityType, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role->name != 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admin can update activity type.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|min:3|max:30|unique:activity_types,name,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        $activityType = ActivityType::findOrFail($id);
        try {
            
            if ($request->has('name')) {
                $activityType->name = $request->name;
            }

            if ($request->hasFile('image')) {
                // delete old image
                $imagePath = public_path('images/' . $activityType->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }

                $imageFile = $request->file("image");
                $imageName = $imageFile->getClientOriginalName();
                $imageName = time() . "_" . $imageName;
                $imageFile->move(public_path("images"), $imageName);
                $activityType->image = $imageName;
            }

            $activityType->save();
            return response()->json($activityType, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Activity type not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {

        if (Auth::user()->role->name != 'admin') {
            return response()->json(['error' => 'Unauthorized. Only admin can delete activity type.'], 401);
        }
        try {
            $activityType = ActivityType::findOrFail($id);

            //   Delete the image file from storage
            $imagePath = public_path('images/' . $activityType->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            $activityType->delete();
            return response()->json(['message' => 'Activity type deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Activity type not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
