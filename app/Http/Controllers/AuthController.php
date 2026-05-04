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
        'email'    => 'required|email|unique:users,email',
        'password' => 'nullable|min:6',
        'phone'    => 'nullable|string',
    ]);

    // If no password provided (doctor/assistant registering), auto-generate one
    $plainPassword = $request->password ?? \Illuminate\Support\Str::random(10);

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($plainPassword),
        'phone'    => $request->phone,
        'role'     => 'patient',
    ]);

        // Create patient profile automatically
        Patient::create([
            'user_id' => $user->id,
        ]);

        // Generate API token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Load the patient profile that was auto-created
        $patient = Patient::where('user_id', $user->id)->first();

        return response()->json([
            'status'            => 'success',
            'message'           => 'Account created successfully',
            'token'             => $token,
            'user'              => $user,
            'patient_id'        => $patient ? $patient->id : null,
            'generated_password'=> $request->password ? null : $plainPassword,
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
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'user'   => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'address'       => $user->address,
                'birth_date'    => $user->birth_date,
                'medical_notes' => $user->medical_notes,
                'role'          => $user->role,
            ],
        ]);
    }
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'birth_date' => 'sometimes|date',
            'medical_notes' => 'sometimes|string',
        ]);

        $user = $request->user();
        $user->update(array_filter([
            'name'          => $request->name,
            'phone'         => $request->phone,
            'address'       => $request->address,
            'birth_date'    => $request->birth_date,
            'medical_notes' => $request->medical_notes,
        ], fn($v) => !is_null($v)));

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated successfully',
            'user'    => $user,
        ]);
    }
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        // Delete the token so they can't use it anymore
        $user->currentAccessToken()->delete();

        // Delete the user (patient profile deletes automatically via DB cascade)
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Account deleted successfully',
        ]);
    }
    public function allPatients(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['doctor', 'assistant'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $patients = Patient::with('user')
            ->get()
            ->map(function($patient) {
                return [
                    'patient_id' => $patient->id,
                    'name'       => $patient->user->name,
                    'email'      => $patient->user->email,
                    'phone'      => $patient->user->phone,
                    'birth_date' => $patient->user->birth_date,
                    'created_at' => $patient->created_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $patients
        ]);
    }

    }
