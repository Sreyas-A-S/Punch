<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCommand extends Model
{
    protected $fillable = [
        'device_sn',
        'command',
        'status',
        'response_payload',
    ];
}
