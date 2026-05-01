<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'is_banned'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function provider()
    {
        return $this->hasOne(Provider::class);
    }





    public function chatsAsParticipantOne()
    {
        return $this->hasMany(Chat::class, 'participant_one');
    }

    public function chatsAsParticipantTwo()
    {
        return $this->hasMany(Chat::class, 'participant_two');
    }

    public function allChats()
    {
        return Chat::where('participant_one', $this->id)
            ->orWhere('participant_two', $this->id);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
