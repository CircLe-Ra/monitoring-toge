<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Planting extends Model
{
    use HasFactory;

    protected $fillable = ['plant_name', 'planted_at', 'estimated_days_to_harvest'];

    // pastikan cast ke datetime sehingga selalu Carbon instance
    protected $casts = [
        'planted_at' => 'datetime',
    ];

    public function growthStages()
    {
        return $this->hasMany(GrowthStage::class);
    }

    public function getDaysSincePlantedAttribute()
    {
        if (!$this->planted_at) {
            return 0;
        }
        if ($this->planted_at->isFuture()) {
            return 0;
        }
        $days = Carbon::now()->diffInDays($this->planted_at, true);
        return max(0, $days);
    }

    public function getEstimatedHarvestDateAttribute()
    {
        if (!$this->planted_at || !$this->estimated_days_to_harvest) {
            return null;
        }
        return $this->planted_at->copy()->addDays($this->estimated_days_to_harvest);
    }

    // Accessor: apakah sudah siap panen
    public function getIsReadyToHarvestAttribute()
    {
        $est = $this->estimated_harvest_date;
        if (!$est) return false;
        return Carbon::now()->gte($est);
    }

    public function getPlantedAtFormattedAttribute()
    {
        if (!$this->planted_at) return '-';
        return $this->planted_at->locale('id')->isoFormat('DD MMMM YYYY');
    }

    public function getEstimatedHarvestDateFormattedAttribute()
    {
        $d = $this->estimated_harvest_date;
        return $d ? $d->locale('id')->isoFormat('DD MMMM YYYY') : '-';
    }

    public function getAgeHarvestAttribute()
    {
        if (!$this->planted_at) {
            return null;
        }
        if ($this->planted_at->isFuture()) {
            return "0 hari";
        }
        $diff = $this->planted_at->diff(now());
        return "{$diff->d} hari";
    }
}
