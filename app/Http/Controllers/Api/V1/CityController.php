<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CityController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all cities (Paginated and Full Data)
     */
    public function index(Request $request)
    {
        $query = City::query();

        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        } else {
            $query->active();
        }

        $query->ordered();

        $data = $this->paginateData($query);

        return Response::success(
            __('Cities fetched successfully'),
            CityResource::collection($data['data']),
            200,
            $data['pagination']
        );
    }

    /**
     * Get a simple list of all active cities
     */
    public function activeList(Request $request)
    {
        // Inject the 'simple' attribute to the request
        $request->merge(['simple' => true]);

        $cities = City::getAllActive();

        return Response::success(
            __('Active cities list fetched successfully'),
            CityResource::collection($cities)
        );
    }
}
