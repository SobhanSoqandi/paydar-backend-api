<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountUsage extends Model
{
    protected $table = 'discount_usages';

    public $timestamps = false;

    protected $fillable = [
        'discount_id',
        'customer_id',
        'appointment_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}