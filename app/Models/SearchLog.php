<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = ['user_id', 'main_service_id', 'sub_service_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mainService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'main_service_id');
    }

    public function subService(): BelongsTo
    {
        return $this->belongsTo(SubService::class);
    }
}