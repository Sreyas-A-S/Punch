<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_attendance_logs';

    protected $fillable = [
        'employee_pin',
        'employee_name',
        'timestamp',
        'status',
        'verify_mode',
        'device_sn',
    ];
}
