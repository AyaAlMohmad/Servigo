<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = ['name_ar', 'name_en', 'photo'];
    public function providers()
    {
        return $this->hasMany(Provider::class);
    }
}
