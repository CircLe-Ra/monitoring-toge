<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('goto');
})->name('home');

Route::get('/goto', function () {
    $user = auth()->user();
    $redirects = [
        'admin' => 'admin.sensor',
    ];

    foreach ($redirects as $role => $route) {
        if($user){
            if ($user->hasRole($role)) {
                return redirect()->route($route);
            }
        }else{
            return redirect()->route('login');
        }
    }
    return redirect()->route('settings.profile');
})->name('goto');


Route::middleware(['auth'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Volt::route('planting-tracker', 'admin.planting-tracker')->name('planting-tracker');
        Volt::route('controller', 'admin.device-controller')->name('controller');
        Volt::route('status/sensors', 'admin.sensor')->name('sensor');
        Volt::route('master-data/users', 'admin.master-data.user')->name('master-data.user');
        Volt::route('report', 'admin.report')->name('report');
    });

    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::get('/reports/planting/pdf', [ReportController::class, 'downloadPdf']);
require __DIR__.'/auth.php';
