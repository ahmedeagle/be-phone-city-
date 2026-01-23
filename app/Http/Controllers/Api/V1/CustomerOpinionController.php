<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerOpinionResource;
use App\Models\CustomerOpinion;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CustomerOpinionController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all customer opinions
     */
    public function index(Request $request)
    {
        $opinions = CustomerOpinion::query()->orderBy('created_at', 'desc');

        // Optional: Filter by minimum rate
        if ($request->filled('min_rate')) {
            $opinions->where('rate', '>=', $request->min_rate);
        }

        $data = $this->paginateData($opinions);
        $opinions = CustomerOpinionResource::collection($data['data']);

        return Response::success(
            __('Customer opinions fetched successfully'),
            $opinions,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single customer opinion
     */
    public function show(Request $request, int $id)
    {
        $opinion = CustomerOpinion::findOrFail($id);

        return Response::success(
            __('Customer opinion fetched successfully'),
            new CustomerOpinionResource($opinion),
            200
        );
    }
}

