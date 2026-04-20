<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email', // step 6: emailExists=true → error
            'password' => 'required|min:6',
            'phone'    => 'nullable|string',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => 'patient', // always patient — doctor/assistant created by seeder
        ]);

        // Create patient profile automatically
        Patient::create([
            'user_id' => $user->id,
        ]);

        // Generate API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Account created successfully',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found', // step 7
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Wrong password', 
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token'  => $token,
            'role'   => $user->role, // frontend uses this to redirect to correct dashboard
            'user'   => $user,
        ]);
    }

    public function logout(Request $request)
    {
        // Destroy current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user'   => $request->user(),
        ]);
    }
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string',
        ]);

        $user = $request->user();
        $user->update($request->only('name', 'phone'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ]);
    }
}