<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointResource;
use App\Models\Point;
use App\Models\Setting;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class PointController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all points for authenticated user
     */
    public function index(Request $request)
    {
        $query = Point::where('user_id', Auth::id())
            ->with(['order', 'product'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'available') {
                $query->available();
            } elseif ($status === 'used') {
                $query->used();
            } elseif ($status === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by order_id
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        // Filter by product_id
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter expired points
        if ($request->boolean('expired_only')) {
            $query->expired();
        }

        // Filter expiring soon (within X days)
        if ($request->filled('expiring_within_days')) {
            $days = (int) $request->expiring_within_days;
            $query->whereNotNull('expire_at')
                ->where('expire_at', '<=', now()->addDays($days))
                ->where('expire_at', '>', now())
                ->where('status', Point::STATUS_AVAILABLE);
        }

        $data = $this->paginateData($query);
        $points = PointResource::collection($data['data']);

        return Response::success(
            __('Points fetched successfully'),
            $points,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single point
     */
    public function show(int $id)
    {
        $point = Point::where('user_id', Auth::id())
            ->with(['order', 'product'])
            ->findOrFail($id);

        return Response::success(
            __('Point fetched successfully'),
            new PointResource($point),
            200
        );
    }

    /**
     * Get points summary for authenticated user
     */
    public function summary()
    {
        $userId = Auth::id();
        $settings = Setting::getSettings();
        $pointValue = $settings->point_value ?? 1.00;

        $availablePoints = Point::getAvailablePoints($userId);
        $usedPoints = Point::getUsedPoints($userId);
        $expiredPoints = Point::getExpiredPoints($userId);
        $totalPoints = $availablePoints + $usedPoints + $expiredPoints;

        // Calculate available points value
        $availablePointsValue = $availablePoints * $pointValue;

        // Get expiring soon points (within 30 days)
        $expiringSoonPoints = Point::where('user_id', $userId)
            ->available()
            ->whereNotNull('expire_at')
            ->where('expire_at', '<=', now()->addDays(30))
            ->where('expire_at', '>', now())
            ->sum('points_count');

        // Get points expiring dates
        $expiringDates = Point::where('user_id', $userId)
            ->available()
            ->whereNotNull('expire_at')
            ->where('expire_at', '>', now())
            ->orderBy('expire_at', 'asc')
            ->limit(5)
            ->get(['expire_at', 'points_count'])
            ->map(function ($point) {
                return [
                    'date' => $point->expire_at->toISOString(),
                    'points' => $point->points_count,
                ];
            });

        return Response::success(
            __('Points summary fetched successfully'),
            [
                'summary' => [
                    'total_points' => $totalPoints,
                    'available_points' => $availablePoints,
                    'available_points_value' => round($availablePointsValue, 2),
                    'used_points' => $usedPoints,
                    'expired_points' => $expiredPoints,
                    'expiring_soon_points' => $expiringSoonPoints,
                    'point_value' => round($pointValue, 2),
                ],
                'expiring_dates' => $expiringDates,
            ],
            200
        );
    }
}
