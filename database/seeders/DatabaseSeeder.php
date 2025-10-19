<?php

namespace Database\Seeders;

use App\Models\Config;
use App\Models\Device;
use App\Models\GrowthStage;
use App\Models\Planting;
use App\Models\Plotting;
use App\Models\RelayChannel;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        Role::create(['name' => 'admin']);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => '123'
        ])->assignRole('admin');

        $devices = [
            ['name' => 'relay_1', 'state' => false],
            ['name' => 'relay_2', 'state' => false],
            ['name' => 'servo_cover', 'state' => false],
        ];

        foreach ($devices as $device) {
            Device::updateOrCreate(['name' => $device['name']], $device);
        }

        Config::updateOrCreate(['key' => 'fan_temp_limit'], ['value' => '32']);

        $this->call([
            PlantingSeeder::class,
        ]);
    }
}
