<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    protected $table = 'tokens';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'refresh_token',
        'expires_at',
        'is_revoked',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_revoked' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}