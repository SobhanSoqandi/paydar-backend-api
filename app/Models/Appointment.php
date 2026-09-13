<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $table = 'appointments';

    const CREATED_AT = 'CreatedAt';
    const UPDATED_AT = 'UpdatedAt';

    protected $fillable = [
        'customer_id',
        'start_time',
        'description',
        'salon_id',
        'is_paid',
        'paid_price',
        'IsDeleted',
        'DeletedAt',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'is_paid' => 'boolean',
        'paid_price' => 'decimal:2',
        'IsDeleted' => 'boolean',
        'DeletedAt' => 'datetime',
        'CreatedAt' => 'datetime',
        'UpdatedAt' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('not_deleted', function ($query) {
            $query->where('IsDeleted', false);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'appointment_services',
            'appointment_id',
            'service_id'
        )->withPivot('id');
    }

    public function appointmentServices(): HasMany
    {
        return $this->hasMany(
            AppointmentService::class,
            'appointment_id'
        );
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(
            WalletTransaction::class,
            'appointment_id'
        );
    }

    public function discountUsage(): HasOne
    {
        return $this->hasOne(
            DiscountUsage::class,
            'appointment_id'
        );
    }
}