<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCommand extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_commands';

    protected $fillable = [
        'device_sn',
        'command',
        'status',
        'response_payload',
    ];
}
