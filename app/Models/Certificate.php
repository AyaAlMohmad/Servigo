<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = ['provider_id', 'file_path'];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
