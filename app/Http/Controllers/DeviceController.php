<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\Device;
use App\Models\Schedule;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $controls = Device::pluck('state', 'name');
        $schedules = Schedule::where('active', true)->pluck('time');
        $fanLimit = Config::where('key', 'fan_temp_limit')->value('value') ?? 32;
        $communication = Config::where('key', 'communication')->value('value') ?? '0';
        $watering = (int) (Config::where('key', 'watering')->value('value') ?? 0);

        return response()->json([
            'relay' => [
                $controls['relay_1'] ?? 0,
                $controls['relay_2'] ?? 0
            ],
            'servo_cover' => $controls['servo_cover'] ?? 0,
            'schedules' => collect($schedules)->map(fn($s) => substr($s, 0, 5)),
            'fan_temp_limit' => (int) $fanLimit,
            'communication' => $communication,
            'watering' => $watering
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'relay' => 'required|array|size:2',
            'servo_cover' => 'required|integer',
        ]);

        Device::where('name', 'relay_1')->update(['state' => $data['relay'][0]]);
        Device::where('name', 'relay_2')->update(['state' => $data['relay'][1]]);
        Device::where('name', 'servo_cover')->update(['state' => $data['servo_cover']]);

        return response()->json(['message' => 'Device states updated']);
    }
}
