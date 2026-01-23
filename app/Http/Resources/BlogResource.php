<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->title,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'short_description' => $this->short_description,
            'short_description_en' => $this->short_description_en,
            'short_description_ar' => $this->short_description_ar,
            'content' => $this->content,
            'content_en' => $this->content_en,
            'content_ar' => $this->content_ar,
            'featured_image' => $this->featured_image ? asset('storage/' . $this->featured_image) : null,
            'meta_description' => $this->meta_description,
            'meta_description_en' => $this->meta_description_en,
            'meta_description_ar' => $this->meta_description_ar,
            'meta_keywords' => $this->meta_keywords,
            'meta_keywords_en' => $this->meta_keywords_en,
            'meta_keywords_ar' => $this->meta_keywords_ar,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'views_count' => $this->views_count,
            'allow_comments' => $this->allow_comments,
            'author' => $this->whenLoaded('admin', function () {
                return [
                    'id' => $this->admin->id,
                    'name' => $this->admin->name,
                ];
            }),
            'author_name' => $this->author_name,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'comments_count' => $this->comments_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
