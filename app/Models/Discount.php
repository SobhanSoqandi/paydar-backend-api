<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $table = 'discounts';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'percent',
        'customer_id',
        'start_date',
        'end_date',
        'max_usage',
        'is_active',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_usage' => 'integer',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function discountUsages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class, 'discount_id');
    }
}