<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Mail\ApplicationRejected;
use App\Models\User;
use App\Models\Activity;
use App\Models\Application;
use App\Models\SavedActivity;
use App\Notifications\ActivityApplicationRejected;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ActivityController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getAllActivities', 'getLatestActivities', 'searchActivities', 'getOneActivity']]);
    }
    public function getAllActivities()
    {
        $now = Carbon::parse('now', 'Europe/Belgrade');

        $activities = Activity::with('activityType', 'location', 'organizer')->where('time', '>=', $now)->orderBy('time', 'asc')->paginate(10);
        return response()->json($activities);
    }

    public function getLatestActivities()
    {

        $activities = Activity::with('activityType', 'location', 'organizer')->orderBy('created_at', 'desc')->limit(6)->get();
        return response()->json($activities);
    }

    public function searchActivities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_type_id' => 'nullable|integer|exists:activity_types,id',
            'location' => 'nullable|string',
            'gender' => 'nullable|string|in:male,female,any',
            'is_active' => 'nullable',
            'page' => 'nullable|integer',
            'from_time' => 'nullable|date_format:H:i',
            'to_time' => 'nullable|date_format:H:i|after_or_equal:from_time',
        ]);

        $now = Carbon::parse('now', 'Europe/Belgrade');

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = Activity::with('activityType', 'location', 'organizer');

        $activity_type = $request->get('activity_type_id');
        $location = $request->get('location');
        $date = $request->get('date');
        $gender = $request->get('gender');
        $is_active = $request->get('is_active');
        $from_time = $request->get('from_time');
        $to_time = $request->get('to_time');
        $sortBy = $request->get('sort_by', 'latest');

        if ($activity_type) {
            $query->where('activity_type_id', $activity_type);
        }
        if ($location) {
            $query->whereHas('location', function ($q) use ($location) {
                $q->where('name', 'like', "%$location%");
            });
        }
        if ($date) {
            $query->whereDate('time', $date);
        }
        if ($from_time) {
            $query->whereTime('time', '>=', $from_time);
        }
        
        if ($to_time) {
            $query->whereTime('time', '<=', $to_time);
        }

        if ($gender) {
            $query->where('gender', $gender);
        }
        if ($is_active) {
            $query->where('is_active', $is_active === 'true' ? 1 : 0);
        }

        if ($sortBy === 'latest') {
            $activities = $query->latest()->paginate(6);
        }
        if ($sortBy === 'upcoming') {
            $activities = $query->where('time', '>=', $now) // Filter for upcoming activities
                ->orderBy('time', 'asc')
                ->paginate(6);
        }

        return response()->json($activities, 200);
    }

    public function getOneActivity($id)
    {
        try {
            $activity = Activity::with(['location', 'organizer', 'activityType'])->findOrFail($id);
            return response()->json($activity);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Activity not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch activity', 'errors' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {

        try {
            $request->validate([
                'name' => 'required|string|min:3|max:50',
                'description' => 'required|min:3|max:1000',
                'time' => 'required|date|after:now',
                'address' => 'required| string| min:3|max:100|',
                'gender' => 'required|string|in:male,female,any',
                'location_id' => 'required|exists:locations,id',
                'activity_type_id' => 'required|exists:activity_types,id',
                'organizer_id' => 'required|exists:users,id',
            ]);

            $request->merge(['organizer_id' => Auth::user()->id]);

            $activity = Activity::create($request->all());

            return response()->json(['message' => 'Activity created successfully', 'activity' => $activity], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create activity', 'errors' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $request->validate([
                'name' => 'required|string|min:3|max:50',
                'description' => 'required|min:3|max:1000',
                'time' => 'required|date|after:now',
                'address' => 'required| string| min:5|max:100|',
                'gender' => 'required|string|in:male,female,any',
                'location_id' => 'required|exists:locations,id',
                'activity_type_id' => 'required|exists:activity_types,id',
                'organizer_id' => 'required|exists:users,id',
            ]);

            $activity = Activity::findOrFail($id);
            if ($activity->organizer_id != $request->organizer_id) {
                return response()->json(['message' => 'You are not authorized to update this activity'], 403);
            }
            $activity->update($request->all());
            return response()->json(['message' => 'Activity updated successfully', 'activity' => $activity], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update activity', 'errors' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $activity = Activity::findOrFail($id);

            if ($activity->organizer_id != Auth::user()->id && Auth::user()->role_id != 1) {
                return response()->json(['message' => 'You are not authorized to delete this activity'], 403);
            }
            $activity->delete();

            return response()->json(['message' => 'Activity deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Activity not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }


    public function activitiesOrganizedBy($userId)
    {

        $activities = Activity::where('organizer_id', $userId)->with('activityType', 'location', 'organizer')->orderBy('time', 'desc')->get();
        return response()->json($activities);
    }

    public function userAppliedActivities($userId)
    {

        if ($userId != Auth::user()->id) {
            return response()->json(['message' => 'You are not authorized to view this activities'], 403);
        }

        $userAppliedActivities = User::find($userId)->appliedActivities()
            ->with('activityType', 'location', 'organizer')
            ->orderBy('time', 'desc')
            ->select('activities.*', 'applications.status')
            ->get();

        return response()->json($userAppliedActivities);
    }

    public function userAppliedActivitiesAccepted($userId)
    {

        if ($userId != Auth::user()->id) {
            return response()->json(['message' => 'You are not authorized to view this activities'], 403);
        }

        $userAppliedActivitiesAccepted = User::find($userId)->appliedActivities()->with('activityType', 'location', 'organizer')->where('status', 'accepted')->orderBy('time', 'desc')->get();

        return response()->json($userAppliedActivitiesAccepted);
    }

    public function userAppliedActivitiesRejected($userId)
    {

        if ($userId != Auth::user()->id) {
            return response()->json(['message' => 'You are not authorized to view this activities'], 403);
        }

        $appliedActivitiesRejected = User::find($userId)->appliedActivities()->with('activityType', 'location', 'organizer')->where('status', 'rejected')->orderBy('time', 'desc')->get();

        return response()->json($appliedActivitiesRejected);
    }

    public function userSavedActivities($userId)
    {
        if ($userId != Auth::user()->id) {
            return response()->json(['message' => 'You are not authorized to view this activities'], 403);
        }

        $savedActivities = User::find($userId)->savedActivities()->with('activityType', 'location', 'organizer')->orderBy('time', 'desc')->get();

        return response()->json($savedActivities);
    }

    public function apply(Request $request, $activityId)
    {

        $user = User::find($request->user_id);
        $activity = Activity::findOrFail($activityId);

        $now = Carbon::parse('now', 'Europe/Belgrade');
        try {

            if ($user->id === $activity->organizer_id) {
                return response()->json(['errors' => 'You cannot apply to an activity you organized'], 403);
            }
            if ($activity->time < $now) {
                return response()->json(['errors' => 'You cannot apply to an activity that has already passed'], 403);
            }

            if ($activity->is_active === 0) {
                return response()->json(['errors' => 'Activity is closed, you cannot apply'], 403);
            }
            if ($activity->gender !== 'any' && $activity->gender !== $user->gender) {
                return response()->json(['errors' => 'You cannot apply to an activity that does not match your gender'], 403);
            }
            if ($activity->applications()->where('user_id', $user->id)->exists()) {
                return response()->json(['errors' => 'You have already applied to this activity'], 403);
            }

            $activity->applications()->attach($user);

            return response()->json(['message' => 'Applied to activity successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Activity not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to apply to activity', 'errors' => $e->getMessage()], 500);
        }
    }

    public function unapply(Request $request, $id)
    {

        $user = User::find($request->user_id);
        try {
            $activity = Activity::findOrFail($id);

            if ($user->id === $activity->organizer_id) {
                return response()->json(['errors' => 'You cannot unapply from an activity you organized'], 403);
            }

            if ($activity->applications()->where('user_id', $user->id)->exists()) {

                if ($activity->applications()->where('user_id', $user->id)->where('status', 'rejected')->exists()) {
                    return response()->json(['errors' => 'You can not unapply from an activity because you are rejected by organizer'], 403);
                }

                $activity->applications()->detach($user);

                return response()->json(['message' => 'Unapplied from activity successfully'], 200);
            }
            return response()->json(['errors' => 'You didn not apply to this activity'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to remove application', 'errors' => $e->getMessage()], 500);
        }
    }


    public function save(Request $request, $id)
    {

        $user = User::find($request->user_id);
        $activity = Activity::findOrFail($id);

        if ($request->user_id != Auth::user()->id) {
            return response()->json(['message' => 'You are not authorized to save activity'], 403);
        }

        if ($user->id === $activity->organizer_id) {
            return response()->json(['errors' => 'You cannot save activity you organized'], 403);
        }
        // if activity is already saved by the user, unsave it
        if ($user->savedActivities()->where('activity_id', $activity->id)->exists()) {
            $user->savedActivities()->detach($activity);
            return response()->json(['message' => 'Activity unsaved successfully'], 200);
        }
        $user->savedActivities()->attach($activity);

        return response()->json(['message' => 'Saved activity successfully'], 200);
    }

    public function rejectApplication(Request $request, $activityId, $userId)
    {
        try {

            $user = User::find($request->user_id);
            $activity = Activity::findOrFail($activityId);


            if ($user->id !== $activity->organizer_id) {
                return response()->json(['errors' => 'Only the organizer can reject applications'], 403);
            }
            if ($user->id != Auth::user()->id) {
                return response()->json(['message' => 'You are not authorized to reject application'], 403);
            }
            if (Application::where('activity_id', $activityId)->where('user_id', $userId)->where('status', 'rejected')->first()) {
                return response()->json(['errors' => 'User is already rejected for this activity'], 403);
            }


            $activity->applications()->updateExistingPivot($userId, ['status' => 'rejected'], 200);

            $user_rejected = User::find($userId);

            $user_rejected->notify(new ActivityApplicationRejected($activity));
            // Mail::to($user_rejected->email)->send(new ApplicationRejected($activity, $user_rejected));

            return response()->json(['message' => 'Application rejected successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => 'Activity or application not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to reject application', 'errors' => $e->getMessage()], 500);
        }
    }

    public function closeActivity(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);


        if ($activity->organizer_id != $request->user_id && $request->user_id != Auth::user()->id) {
            return response()->json(['errors' => 'You are not authorized to close this activity'], 403);
        }

        if ($activity->is_active == 1) {
            $activity->is_active = 0;

            $activity->save();
            return response()->json(['message' => 'Activity closed successfully']);
        }
        return response()->json(['errors' => 'Activity is already closed'], 403);
    }

    public function getUsersByActivityId($id)
    {
        try {

            $users = Activity::findOrFail($id)->applications()->where('status', 'accepted')->get();

            return response()->json(ApplicationResource::collection($users), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => 'Activity not found'], 404);
        }
    }

    public function getRejectedUsersByActivityId($id)
    {
        try {

            $users = Activity::findOrFail($id)->applications()->where('status', 'rejected')->get();

            return response()->json(ApplicationResource::collection($users), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => 'Activity not found'], 404);
        }
    }
}
