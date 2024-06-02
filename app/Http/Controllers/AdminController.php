<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index(){

        if (Auth::user()->role_id == 2) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $activities_count = Activity::count();
        $users_count = User::count();
        $activity_types_count = ActivityType::count();
        $locations_count = Location::count();

        return response()->json([
            'activities_count' => $activities_count,
            'users_count' => $users_count,
            'activity_types_count' => $activity_types_count,
            'locations_count' => $locations_count
        ], 200);

    }
}
