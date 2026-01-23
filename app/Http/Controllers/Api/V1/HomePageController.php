<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomePageResource;
use App\Models\HomePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class HomePageController extends Controller
{
    /**
     * Get home page information (single record)
     */
    public function show()
    {
        $homePage = HomePage::first();

        // If no home page record exists, return empty response or create default
        if (!$homePage) {
            return Response::success(
                __('Home page information not found'),
                null,
                200
            );
        }

        return Response::success(
            __('Home page information fetched successfully'),
            new HomePageResource($homePage)
        );
    }
}

