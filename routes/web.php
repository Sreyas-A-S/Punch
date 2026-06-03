<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\IclockController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/login', [AdminController::class, 'authenticate'])->name('admin.authenticate');
Route::post('/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/attendance', [AdminController::class, 'attendance'])->name('admin.attendance');
    Route::post('/admin/devices', [AdminController::class, 'storeDevice'])->name('admin.devices.store');
    Route::get('/admin/devices/{id}/edit', [AdminController::class, 'editDevice'])->name('admin.devices.edit');
    Route::put('/admin/devices/{id}', [AdminController::class, 'updateDevice'])->name('admin.devices.update');
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/admin/settings/password', [AdminController::class, 'updatePassword'])->name('admin.settings.password');
});

Route::get('/db/migrate', function () {
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    return "<pre>Exit Code: $exitCode\nOutput:\n" . Artisan::output() . "</pre>";
});

Route::get('/db/backfill-names', function () {
    $users = \App\Models\User::whereNotNull('pin')->get();
    $count = 0;
    foreach ($users as $user) {
        $affected = \App\Models\AttendanceLog::where('employee_pin', $user->pin)
            ->whereNull('employee_name')
            ->update(['employee_name' => $user->name]);
        $count += $affected;
    }
    return "Backfilled $count attendance records with names.";
});

Route::get('/db/status', function () {
    $exitCode = Artisan::call('migrate:status');
    return "<pre>Exit Code: $exitCode\nOutput:\n" . Artisan::output() . "</pre>";
});

Route::get('/logs', function () {
    $logPath = storage_path('logs/laravel.log');
    if (!file_exists($logPath)) return "Log file not found.";
    $logs = file_get_contents($logPath);
    $lines = explode("\n", $logs);
    return "<pre>" . implode("\n", array_slice($lines, -50)) . "</pre>";
});

Route::get('/db/fresh', function () {
    $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);
    return "<pre>Exit Code: $exitCode\nOutput:\n" . Artisan::output() . "</pre>";
});

Route::get('/db/seed', function () {
    $exitCode = Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    return "<pre>Exit Code: $exitCode\nOutput:\n" . Artisan::output() . "</pre>";
});

Route::any('/iclock/cdata', [IclockController::class, 'cdata']);
Route::any('/iclock/cdata.aspx', [IclockController::class, 'cdata']);

Route::any('/iclock/getrequest', [IclockController::class, 'getrequest']);
Route::any('/iclock/getrequest.aspx', [IclockController::class, 'getrequest']);

Route::any('/iclock/devicecmd', [IclockController::class, 'devicecmd']);
Route::any('/iclock/devicecmd.aspx', [IclockController::class, 'devicecmd']);
Route::get('/iclock/trigger', [IclockController::class, 'triggerCommand']);
Route::get('/iclock/fetch-users', [IclockController::class, 'fetchAllUsers']);
Route::get('/optimize', [IclockController::class, 'optimizeApp']);
