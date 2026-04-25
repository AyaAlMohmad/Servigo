<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Provider extends Model
{
    protected $fillable = [
        'user_id', 'location_name', 'latitude', 'longitude', 'work_type',
        'main_service_id', 'id_photo_front', 'id_photo_back', 'status',
        'rejection_reason', 'profile_completed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mainService()
    {
        return $this->belongsTo(Service::class, 'main_service_id');
    }
}
