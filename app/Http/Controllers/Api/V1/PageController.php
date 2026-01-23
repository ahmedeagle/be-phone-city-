<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PageController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all pages (with optional filters & search)
     */
    public function index(Request $request)
    {
        $query = Page::query();

        // Search by title or short description (both languages)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'like', "%{$search}%")
                  ->orWhere('title_ar', 'like', "%{$search}%")
                  ->orWhere('short_description_en', 'like', "%{$search}%")
                  ->orWhere('short_description_ar', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active') && in_array($request->is_active, ['0', '1'])) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by slug (exact match – useful for frontend routing)
        if ($request->filled('slug')) {
            $query->where('slug', $request->slug);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'order'); // default sort by order column
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['order', 'created_at', 'updated_at', 'title_en', 'title_ar'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $data = $this->paginateData($query);

        $pages = PageResource::collection($data['data']);

        return Response::success(
            __('Pages fetched successfully'),
            $pages,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single page by ID or slug
     */
    public function show(Request $request, $identifier)
    {
        $query = Page::query();

        // Allow lookup by ID or slug
        if (is_numeric($identifier)) {
            $query->where('id', $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        // Optionally force only active pages in public API
        $query->where('is_active', true);

        $page = $query->firstOrFail();

        return Response::success(
            __('Page fetched successfully'),
            new PageResource($page)
        );
    }
}
