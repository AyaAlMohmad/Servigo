<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'email',
        'data',
        'role',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
    ];

    // إذا كنت تستخدم timestamps في الجدول
    public $timestamps = true;
}