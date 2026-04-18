<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_number',
        'issue_date',
        'amount',
        'pdf_path',
    ];

    // Receipt belongs to a payment
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
