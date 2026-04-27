<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Provider extends Model
{
    use SoftDeletes;

    // protected $fillable = [
    //     'user_id', 'location_name', 'latitude', 'longitude', 'work_type',
    //     'main_service_id', 'id_photo_front', 'id_photo_back', 'status',
    //     'rejection_reason', 'profile_completed'
    // ];
protected $fillable = [
    'user_id', 'location_name', 'latitude', 'longitude', 'location_description',
    'work_type', 'main_service_id', 'sub_service_id', 'id_photo_front', 'id_photo_back',
    'status', 'rejection_reason', 'profile_completed', 'currency', 'min_price', 'max_price',
    'work_start_time', 'work_end_time', 'overnight', 'about_me'
];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mainService()
    {
        return $this->belongsTo(Service::class, 'main_service_id');
    }
    // أضف هذه العلاقات داخل Provider.php

public function offDays()
{
    return $this->hasMany(OffDay::class);
}

public function certificates()
{
    return $this->hasMany(Certificate::class);
}

public function portfolio()
{
    return $this->hasMany(Portfolio::class);
}



public function subService()
{
    return $this->belongsTo(SubService::class, 'sub_service_id');
}
}
