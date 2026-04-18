<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'appointment_date',
        'state',
        'motif',
    ];

    // Appointment belongs to a patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Appointment belongs to a doctor (who is a user)
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
