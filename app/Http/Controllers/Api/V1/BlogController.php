<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BlogController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all blog posts (with optional filters & search)
     */
    public function index(Request $request)
    {
        $query = Blog::with(['admin', 'images']);

        // Only show published blogs for public API
        if (!$request->has('include_drafts')) {
            $query->published();
        }

        // Search by title or content (both languages)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title_en', 'like', "%{$search}%")
                  ->orWhere('title_ar', 'like', "%{$search}%")
                  ->orWhere('short_description_en', 'like', "%{$search}%")
                  ->orWhere('short_description_ar', 'like', "%{$search}%")
                  ->orWhere('content_en', 'like', "%{$search}%")
                  ->orWhere('content_ar', 'like', "%{$search}%");
            });
        }

        // Filter by published status
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        // Filter by slug (exact match)
        if ($request->filled('slug')) {
            $query->where('slug', $request->slug);
        }

        // Filter by author
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['published_at', 'created_at', 'updated_at', 'views_count', 'title_en', 'title_ar'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            // Default: latest published first
            $query->latest();
        }

        // Include comments count
        $query->withCount('comments');

        // Pagination
        $data = $this->paginateData($query);
        $blogs = BlogResource::collection($data['data']);

        return Response::success(
            __('Blog posts fetched successfully'),
            $blogs,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single blog post by ID or slug
     */
    public function show(Request $request, $identifier)
    {
        $query = Blog::with(['admin', 'images']);

        // Allow lookup by ID or slug
        if (is_numeric($identifier)) {
            $query->where('id', $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        // Only show published blogs for public API (unless admin)
        if (!$request->has('include_drafts')) {
            $query->published();
        }

        $blog = $query->firstOrFail();

        // Increment views count
        $blog->incrementViews();

        // Load comments if requested
        if ($request->has('include_comments')) {
            $blog->load(['comments.replies' => function ($q) {
                $q->approved()->oldest();
            }]);
        }

        // Load comments count
        $blog->loadCount('comments');

        return Response::success(
            __('Blog post fetched successfully'),
            new BlogResource($blog)
        );
    }
}

