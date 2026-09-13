<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;

// class WalletTransaction extends Model
// {
//     protected $table = 'wallet_transactions';

//     public $timestamps = false;

//     protected $fillable = [
//         'wallet_id',
//         'appointment_id',
//         'amount',
//         'type',
//         'description',
//         'created_at',
//     ];

//     protected $casts = [
//         'amount' => 'decimal:2',
//         'created_at' => 'datetime',
//     ];

//     public function wallet(): BelongsTo
//     {
//         return $this->belongsTo(
//             Wallet::class,
//             'wallet_id'
//         );
//     }

//     public function appointment(): BelongsTo
//     {
//         return $this->belongsTo(
//             Appointment::class,
//             'appointment_id'
//         );
//     }
// }



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'appointment_id',
        'amount',
        'type',
        'description',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}