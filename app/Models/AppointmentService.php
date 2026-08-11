<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentService extends Model
{
    protected $fillable = ['service_id', 'name_snapshot', 'price_snapshot', 'duration_minutes_snapshot', 'display_order'];

    protected $casts = ['price_snapshot' => 'decimal:2', 'duration_minutes_snapshot' => 'integer'];

    public function service() { return $this->belongsTo(Service::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
}
