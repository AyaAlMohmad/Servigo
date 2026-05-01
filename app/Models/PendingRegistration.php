<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingRegistration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'data',
        'role',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
    ];

    public $timestamps = true;
}
