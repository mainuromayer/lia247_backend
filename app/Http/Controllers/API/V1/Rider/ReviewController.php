<?php

namespace App\Http\Controllers\API\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Rider\ReviewResource;
use App\Models\Review;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    use ApiResponse;

    /**
     * Get list of reviews and rating stats for the authenticated rider
     */
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user() ?? Auth::user();

            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }

            // Fetch reviews for this rider
            $reviewsQuery = Review::with(['user', 'ride'])
                ->where('target_id', $user->id)
                ->where('role', 'rider')
                ->latest();

            // Stats
            $totalReviews = $reviewsQuery->count();
            $averageRating = $reviewsQuery->avg('rating') ?: 0;

            $ratingDistribution = [
                5 => Review::where('target_id', $user->id)->where('role', 'rider')->where('rating', 5)->count(),
                4 => Review::where('target_id', $user->id)->where('role', 'rider')->where('rating', 4)->count(),
                3 => Review::where('target_id', $user->id)->where('role', 'rider')->where('rating', 3)->count(),
                2 => Review::where('target_id', $user->id)->where('role', 'rider')->where('rating', 2)->count(),
                1 => Review::where('target_id', $user->id)->where('role', 'rider')->where('rating', 1)->count(),
            ];

            // Paginate results
            $reviews = $reviewsQuery->paginate($request->get('limit', 10));

            return $this->success([
                'stats' => [
                    'average_rating' => (float)number_format($averageRating, 1),
                    'total_reviews' => (int)$totalReviews,
                    'rating_distribution' => [
                        'five_star' => (int)$ratingDistribution[5],
                        'four_star' => (int)$ratingDistribution[4],
                        'three_star' => (int)$ratingDistribution[3],
                        'two_star' => (int)$ratingDistribution[2],
                        'one_star' => (int)$ratingDistribution[1],
                    ]
                ],
                'reviews' => ReviewResource::collection($reviews),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ], 'Rider reviews retrieved successfully');

        } catch (Exception $e) {
            return $this->error([], 'An error occurred while fetching reviews: ' . $e->getMessage(), 500);
        }
    }
}
