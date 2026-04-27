<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubService extends Model
{
    protected $fillable = ['service_id', 'name_ar', 'name_en'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class, 'sub_service_id');
    }
}
