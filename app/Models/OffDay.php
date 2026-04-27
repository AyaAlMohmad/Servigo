<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffDay extends Model
{
    protected $fillable = ['provider_id', 'day'];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
