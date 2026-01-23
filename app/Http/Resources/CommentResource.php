<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blog_id' => $this->blog_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'guest_name' => $this->guest_name,
            'guest_email' => $this->guest_email,
            'commenter_name' => $this->commenter_name,
            'commenter_email' => $this->commenter_email,
            'is_guest_comment' => $this->is_guest_comment,
            'content' => $this->content,
            'is_approved' => $this->is_approved,
            'parent_id' => $this->parent_id,
            'is_reply' => $this->is_reply,
            'parent' => new CommentResource($this->whenLoaded('parent')),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'approved_replies' => CommentResource::collection($this->whenLoaded('approvedReplies')),
            'approved_replies_count' => $this->when(isset($this->approved_replies_count), $this->approved_replies_count),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
