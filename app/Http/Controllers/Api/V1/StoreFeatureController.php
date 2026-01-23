<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreFeatureResource;
use App\Models\StoreFeature;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class StoreFeatureController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all store features
     */
    public function index(Request $request)
    {
        $features = StoreFeature::query()->orderBy('created_at', 'desc');

        $data = $this->paginateData($features);
        $features = StoreFeatureResource::collection($data['data']);

        return Response::success(
            __('Store features fetched successfully'),
            $features,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single store feature
     */
    public function show(Request $request, int $id)
    {
        $feature = StoreFeature::findOrFail($id);

        return Response::success(
            __('Store feature fetched successfully'),
            new StoreFeatureResource($feature),
            200
        );
    }
}

