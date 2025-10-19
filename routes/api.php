<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/device-control', [DeviceController::class, 'index']);
Route::post('/device-control', [DeviceController::class, 'update']);

Route::post('/sensors', [SensorController::class, 'store']);
Route::get('/sensors/latest', [SensorController::class, 'latest']);
Route::get('/sensors/daily-average/{days?}', [SensorController::class, 'dailyAverage']);
