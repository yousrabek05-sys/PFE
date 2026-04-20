<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalFolder extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'creation_date',
        'notes',
    ];

    protected $casts=[
        'notes' => 'encrypted',
    ];

    // Folder belongs to a patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Folder belongs to a doctor
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    // Folder has many diagnostics
    public function diagnostics()
    {
        return $this->hasMany(Diagnostic::class);
    }

    // Folder has many treatment plans
    public function treatmentPlans()
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    // Folder has many medical images
    public function medicalImages()
    {
        return $this->hasMany(MedicalImage::class);
    }
}
