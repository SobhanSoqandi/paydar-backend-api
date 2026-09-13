<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentService extends Model
{
    protected $table = 'appointment_services';

    public $timestamps = false;

    protected $fillable = [
        'appointment_id',
        'service_id',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}