<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ReviewController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all reviews for a product — only approved reviews are returned publicly.
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product'])
            ->where('status', Review::STATUS_APPROVED);

        // Filter by product_id
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by user_id (for authenticated user's reviews)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'rating', 'updated_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $data = $this->paginateData($query);
        $reviews = ReviewResource::collection($data['data']);

        return Response::success(
            __('Reviews fetched successfully'),
            $reviews,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single review
     */
    public function show(int $id)
    {
        $review = Review::with(['user', 'product'])->findOrFail($id);

        return Response::success(
            __('Review fetched successfully'),
            new ReviewResource($review)
        );
    }

    /**
     * Create a new review
     */
    public function store(StoreReviewRequest $request)
    {
        $userId = Auth::id();

        // Check if user already reviewed this product
        $existingReview = Review::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingReview) {
            return Response::error(
                __('You have already reviewed this product'),
                null,
                400
            );
        }

        $review = Review::create([
            'user_id'    => $userId,
            'product_id' => $request->product_id,
            'comment'    => $request->comment,
            'rating'     => $request->rating,
            'status'     => Review::STATUS_PENDING,
        ]);

        $review->load(['user', 'product']);

        return Response::success(
            __('Your review has been submitted and is awaiting admin approval'),
            new ReviewResource($review),
            201
        );
    }

    /**
     * Update a review (only the owner can update)
     */
    public function update(UpdateReviewRequest $request, int $id)
    {
        $review = Review::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($review->isApproved()) {
            return Response::error(
                __('لا يمكن تعديل التقييم بعد اعتماده من الإدارة'),
                null,
                403
            );
        }

        $updateData = [];

        if ($request->filled('comment')) {
            $updateData['comment'] = $request->comment;
        }

        if ($request->filled('rating')) {
            $updateData['rating'] = $request->rating;
        }

        if (!empty($updateData)) {
            // Reset to pending so the updated content is re-reviewed by admin
            $updateData['status'] = Review::STATUS_PENDING;
            $review->update($updateData);
            $review->refresh();
        }

        $review->load(['user', 'product']);

        return Response::success(
            __('Review updated successfully'),
            new ReviewResource($review)
        );
    }

    /**
     * Delete a review (only the owner can delete)
     */
    public function destroy(int $id)
    {
        $review = Review::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($review->isApproved()) {
            return Response::error(
                __('لا يمكن حذف التقييم بعد اعتماده من الإدارة'),
                null,
                403
            );
        }

        $review->delete();

        return Response::success(__('Review deleted successfully'));
    }

    /**
     * Get authenticated user's reviews
     */
    public function myReviews(Request $request)
    {
        $reviews = Review::where('user_id', Auth::id())
            ->with(['product'])
            ->orderBy('created_at', 'desc');

        $data = $this->paginateData($reviews);
        $reviews = ReviewResource::collection($data['data']);

        return Response::success(
            __('Your reviews fetched successfully'),
            $reviews,
            200,
            $data['pagination']
        );
    }
}

