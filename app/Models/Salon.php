<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salon extends Model
{
    protected $table = 'salons';

    const CREATED_AT = 'CreatedAt';
    const UPDATED_AT = 'UpdatedAt';

    protected $fillable = [
        'name',
        'location',
        'back_percent',
        'owner_id',
        'IsDeleted',
        'DeletedAt',
    ];

    protected $casts = [
        'back_percent' => 'decimal:1',
        'IsDeleted' => 'boolean',
        'DeletedAt' => 'datetime',
        'CreatedAt' => 'datetime',
        'UpdatedAt' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'salon_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'salon_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'salon_id');
    }
}