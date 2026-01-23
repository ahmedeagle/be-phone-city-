<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SliderController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all sliders
     */
    public function index(Request $request)
    {
        $sliders = Slider::query()->orderBy('created_at', 'desc');

        $data = $this->paginateData($sliders);
        $sliders = SliderResource::collection($data['data']);

        return Response::success(
            __('Sliders fetched successfully'),
            $sliders,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single slider
     */
    public function show(Request $request, int $id)
    {
        $slider = Slider::findOrFail($id);

        return Response::success(
            __('Slider fetched successfully'),
            new SliderResource($slider),
            200
        );
    }
}
