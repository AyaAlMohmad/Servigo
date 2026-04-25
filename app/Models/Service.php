<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
#[Fillable( ['name_ar', 'name_en', 'photo'])]

class Service extends Model
{
    public function providers()
    {
        return $this->hasMany(Provider::class);  
    }
}
