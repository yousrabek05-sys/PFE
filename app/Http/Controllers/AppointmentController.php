<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Notification;
use Illuminate\Http\Request;

class RDVController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Patient profile not found'
                ], 404);
            }
            
            $appointments = Appointment::where('patient_id', $patient->id)
                ->with('doctor') // load doctor info
                ->orderBy('date', 'desc')
                ->get();

        } elseif ($user->role === 'doctor') {
            $appointments = Appointment::where('doctor_id', $user->id)
                ->with('patient.user') 
                ->orderBy('date', 'desc')
                ->get();

        } else {
            $appointments = Appointment::with(['patient.user', 'doctor'])
                ->orderBy('date', 'desc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $appointments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'  => 'required|date|after:today', // can't book in the past
            'motif' => 'required|string|max:500',
        ]);

        $user = $request->user();

        if ($user->role !== 'patient') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only patients can book appointments'
            ], 403);
        }

        $patient = Patient::where('user_id', $user->id)->first();

        
        $slotTaken = Appointment::where('date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists(); 

            
        if ($slotTaken) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Slot not available' 
            ], 409);
        }

        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id'  => $this->getDoctorId(), 
            'date'       => $request->date,
            'motif'      => $request->motif,
            'status'     => 'pending', // always starts as pending
        ]);

        $this->sendNotification(
            $patient->user_id,
            'Your appointment request has been sent. Status: pending',
            'appointment'
        );
        
        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment request sent!',
            'data'    => $appointment
        ], 201);
    }
    
    public function cancel(Request $request, $id)
    {
        $user    = $request->user();
        $patient = Patient::where('user_id', $user->id)->first();

        
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found'
            ], 404);
        }
        
        if ($appointment->patient_id !== $patient->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        if ($appointment->status === 'pending') {

        
            $appointment->update(['status' => 'cancelled']);

            
            return response()->json([
                'status'  => 'success',
                'message' => 'Appointment cancelled'
            ]);
        }

        
        if ($appointment->status === 'confirmed') {

        
            $appointment->update(['status' => 'cancelled']);

            $this->sendNotification(
                $appointment->doctor_id,
                'Appointment on ' . $appointment->date . ' was cancelled by patient',
                'appointment'
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Appointment cancelled'
            ]);
        }

        
        return response()->json([
            'status'  => 'error',
            'message' => 'This appointment cannot be cancelled'
        ], 400);
    }
    

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:confirmed,refused'
        ]);

        $user = $request->user();

        // Only doctors can accept/refuse
        if ($user->role !== 'doctor') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only doctors can accept or refuse appointments'
            ], 403);
        }

        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found'
            ], 404);
        }

        if ($appointment->status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending appointments can be processed'
            ], 400);
        }

        if ($request->decision === 'confirmed') {
            $appointment->update(['status' => 'confirmed']);

            $this->sendNotification(
                $appointment->patient->user_id,
                'Your appointment is confirmed',
                'appointment'
            );
            
            return response()->json([
                'status'  => 'success',
                'message' => 'Appointment confirmed'
            ]);
        }

        $appointment->update(['status' => 'refused']);

        
        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment refused'
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'date'  => 'sometimes|date|after:today',
            'motif' => 'sometimes|string|max:500',
        ]);

        $user = $request->user();

        // Only assistant can update appointments
        if ($user->role !== 'assistant') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only assistants can update appointments'
            ], 403);
        }

        
        $appointment = Appointment::find($id);

        
        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found' 
            ], 404);
        }

        
        $appointment->update($request->only('date', 'motif'));

        
        $this->sendNotification(
            $appointment->patient->user_id,
            'Your appointment has been rescheduled to ' . $appointment->date,
            'appointment'
        );

        
        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment updated successfully',
            'data'    => $appointment
        ]);
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
    
    private function getDoctorId()
    {
        return \App\Models\User::where('role', 'doctor')
            ->first()
            ->id;
    }
}