<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'birthday',
        'profile_image',
        'user_id',
        'salon_id',
        'first_name',
        'last_name',
    ];

    protected $casts = [
        'birthday' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class, 'salon_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'customer_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'customer_id');
    }

    public function discountUsages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class, 'customer_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }
}