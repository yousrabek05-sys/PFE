<?php

namespace App\Http\Controllers;

use App\Models\MedicalFolder;
use App\Models\Diagnostic;
use App\Models\TreatmentPlan;
use App\Models\MedicalImage;
use App\Models\Patient;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{
    // Get all folders
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'doctor') {
            // Doctor sees all folders with patient info
            $folders = MedicalFolder::where('doctor_id', $user->id)
                ->with('patient.user') // load patient + user data
                ->orderBy('created_at', 'desc')
                ->get();

        } elseif ($user->role === 'patient') {
            // Patient sees only their folder
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Patient profile not found'
                ], 404);
            }

            $folders = MedicalFolder::where('patient_id', $patient->id)
                ->with(['diagnostics', 'treatmentPlans', 'medicalImages'])
                ->get();

        } else {
            // Assistant sees all folders
            $folders = MedicalFolder::with('patient.user')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $folders
        ]);
    }

    //Create Medical Folder
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'notes'      => 'nullable|string',
        ]);

        $user = $request->user();

        // Only doctors can create folders
        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only doctors can create medical folders'
            ], 403);
        }

        $exists = MedicalFolder::where('patient_id', $request->patient_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This patient already has a medical folder'
            ], 409);
        }

        $folder = MedicalFolder::create([
            'patient_id'    => $request->patient_id,
            'doctor_id'     => $user->id,
            'creation_date' => now()->toDateString(),
            'notes'         => $request->notes,
        ]);

        // Notify the patient that their folder was created
        $patient = $folder->patient; 
        $this->sendNotification(
            $patient->user_id,
            'Your medical folder has been created by the doctor',
            'medical_folder'
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Medical folder created successfully',
            'data'    => $folder
        ], 201);
    }
//  Consult Medical Folder
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $folder = MedicalFolder::with([
            'diagnostics',    // all diagnostics in this folder
            'treatmentPlans', // all treatment plans
            'medicalImages',  // all images
            'patient.user',   // patient info
            'doctor',         // doctor info
        ])->find($id);

        if (!$folder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No medical folder found' 
            ], 404);
        }

        // Security: patient can only see their OWN folder
        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            if ($folder->patient_id !== $patient->id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $folder 
        ]);
    }

    //  UPDATE — Edit folder notes
    public function update(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        $user = $request->user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only doctors can edit medical folders'
            ], 403);
        }

        $folder = MedicalFolder::find($id);

        if (!$folder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Folder not found'
            ], 404);
        }

        $folder->update(['notes' => $request->notes]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Folder updated successfully',
            'data'    => $folder
        ]);
    }

    // ADD DIAGNOSTIC
    public function addDiagnostic(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
            'date'        => 'required|date',
        ]);

        $user = $request->user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only doctors can add diagnostics'
            ], 403);
        }

        $folder = MedicalFolder::find($id);

        if (!$folder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Folder not found'
            ], 404);
        }

        $diagnostic = Diagnostic::create([
            'medical_folder_id' => $id,
            'description'       => $request->description,
            'date'              => $request->date,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Diagnostic added successfully',
            'data'    => $diagnostic
        ], 201);
    }
    // 💊 ADD TREATMENT PLAN
    public function addTreatment(Request $request, $id)
    {
        $request->validate([
            'type'        => 'required|string',
            'description' => 'required|string',
            'duration'    => 'nullable|string',
        ]);

        $user = $request->user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only doctors can add treatment plans'
            ], 403);
        }

        $folder = MedicalFolder::find($id);

        if (!$folder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Folder not found'
            ], 404);
        }

        $treatment = TreatmentPlan::create([
            'medical_folder_id' => $id,
            'type'              => $request->type,
            'description'       => $request->description,
            'duration'          => $request->duration,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Treatment plan added successfully',
            'data'    => $treatment
        ], 201);
    }

    // 🖼️ ADD IMAGE
    public function addImage(Request $request, $id)
    {
        $request->validate([
            // 'file' must be an image, max 5MB
            'file' => 'required|image|max:5120',
            'type' => 'required|in:xray,intraoral,other',
        ]);

        $user = $request->user();

        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only doctors can add images'
            ], 403);
        }

        $folder = MedicalFolder::find($id);

        if (!$folder) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Folder not found'
            ], 404);
        }

        $path = $request->file('file')->store(
            'medical-images', 
            'public'         
        );

        $image = MedicalImage::create([
            'medical_folder_id' => $id,
            'type'              => $request->type,
            'path'              => $path,
            'description'       => $request->description ?? null,
            'ai_analysis'       => null, 
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Image added successfully',
            'data'    => $image
        ], 201);
    }

    private function sendNotification($userId, $message, $type)
    {
        Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'type'    => $type,
            'date'    => now(),
            'is_read' => false,
            'channel' => 'in_app',
            'status'  => 'sent',
            'sent_at' => now(),
        ]);
    }
}