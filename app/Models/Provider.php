<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Provider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'location_name', 'latitude', 'longitude', 'location_description',
        'work_type', 'main_service_id', 'sub_service_id', 'id_photo_front', 'id_photo_back',
        'status', 'rejection_reason', 'profile_completed', 'currency', 'min_price', 'max_price',
        'work_start_time', 'work_end_time', 'overnight', 'about_me'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'profile_completed' => 'boolean',
        'overnight' => 'boolean',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

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
        return $this->belongsTo(SubService::class, 'sub_service_id');
    }

    public function offDays(): HasMany
    {
        return $this->hasMany(OffDay::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function portfolio(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

 
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'provider_id');
    }


    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'provider_id');
    }




    public function getAvgRatingAttribute(): float
    {
        return round($this->ratings()->avg('rating') ?? 0, 1);
    }

 
    public function getRatingsCountAttribute(): int
    {
        return $this->ratings()->count();
    }


    public function getOffDaysArrayAttribute(): array
    {
        return $this->offDays()->pluck('day')->toArray();
    }


    public function getIsAvailableNowAttribute(): bool
    {
        return $this->checkAvailability();
    }

   
    public function getPhotoAttribute(): ?string
    {
        return $this->id_photo_front ?? null;
    }


    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')
                     ->where('profile_completed', true);
    }


    public function scopeNotBanned($query)
    {
        return $query->whereHas('user', function($q) {
            $q->where('is_banned', false);
        });
    }


    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }


    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('max_price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('min_price', '<=', $max);
        }
        return $query;
    }

 
    public function scopeMinRating($query, $rating)
    {
        if ($rating == 5) {
            return $query->havingRaw('AVG(ratings.rating) = 5');
        }
        return $query->havingRaw('AVG(ratings.rating) >= ?', [$rating]);
    }

   
    public function scopeWorkType($query, $type)
    {
        if ($type && in_array($type, ['fixed', 'mobile', 'both'])) {
            return $query->where('work_type', $type);
        }
        return $query;
    }

   
    public function scopeAvailableNow($query)
    {
        return $query->where(function($q) {
            $now = now();
            $currentTime = $now->format('H:i:s');
            $currentDay = strtolower($now->format('l'));
            
            $q->whereDoesntHave('offDays', function($off) use ($currentDay) {
                $off->where('day', $currentDay);
            })->where(function($time) use ($currentTime) {
                $time->whereNull('work_start_time')
                     ->orWhere(function($sub) use ($currentTime) {
                         $sub->where('work_start_time', '<=', $currentTime)
                             ->where('work_end_time', '>=', $currentTime);
                     })
                     ->orWhere(function($sub) use ($currentTime) {
                         $sub->where('overnight', true)
                             ->where(function($night) use ($currentTime) {
                                 $night->where('work_start_time', '<=', $currentTime)
                                       ->orWhere('work_end_time', '>=', $currentTime);
                             });
                     });
            });
        });
    }

  
    public function scopeOrderByPrice($query, $direction = 'asc')
    {
        return $query->orderBy('min_price', $direction);
    }

  
    public function scopeOrderByRating($query, $direction = 'desc')
    {
        return $query->orderBy('avg_rating', $direction);
    }

  
    public function scopeOrderByDistance($query, $latitude, $longitude, $direction = 'asc')
    {
        $haversine = "(6371 * acos(
            cos(radians($latitude)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians($longitude)) +
            sin(radians($latitude)) * sin(radians(latitude))
        ))";
        
        return $query->select('*')
                     ->selectRaw("{$haversine} AS distance")
                     ->orderBy('distance', $direction);
    }


    public function checkAvailability(): bool
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = strtolower($now->format('l'));
        
        $offDays = $this->offDays()->pluck('day')->toArray();
        if (in_array($currentDay, $offDays)) {
            return false;
        }
        
        if (!$this->work_start_time || !$this->work_end_time) {
            return true;
        }
        
        if ($this->overnight) {
            return $currentTime >= $this->work_start_time || $currentTime <= $this->work_end_time;
        } else {
            return $currentTime >= $this->work_start_time && $currentTime <= $this->work_end_time;
        }
    }

  
    public function distanceTo($latitude, $longitude): float
    {
        $earthRadius = 6371; // km
        
        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }

  
    public function isComplete(): bool
    {
        return $this->profile_completed && $this->status === 'approved';
    }

  
    public function updateProfileCompletion(): bool
    {
        $required = [
            'location_name', 'latitude', 'longitude', 'work_type',
            'main_service_id', 'min_price', 'max_price', 'about_me'
        ];
        
        $completed = true;
        foreach ($required as $field) {
            if (empty($this->$field)) {
                $completed = false;
                break;
            }
        }
        
        $this->profile_completed = $completed;
        $this->save();
        
        return $completed;
    }
}