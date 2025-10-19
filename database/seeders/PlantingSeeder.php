<?php

namespace Database\Seeders;

use App\Models\GrowthStage;
use App\Models\Planting;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlantingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planting = Planting::create([
            'plant_name' => 'Toge Hijau',
            'planted_at' => Carbon::now()->subDays(1), // ditanam 1 hari yang lalu
            'estimated_days_to_harvest' => 3,
        ]);

        $stages = [
            ['stage_name' => 'Perendaman', 'day_start' => 0, 'day_end' => 1],
            ['stage_name' => 'Perkecambahan', 'day_start' => 1, 'day_end' => 2],
            ['stage_name' => 'Pertumbuhan Daun', 'day_start' => 2, 'day_end' => 3],
            ['stage_name' => 'Siap Panen', 'day_start' => 3, 'day_end' => 4],
        ];

        foreach ($stages as $stage) {
            GrowthStage::create(array_merge($stage, ['planting_id' => $planting->id]));
        }
    }
}
