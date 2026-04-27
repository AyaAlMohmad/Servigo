<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $table = 'portfolio';

    protected $fillable = ['provider_id', 'file_path', 'file_type', 'description'];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
