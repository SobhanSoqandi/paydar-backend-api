<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'user_name',
        'phone',
        'email',
        'password_hash',
        'is_active',
        'role_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class, 'user_id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class, 'user_id');
    }
}