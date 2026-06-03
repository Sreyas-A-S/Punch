<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'employee_pin',
        'employee_name',
        'timestamp',
        'status',
        'verify_mode',
        'device_sn',
    ];
}
