<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslDevice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_ssl_devices';

    protected $fillable = ['display_name', 'serial_number', 'status'];
}
