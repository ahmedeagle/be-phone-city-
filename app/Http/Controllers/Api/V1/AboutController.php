<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Models\About;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AboutController extends Controller
{
    /**
     * Get about information (single record)
     */
    public function show()
    {
        $about = About::first();

        // If no about record exists, return empty response or create default
        if (!$about) {
            return Response::success(
                __('About information not found'),
                null,
                200
            );
        }

        return Response::success(
            __('About information fetched successfully'),
            new AboutResource($about)
        );
    }
}

