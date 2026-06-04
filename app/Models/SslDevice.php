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

    /**
     * Determine if the device is currently online based on the heartbeat.
     * Heartbeat is 30s, so we allow 60s (2x) before marking as offline.
     *
     * @return bool
     */
    public function getStatusAttribute($value)
    {
        if (!$value || !$this->updated_at) {
            return false;
        }

        return $this->updated_at->gt(now()->subSeconds(60));
    }
}
