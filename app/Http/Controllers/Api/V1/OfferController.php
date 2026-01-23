<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
class OfferController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all offers
     */
    public function index(Request $request)
    {
        $query = Offer::active();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by apply_to
        if ($request->filled('apply_to')) {
            $query->where('apply_to', $request->apply_to);
        }

        $data = $this->paginateData($query);
        $offers = OfferResource::collection($data['data']);

        return Response::success(__('Offers fetched successfully'), $data, 200, $data['pagination']);
    }

    public function homeOffers(Request $request)
    {
        $offers = Offer::with(['products', 'categories'])
            ->where('show_in_home', true)
            ->whereIn('apply_to', ['product', 'category'])
            ->active()
            ->get()
            ->map(function ($offer) {
                // إضافة أول عنصر مرتبط داخل الريسورس
                $offer->first_related = $offer->apply_to === 'product'
                    ? $offer->products()->first()
                    : $offer->categories()->first();

                return $offer;
            });

        return Response::success(
            __('Home offers fetched successfully'),
            OfferResource::collection($offers)
        );
    }

    /**
     * Get single offer details
     */
    public function show(Offer $offer)
    {
        $offer->load(['products', 'categories']);

        return Response::success(
            __('Offer fetched successfully'),
            new OfferResource($offer)
        );
    }
}
