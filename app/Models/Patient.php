<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'user_id',
        'birth_date',
        'address',
        'medical_history',
    ];

    // Patient belongs to a user account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Patient has many appointments
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Patient has one medical folder
    public function medicalFolder()
    {
        return $this->hasOne(MedicalFolder::class);
    }

    // Patient has many payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
