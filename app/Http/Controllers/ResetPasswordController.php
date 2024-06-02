<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User with this email doesn\'t exist'], 404);
        }

        $email = $user->email;

        $token = $this->createToken($email);

        Mail::to($user->email)->send(new ResetPasswordEmail($token, $email));
        return response()->json(['message' => 'Reset email succesfully sent, check your inbox'], 200);
    }


    public function createToken($email)
    {
        $oldToken = DB::table('password_reset_tokens')->where('email', $email)->first();
        if ($oldToken) {
            if ($oldToken->created_at > now()->subMinutes(30)) {
                return $oldToken->token;
            }
            DB::table('password_reset_tokens')->where('email', $email)->delete();
        }

        $token = Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => now()
        ]);
        return $token;
    }


    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        $user = User::where('email', $request->email)->first();
       
        $tokenRow = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$tokenRow) {
            return response()->json(['error' => 'Invalid token'], 404);
        }
        if ($tokenRow->token != $request->token) {
            return response()->json(['error' => 'Invalid token'], 404);
        }
        if ($tokenRow->created_at < now()->subMinutes(30)) {
            return response()->json(['error' => 'Token expired'], 404);
        }

        try {
            $user->password = bcrypt($request->password);
            $user->save();
            DB::table('password_reset_tokens')->where('token', $request->token)->delete();
            return response()->json(['message' => 'Password succesfully changed'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
