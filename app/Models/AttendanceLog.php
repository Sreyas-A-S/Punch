<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'employee_pin',
        'timestamp',
        'status',
        'device_sn',
    ];
}
