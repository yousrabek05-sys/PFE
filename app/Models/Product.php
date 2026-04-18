<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'quantity',
        'sold',
        'price',
        'description',
        'stock_alert',
    ];
}
