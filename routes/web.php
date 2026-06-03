<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IclockController;

Route::get('/', function () {
    return view('welcome');
});

Route::any('/iclock/cdata', [IclockController::class, 'cdata']);
Route::any('/iclock/getrequest', [IclockController::class, 'getrequest']);
Route::any('/iclock/devicecmd', [IclockController::class, 'devicecmd']);
Route::get('/iclock/trigger', [IclockController::class, 'triggerCommand']);
