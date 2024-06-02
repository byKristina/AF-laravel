<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{

    public function __construct()
    {

        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            return response()->json(['error' => 'Invalid verification link.'], 400);
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return response()->json(['error' => 'Invalid verification link.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully.'], 200);
    }


    public function resend(Request $request)
    {
        $email = $request->input('email');
        $userId = $request->input('id');

        if ($email && $userId) {
            return response()->json(['error' => 'Provide either email or user ID, not both.'], 400);
        }

        if (!$email && !$userId) {
            return response()->json(['error' => 'Provide either email or user ID.'], 400);
        }

        $user = null;

        if ($email) {
            $user = User::where('email', $email)->first();
        } elseif ($userId) {
            $user = User::find($userId);
        }

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        if ($user) {
            if ($user->hasVerifiedEmail()) {
                return response(['message' => "Email already verified"], 200);
            }
            $user->sendEmailVerificationNotification();
            return response(['message' => 'Verification email resent.'], 200);
        } else {

            return response()->json([
                'status' => 'error',
                'message' => 'Email or password are incorrect.'
            ], 403);
        }
    }
}
