<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('users', 'device_users');
        Schema::rename('password_reset_tokens', 'device_password_reset_tokens');
        Schema::rename('sessions', 'device_sessions');
        Schema::rename('cache', 'device_cache');
        Schema::rename('cache_locks', 'device_cache_locks');
        Schema::rename('jobs', 'device_jobs');
        Schema::rename('job_batches', 'device_job_batches');
        Schema::rename('failed_jobs', 'device_failed_jobs');
        Schema::rename('attendance_logs', 'device_attendance_logs');
        Schema::rename('employees', 'device_employees');
        Schema::rename('ssl_devices', 'device_ssl_devices');
        // 'device_commands' already has the prefix.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('device_users', 'users');
        Schema::rename('device_password_reset_tokens', 'password_reset_tokens');
        Schema::rename('device_sessions', 'sessions');
        Schema::rename('device_cache', 'cache');
        Schema::rename('device_cache_locks', 'cache_locks');
        Schema::rename('device_jobs', 'jobs');
        Schema::rename('device_job_batches', 'job_batches');
        Schema::rename('device_failed_jobs', 'failed_jobs');
        Schema::rename('device_attendance_logs', 'attendance_logs');
        Schema::rename('device_employees', 'employees');
        Schema::rename('device_ssl_devices', 'ssl_devices');
    }
};
