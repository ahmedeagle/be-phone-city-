<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckDiscountCodeRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use App\Traits\PaginatesResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DiscountController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all active discounts (authenticated users only)
     */
    public function index(Request $request)
    {
        $query = Discount::where('status', true)
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by code
        if ($request->filled('search')) {
            $query->where('code', 'like', "%{$request->search}%");
        }

        $data = $this->paginateData($query);
        $discounts = DiscountResource::collection($data['data']);

        return Response::success(
            __('Discounts fetched successfully'),
            $discounts,
            200,
            $data['pagination']
        );
    }

    /**
     * Check if a discount code is valid
     */
    public function checkCode(CheckDiscountCodeRequest $request)
    {
        $code = $request->code;
        $discount = Discount::where('code', $code)->first();

        // Check if discount exists
        if (!$discount) {
            return Response::error(
                __('Discount code not found'),
                null,
                404
            );
        }

        // Check if discount is active
        if (!$discount->status) {
            return Response::error(
                __('Discount code is not active'),
                null,
                400
            );
        }

        // Check if discount is within valid date range
        $now = Carbon::now();
        if ($now->lt($discount->start) || $now->gt($discount->end)) {
            return Response::error(
                __('Discount code has expired or not yet started'),
                null,
                400
            );
        }

        // Return discount details
        return Response::success(
            __('Discount code is valid'),
            new DiscountResource($discount),
            200
        );
    }
}
