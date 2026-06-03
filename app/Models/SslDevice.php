<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslDevice extends Model
{
    protected $fillable = ['display_name', 'serial_number', 'status'];
}
