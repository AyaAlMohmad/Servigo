<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        if ($user->role !== 'user') {
            return response()->json([
                'success' => false, 
                'message' => 'only_customers_can_rate'
            ], 403);
        }

        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);

        $providerUser = User::where('id', $request->provider_id)
            ->where('role', 'provider')
            ->first();

        if (!$providerUser) {
            return response()->json([
                'success' => false, 
                'message' => 'provider_not_found'
            ], 404);
        }

        $existingRating = Rating::where('provider_id', $request->provider_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false, 
                'message' => 'you_already_rated_this_provider',
                'data' => [
                    'existing_rating' => $existingRating->rating,
                    'existing_review' => $existingRating->review,
                ]
            ], 409);
        }

        $rating = Rating::create([
            'provider_id' => $request->provider_id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        $rating->load(['user']);

        return response()->json([
            'success' => true,
            'message' => 'rating_added_successfully',
            'data' => [
                'id' => $rating->id,
                'provider_id' => $rating->provider_id,
                'provider_name' => $providerUser->name,
                'user_id' => $rating->user_id,
                'user_name' => $rating->user->name,
                'rating' => $rating->rating,
                'review' => $rating->review,
                'created_at' => $rating->created_at->toDateTimeString(),
            ]
        ], 201);
    }

    
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $rating = Rating::find($id);
        
        if (!$rating) {
            return response()->json([
                'success' => false, 
                'message' => 'rating_not_found'
            ], 404);
        }

        if ($rating->user_id !== $user->id) {
            return response()->json([
                'success' => false, 
                'message' => 'you_can_only_update_your_own_ratings'
            ], 403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);

        $rating->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        $rating->load(['user']);

        $providerUser = User::find($rating->provider_id);

        return response()->json([
            'success' => true,
            'message' => 'rating_updated_successfully',
            'data' => [
                'id' => $rating->id,
                'provider_id' => $rating->provider_id,
                'provider_name' => $providerUser?->name,
                'user_id' => $rating->user_id,
                'user_name' => $rating->user->name,
                'rating' => $rating->rating,
                'review' => $rating->review,
                'updated_at' => $rating->updated_at->toDateTimeString(),
            ]
        ]);
    }

   
    public function destroy($id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $rating = Rating::find($id);
        
        if (!$rating) {
            return response()->json([
                'success' => false, 
                'message' => 'rating_not_found'
            ], 404);
        }

        if ($rating->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false, 
                'message' => 'you_can_only_delete_your_own_ratings'
            ], 403);
        }

        $rating->delete();

        return response()->json([
            'success' => true,
            'message' => 'rating_deleted_successfully',
            'data' => null
        ]);
    }

    
    public function getProviderRatings($provider_id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $providerUser = User::where('id', $provider_id)
            ->where('role', 'provider')
            ->first();

        if (!$providerUser) {
            return response()->json([
                'success' => false, 
                'message' => 'provider_not_found'
            ], 404);
        }

        $ratings = Rating::where('provider_id', $provider_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($rating) {
                return [
                    'id' => $rating->id,
                    'user_id' => $rating->user_id,
                    'user_name' => $rating->user->name,
                    'rating' => $rating->rating,
                    'review' => $rating->review,
                    'created_at' => $rating->created_at->toDateTimeString(),
                    'created_at_human' => $rating->created_at->diffForHumans(),
                ];
            });

        $stats = [
            'average_rating' => round($ratings->avg('rating') ?? 0, 1),
            'total_ratings' => $ratings->count(),
            'rating_distribution' => [
                '5_stars' => $ratings->where('rating', 5)->count(),
                '4_stars' => $ratings->where('rating', 4)->count(),
                '3_stars' => $ratings->where('rating', 3)->count(),
                '2_stars' => $ratings->where('rating', 2)->count(),
                '1_stars' => $ratings->where('rating', 1)->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => [
                'provider' => [
                    'id' => $providerUser->id,
                    'name' => $providerUser->name,
                ],
                'stats' => $stats,
                'ratings' => $ratings,
            ]
        ]);
    }

public function myRatings()
{
    $user = auth()->user();
    
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
    }

    $ratings = Rating::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    if ($ratings->isEmpty()) {
        return response()->json([
            'success' => true,
            'message' => 'no_ratings_found',
            'data' => []
        ]);
    }

    $formattedRatings = $ratings->map(function($rating) {
        $providerUser = User::find($rating->provider_id);
        return [
            'id' => $rating->id,
            'provider_id' => $rating->provider_id,
            'provider_name' => $providerUser?->name,
            'rating' => $rating->rating,
            'review' => $rating->review,
            'created_at' => $rating->created_at->toDateTimeString(),
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'success',
        'data' => $formattedRatings
    ]);
}

 
    public function show($id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $rating = Rating::with('user')->find($id);
        
        if (!$rating) {
            return response()->json([
                'success' => false, 
                'message' => 'rating_not_found'
            ], 404);
        }

        $providerUser = User::find($rating->provider_id);

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => [
                'id' => $rating->id,
                'provider' => [
                    'id' => $rating->provider_id,
                    'name' => $providerUser?->name,
                ],
                'user' => [
                    'id' => $rating->user_id,
                    'name' => $rating->user->name,
                ],
                'rating' => $rating->rating,
                'review' => $rating->review,
                'created_at' => $rating->created_at->toDateTimeString(),
                'updated_at' => $rating->updated_at->toDateTimeString(),
            ]
        ]);
    }


    public function getProviderAverage($provider_id)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        $providerUser = User::where('id', $provider_id)
            ->where('role', 'provider')
            ->first();

        if (!$providerUser) {
            return response()->json([
                'success' => false, 
                'message' => 'provider_not_found'
            ], 404);
        }

        $average = Rating::where('provider_id', $provider_id)
            ->avg('rating') ?? 0;
        
        $total = Rating::where('provider_id', $provider_id)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => [
                'provider_id' => $provider_id,
                'provider_name' => $providerUser->name,
                'average_rating' => round($average, 1),
                'total_ratings' => $total,
            ]
        ]);
    }
}