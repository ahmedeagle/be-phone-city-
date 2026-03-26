<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Support\Facades\Response;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::active()->ordered()->get();

        return Response::success(
            __('Branches fetched successfully'),
            BranchResource::collection($branches),
            200
        );
    }
}
