<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'date',
        'is_read',
        'type',
        'channel',
        'status',
        'sent_at',
    ];

    // Notification belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
