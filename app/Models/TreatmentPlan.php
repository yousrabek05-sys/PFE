<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentPlan extends Model
{
    protected $fillable = [
        'medical_folder_id',
        'type',
        'description',
        'duration',
    ];

    protected $casts=[
        'description' => 'encrypted',
    ];

    // Treatment plan belongs to a medical folder
    public function medicalFolder()
    {
        return $this->belongsTo(MedicalFolder::class);
    }
}
