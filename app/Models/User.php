<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\MedicalFolder;
use App\Models\Notification;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // This user has one patient profile
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    // This user receives many notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // If this user is a doctor, they have many appointments
    public function appointmentsAsDoctor()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    // If this user is a doctor, they manage many medical folders
    public function medicalFolders()
    {
        return $this->hasMany(MedicalFolder::class, 'doctor_id');
    }
}
