<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostic extends Model
{
    protected $fillable = [
        'medical_folder_id',
        'date',
        'description',
    ];

    protected $casts = [
        'description' => 'encrypted',
    ];

    // Diagnostic belongs to a medical folder
    public function medicalFolder()
    {
        return $this->belongsTo(MedicalFolder::class);
    }
}
