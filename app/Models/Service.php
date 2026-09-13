<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $table = 'services';

    const CREATED_AT = 'CreatedAt';
    const UPDATED_AT = 'UpdatedAt';

    protected $fillable = [
        'name',
        'salon_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'CreatedAt' => 'datetime',
        'UpdatedAt' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(
            Appointment::class,
            'appointment_services',
            'service_id',
            'appointment_id'
        )->withPivot('id');
    }
}