<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VipTierService;
use Illuminate\Support\Facades\Response;

class VipTierController extends Controller
{
    public function __construct(
        protected VipTierService $vipTierService
    ) {}

    /**
     * Get all VIP tier definitions (public endpoint).
     */
    public function index()
    {
        return Response::success(
            __('VIP tiers fetched successfully'),
            $this->vipTierService->getAllTiers()
        );
    }
}
