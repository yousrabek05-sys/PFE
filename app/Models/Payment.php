<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'patient_id',
        'assistant_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
    ];

    // Payment belongs to a patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Payment was recorded by an assistant (who is a user)
    public function assistant()
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    // Payment has one receipt
    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}
