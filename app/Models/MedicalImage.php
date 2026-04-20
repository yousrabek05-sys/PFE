<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalImage extends Model
{
    protected $fillable = [
        'medical_folder_id',
        'type',
        'path',
        'description',
        'ai_analysis',
    ];

    protected $casts=[
        'ai_analysis' => 'encrypted',
    ];

    // Image belongs to a medical folder
    public function medicalFolder()
    {
        return $this->belongsTo(MedicalFolder::class);
    }
}
