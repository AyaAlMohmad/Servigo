<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\SubService;
use App\Models\Provider;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function getSubServices($service_id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $mainService = Service::find($service_id);
        
        if (!$mainService) {
            return response()->json(['success' => false, 'message' => 'main_service_not_found'], 404);
        }

        $subServices = SubService::where('service_id', $service_id)
            ->get(['id', 'name_ar', 'name_en']);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => [
                'main_service' => [
                    'id' => $mainService->id,
                    'name_ar' => $mainService->name_ar,
                    'name_en' => $mainService->name_en,
                ],
                'sub_services' => $subServices
            ]
        ]);
    }

    public function getTopProviders($main_service_id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $mainService = Service::find($main_service_id);
        
        if (!$mainService) {
            return response()->json(['success' => false, 'message' => 'main_service_not_found'], 404);
        }

        $topProviders = Provider::where('main_service_id', $main_service_id)
            ->where('status', 'approved')
            ->where('profile_completed', true)
            ->where('is_available', true)
            ->with('user')
            ->withAvg('ratings', 'rating')
            ->orderBy('ratings_avg_rating', 'desc')
            ->limit(5)
            ->get()
            ->map(function($provider) {
                $avgRating = $provider->ratings_avg_rating ?? 0;
                return [
                    'provider_user_id' => $provider->user_id,
                    'name' => $provider->user?->name,
                    'photo' => $this->getUserPhoto($provider->user),
                    'avg_rating' => round($avgRating, 1),
                    'min_price' => (float)($provider->min_price ?? 0),
                    'max_price' => (float)($provider->max_price ?? 0),
                    'currency' => $provider->currency ?? 'USD',
                    'work_type' => $provider->work_type,
                    'location_name' => $provider->location_name,
                    'is_available' => $this->checkAvailability($provider),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $topProviders
        ]);
    }

    public function searchProviders(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $validator = $this->validateSearchRequest($request);
        if ($validator !== true) {
            return $validator;
        }

        $query = Provider::query()
            ->where('status', 'approved')
            ->where('profile_completed', true)
            ->where('is_available', true)
            ->with('user')
            ->withAvg('ratings', 'rating');

        $query->where('main_service_id', $request->main_service_id);

        if ($request->has('sub_service_id') && $request->sub_service_id) {
            $query->where('sub_service_id', $request->sub_service_id);
        }

        if ($request->has('min_price') && $request->min_price !== null) {
            $query->where('max_price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price !== null) {
            $query->where('min_price', '<=', $request->max_price);
        }

        if ($request->has('rating')) {
            $rating = (int)$request->rating;
            if ($rating == 5) {
                $query->having('ratings_avg_rating', '=', 5);
            } else {
                $query->having('ratings_avg_rating', '>=', $rating);
            }
        }

        if ($request->has('work_type') && $request->work_type) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->has('availability') && $request->availability === 'available_now') {
            $query->where(function($q) {
                $now = Carbon::now();
                $currentTime = $now->format('H:i:s');
                $currentDay = strtolower($now->format('l'));
                
                $q->whereDoesntHave('offDays', function($off) use ($currentDay) {
                    $off->where('day', $currentDay);
                })->where(function($time) use ($currentTime) {
                    $time->whereNull('work_start_time')
                         ->orWhere('work_start_time', '<=', $currentTime)
                         ->where('work_end_time', '>=', $currentTime);
                });
            });
        }

        $sortBy = $request->get('sort_by', 'rating');
        
        if ($sortBy === 'price') {
            $query->orderBy('min_price', 'asc');
        } elseif ($sortBy === 'rating') {
            $query->orderBy('ratings_avg_rating', 'desc');
        } elseif ($sortBy === 'location') {
            $this->applyLocationSorting($query, $request);
        }

        $providers = $query->get();

        SearchLog::create([
            'user_id' => $user->id,
            'main_service_id' => $request->main_service_id,
            'sub_service_id' => $request->sub_service_id ?? null,
        ]);

        if ($providers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'no_results_found',
                'data' => []
            ]);
        }

        $formattedProviders = $providers->map(function($provider) {
            return [
                'provider_user_id' => $provider->user_id,
                'name' => $provider->user?->name,
                'photo' => $this->getUserPhoto($provider->user),
                'avg_rating' => round($provider->ratings_avg_rating ?? 0, 1),
                'min_price' => (float)($provider->min_price ?? 0),
                'max_price' => (float)($provider->max_price ?? 0),
                'currency' => $provider->currency ?? 'USD',
                'work_type' => $provider->work_type,
                'location_name' => $provider->location_name,
                'latitude' => (float)($provider->latitude ?? 0),
                'longitude' => (float)($provider->longitude ?? 0),
                'is_available' => $this->checkAvailability($provider),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $formattedProviders
        ]);
    }

    private function validateSearchRequest($request)
    {
        if (!$request->has('main_service_id')) {
            return response()->json(['success' => false, 'message' => 'main_service_id_required'], 422);
        }

        if (!$request->has('sub_service_id')) {
            return response()->json(['success' => false, 'message' => 'sub_service_id_required'], 422);
        }

        $mainService = Service::find($request->main_service_id);
        if (!$mainService) {
            return response()->json(['success' => false, 'message' => 'main_service_not_found'], 404);
        }

        if ($request->has('rating')) {
            $rating = (int)$request->rating;
            if (!in_array($rating, [2, 3, 4, 5])) {
                return response()->json(['success' => false, 'message' => 'rating_invalid'], 422);
            }
        }

        if ($request->has('availability')) {
            if (!in_array($request->availability, ['available_now', 'any'])) {
                return response()->json(['success' => false, 'message' => 'availability_invalid'], 422);
            }
        }

        if ($request->has('work_type') && $request->work_type) {
            if (!in_array($request->work_type, ['fixed', 'mobile', 'both'])) {
                return response()->json(['success' => false, 'message' => 'work_type_invalid'], 422);
            }
        }

        if ($request->has('sort_by')) {
            if (!in_array($request->sort_by, ['price', 'rating', 'location'])) {
                return response()->json(['success' => false, 'message' => 'sort_by_invalid'], 422);
            }
            
            if ($request->sort_by === 'location') {
                if (!$request->has('latitude')) {
                    return response()->json(['success' => false, 'message' => 'latitude_required'], 422);
                }
                if (!$request->has('longitude')) {
                    return response()->json(['success' => false, 'message' => 'longitude_required'], 422);
                }
            }
        }

        return true;
    }

    private function applyLocationSorting($query, $request)
    {
        $lat = (float)$request->latitude;
        $lon = (float)$request->longitude;
        
        $haversine = "(6371 * acos(
            cos(radians($lat)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians($lon)) +
            sin(radians($lat)) * sin(radians(latitude))
        ))";
        
        $query->select('*')
            ->selectRaw("{$haversine} AS distance")
            ->orderBy('distance', 'asc');
    }

    private function checkAvailability($provider)
    {
        if (!$provider->is_available) {
            return false;
        }

        if (!$provider->work_start_time || !$provider->work_end_time) {
            return true;
        }

        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $currentDay = strtolower($now->format('l'));

        $offDays = $provider->offDays()->pluck('day')->toArray();
        if (in_array($currentDay, $offDays)) {
            return false;
        }

        $start = $provider->work_start_time;
        $end = $provider->work_end_time;
        
        if ($provider->overnight) {
            return $currentTime >= $start || $currentTime <= $end;
        } else {
            return $currentTime >= $start && $currentTime <= $end;
        }
    }

    private function getUserPhoto($user)
    {
        if ($user && $user->role === 'provider' && $user->provider) {
            return $user->provider->id_photo_front ?? null;
        }
        return null;
    }
}