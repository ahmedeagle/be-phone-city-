<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Blog;
use App\Models\Comment;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CommentController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all comments for a blog post
     */
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'blog', 'images', 'parent', 'approvedReplies.images']);

        // Filter by blog_id (required)
        if (!$request->filled('blog_id')) {
            return Response::error(
                __('Blog ID is required'),
                ['blog_id' => __('The blog_id field is required.')],
                422
            );
        }

        $query->where('blog_id', $request->blog_id);

        // Only show approved comments for public API
        if (!$request->has('include_pending')) {
            $query->approved();
        }

        // Only show top-level comments (not replies) unless specified
        if (!$request->has('include_replies')) {
            $query->topLevel();
        }

        // Filter by user_id
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by parent_id (to get replies to a specific comment)
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['created_at', 'updated_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $data = $this->paginateData($query);
        $comments = CommentResource::collection($data['data']);

        return Response::success(
            __('Comments fetched successfully'),
            $comments,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single comment
     */
    public function show(int $id)
    {
        $comment = Comment::with(['user', 'blog', 'images', 'parent', 'approvedReplies.images'])
            ->findOrFail($id);

        return Response::success(
            __('Comment fetched successfully'),
            new CommentResource($comment)
        );
    }

    /**
     * Create a new comment
     */
    public function store(StoreCommentRequest $request)
    {
        // Check if blog exists and allows comments
        $blog = Blog::findOrFail($request->blog_id);

        if (!$blog->allow_comments) {
            return Response::error(
                __('Comments are disabled for this blog post'),
                null,
                403
            );
        }

        // Check if blog is published
        if (!$blog->isVisible()) {
            return Response::error(
                __('Cannot comment on unpublished blog post'),
                null,
                403
            );
        }

        // If parent_id is provided, verify it exists and belongs to the same blog
        if ($request->filled('parent_id')) {
            $parentComment = Comment::where('id', $request->parent_id)
                ->where('blog_id', $request->blog_id)
                ->first();

            if (!$parentComment) {
                return Response::error(
                    __('Parent comment not found or does not belong to this blog'),
                    null,
                    404
                );
            }
        }

        $userId = Auth::id();

        $commentData = [
            'blog_id' => $request->blog_id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
            'is_approved' => false, // Comments require moderation by default
        ];

        // Add user_id if authenticated, otherwise use guest fields
        if ($userId) {
            $commentData['user_id'] = $userId;
        } else {
            $commentData['guest_name'] = $request->guest_name;
            $commentData['guest_email'] = $request->guest_email;
        }

        $comment = Comment::create($commentData);

        // Load relationships
        $comment->load(['user', 'blog', 'parent']);

        return Response::success(
            __('Comment created successfully. It will be visible after approval.'),
            new CommentResource($comment),
            201
        );
    }

    /**
     * Update a comment (only the owner can update)
     */
    public function update(UpdateCommentRequest $request, int $id)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required to update comments'),
                null,
                401
            );
        }

        $comment = Comment::where('user_id', $userId)
            ->findOrFail($id);

        // Update content if provided
        if ($request->filled('content')) {
            $comment->update([
                'content' => $request->content,
                'is_approved' => false, // Reset approval status when updated
            ]);
            $comment->refresh();
        }

        $comment->load(['user', 'blog', 'parent']);

        return Response::success(
            __('Comment updated successfully. It will be visible after re-approval.'),
            new CommentResource($comment)
        );
    }

    /**
     * Delete a comment (only the owner can delete)
     */
    public function destroy(int $id)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required to delete comments'),
                null,
                401
            );
        }

        $comment = Comment::where('user_id', $userId)
            ->findOrFail($id);

        $comment->delete();

        return Response::success(__('Comment deleted successfully'));
    }

    /**
     * Get authenticated user's comments
     */
    public function myComments(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required'),
                null,
                401
            );
        }

        $comments = Comment::where('user_id', $userId)
            ->with(['blog', 'images', 'parent'])
            ->orderBy('created_at', 'desc');

        $data = $this->paginateData($comments);
        $comments = CommentResource::collection($data['data']);

        return Response::success(
            __('Your comments fetched successfully'),
            $comments,
            200,
            $data['pagination']
        );
    }
}

