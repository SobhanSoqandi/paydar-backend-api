<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $table = 'wallets';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }
}