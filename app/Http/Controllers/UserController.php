<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function getUsers(Request $request)
    {
        $users = User::query()->where('role_id', '!=', 1);


        $results = $users->paginate(3);

        $users = $results->items();
        $totalPages = $results->lastPage();

        return response()->json([
            'users' => UserResource::collection($users),
            'totalPages' => $totalPages,
        ], 200);
    }

    public function searchUsers(Request $request)
    {
        $firstName = $request->first_name;
        $lastName = $request->last_name;
        $users = User::query()->where('role_id', '!=', 1);

        if ($firstName) {
            $users->where('first_name', 'LIKE', "%$firstName%");
        }

        if ($lastName) {
            $users->where('last_name', 'LIKE', "%$lastName%");
        }

        $results = $users->get();

        return response()->json($results, 200);
        
    }


    public function getOneUser($id)
    {
        $user = User::find($id);
        $userResource = UserResource::make($user);
        return response()->json($userResource, 200);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);


        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:3|max:30',
            'last_name' => 'required|string|min:3|max:30',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'date_of_birth' => 'date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update($request->all());
        return response()->json($user, 200);
    }

    public function updatePassword(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|current_password',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

            $user = User::find($id);

           if (Auth::user()->id != $user->id) {
            return response()->json(['message' => 'You are not authorized to update this user'], 403);
           }
        
          $user->password = bcrypt($request->password);
          $user->save(); 
        
          return response()->json(['message' => 'Password updated successfully!'], 200);
    }




    public function updateProfilePicture(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::findOrFail($id);
        try {


            if ($request->hasFile('profile_picture')) {
                // delete old image
                $imagePath = public_path('images/' . $user->profile_picture);

                if ($user->profile_picture != null) {
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
               
                $imageFile = $request->file("profile_picture");
                $imageName = $imageFile->getClientOriginalName();
                $imageName = time() . "_" . $imageName;
                $imageFile->move(public_path("images"), $imageName);
                $user->profile_picture = $imageName;
            }

            $user->save();
            return response()->json($user, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
