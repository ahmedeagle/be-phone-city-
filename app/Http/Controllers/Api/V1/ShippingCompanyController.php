<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShippingCompanyResource;
use App\Models\ShippingCompany;
use Illuminate\Support\Facades\Response;

class ShippingCompanyController extends Controller
{
    public function index()
    {
        $companies = ShippingCompany::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return Response::success(
            __('Shipping companies fetched successfully'),
            ShippingCompanyResource::collection($companies),
            200
        );
    }
}
