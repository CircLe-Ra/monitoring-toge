<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric',
            'light_level' => 'required|integer',
            'measured_at' => 'nullable|date',
        ]);

        $data['measured_at'] = $data['measured_at'] ?? Carbon::now();

        Sensor::create($data);

        return response()->json(['message' => 'Sensor data saved'], 201);
    }

    // optional: get latest reading
    public function latest()
    {
        $latest = Sensor::orderBy('measured_at','desc')->first();
        return response()->json($latest);
    }

    // optional: get aggregates (per hari)
    public function dailyAverage($days = 7)
    {
        $from = now()->subDays($days);
        $avg = Sensor::where('measured_at', '>=', $from)
            ->selectRaw('DATE(measured_at) as day, AVG(temperature) as avg_temp, AVG(humidity) as avg_hum, AVG(light_level) as avg_light')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
        return response()->json($avg);
    }
}
