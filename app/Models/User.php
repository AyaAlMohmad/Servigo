<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;          // أضف هذا السطر
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;  // أضف HasApiTokens هنا

    protected $fillable = [
        'name', 'phone', 'email', 'password', 'role', 'is_banned'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function provider()
    {
        return $this->hasOne(Provider::class);
    }
}