<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrowthStage extends Model
{
    use HasFactory;

    protected $fillable = ['planting_id', 'stage_name', 'day_start', 'day_end', 'photo'];

    public function planting()
    {
        return $this->belongsTo(Planting::class);
    }
}
