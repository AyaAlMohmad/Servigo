<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtpVerification extends Model
{
    use SoftDeletes;
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
