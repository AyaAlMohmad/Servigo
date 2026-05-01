<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    protected $fillable = ['type', 'participant_one', 'participant_two'];

    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one');
    }

    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function getOtherParticipant($userId)
    {
        if ($this->participant_one == $userId) {
            return $this->participantTwo;
        }
        return $this->participantOne;
    }
}