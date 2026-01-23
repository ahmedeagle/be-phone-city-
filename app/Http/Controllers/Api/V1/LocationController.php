<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class LocationController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all locations for authenticated user
     */
    public function index(Request $request)
    {
        $locations = Location::where('user_id', Auth::id())
            ->with('city')
            ->orderBy('created_at', 'desc');

        $data = $this->paginateData($locations);
        $locations = LocationResource::collection($data['data']);

        return Response::success(
            __('Locations fetched successfully'),
            $locations,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single location
     */
    public function show(int $id)
    {
        $location = Location::where('user_id', Auth::id())
            ->with('city')
            ->findOrFail($id);

        return Response::success(
            __('Location fetched successfully'),
            new LocationResource($location)
        );
    }

    /**
     * Create new location
     */
    public function store(StoreLocationRequest $request)
    {
        $location = Location::create([
            'user_id' => Auth::id(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'country' => $request->country,
            'city_id' => $request->city_id,
            'street_address' => $request->street_address,
            'national_address' => $request->national_address,
            'phone' => $request->phone,
            'email' => $request->email,
            'label' => $request->label,
        ]);

        $location->load('city');

        return Response::success(
            __('Location created successfully'),
            new LocationResource($location),
            201
        );
    }

    /**
     * Update location
     */
    public function update(UpdateLocationRequest $request, int $id)
    {
        $location = Location::where('user_id', Auth::id())
            ->findOrFail($id);

        $location->update($request->only([
            'first_name',
            'last_name',
            'country',
            'city_id',
            'street_address',
            'national_address',
            'phone',
            'email',
            'label',
        ]));

        $location->load('city');

        return Response::success(
            __('Location updated successfully'),
            new LocationResource($location)
        );
    }

    /**
     * Delete location
     */
    public function destroy(int $id)
    {
        $location = Location::where('user_id', Auth::id())
            ->findOrFail($id);

        $location->delete();

        return Response::success(__('Location deleted successfully'));
    }
}
