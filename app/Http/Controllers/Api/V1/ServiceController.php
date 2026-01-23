<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ServiceController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all services
     */
    public function index(Request $request)
    {
        $query = Service::query()->where('is_active', 1)->orderBy('order', 'asc');

        $data = $this->paginateData($query);
        $services = ServiceResource::collection($data['data']);

        return Response::success(
            __('Services fetched successfully'),
            $services,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single service
     */
    public function show(Request $request, int $id)
    {
        $service = Service::findOrFail($id);

        return Response::success(
            __('Service fetched successfully'),
            new ServiceResource($service),
            200
        );
    }
}
