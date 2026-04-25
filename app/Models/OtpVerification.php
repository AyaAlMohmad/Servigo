<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = [ 'email',
    'code',
    'type',
    'expires_at',
    'attempts',
    'is_verified',
    'verified_at'];
    public function user(){
        return $this->belongsTo(User::class);
    }
}
