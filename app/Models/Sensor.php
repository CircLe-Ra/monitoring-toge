<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $fillable = ['temperature','humidity','light_level','measured_at'];
    protected $casts = [
        'measured_at' => 'datetime',
    ];
}
