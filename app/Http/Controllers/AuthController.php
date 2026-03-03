<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => 'student',
        ]);

        $user->tokens()->delete();
        $token = $user->createToken('myapptoken')->plainTextToken;

        return response(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $fields['email'])->first();

        // If password wrong then save activity log
        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            ActivityLog::create([
                'action' => 'Failed Login Attempt',
                'ip_address' => $request->ip(),
                'status' => 'Failed',
                'user_name' => $fields['email']
            ]);
            return response(['message' => 'Invalid Credentials'], 401);
        }

        // for preventing the user who already loged in google.
        if ($user->provider === 'google') {
            return response([
                'message' => 'Please login using Google',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('myapptoken')->plainTextToken;

        $user->update([
            'last_login_at' => now(),
        ]);

        // Activity log save
        ActivityLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'User Login',
            'ip_address' => $request->ip(),
            'status' => 'Success',
        ]);

        return response([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ], 200);
    }

    // Google Redirect
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // এখানে updateOrCreate ব্যবহার করলে পাসওয়ার্ড বারবার রিসেট হবে না যদি সেটি কন্ডিশনালি হ্যান্ডেল করেন
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'email' => $googleUser->getEmail(),
                    'name' => $googleUser->getName(),
                    'provider_id' => $googleUser->getId(),
                    'provider' => 'google',
                    'password' => Hash::make(Str::random(24)), // শুধু নতুন ইউজারের জন্য
                ]);
            }

            $user->tokens()->delete();
            $token = $user->createToken('myapptoken')->plainTextToken;

            return response()->json(['user' => $user, 'token' => $token]);
        } catch (\Exception $e) {
            return response(['error' => 'Authentication failed'], 401);
        }
    }
    public function logout(Request $request)
    {
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
