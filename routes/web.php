<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\IclockController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/db/migrate', function () {
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    return "<pre>Exit Code: $exitCode\nOutput:\n" . Artisan::output() . "</pre>";
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
Route::any('/iclock/getrequest', [IclockController::class, 'getrequest']);
Route::any('/iclock/devicecmd', [IclockController::class, 'devicecmd']);
Route::get('/iclock/trigger', [IclockController::class, 'triggerCommand']);
Route::get('/optimize', [IclockController::class, 'optimizeApp']);
