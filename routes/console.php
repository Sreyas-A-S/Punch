<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\SslDevice;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // Mark devices offline if they haven't checked in for 3 minutes
    SslDevice::where('status', true)
        ->where('updated_at', '<', now()->subMinutes(3))
        ->update(['status' => false]);
})->everyMinute();
