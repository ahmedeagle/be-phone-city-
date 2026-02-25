<?php

namespace App\Ai\Tools;

use App\Models\Page;
use App\Models\About;
use App\Models\User;

class GetPageContentTool extends BaseTool
{
    public static function getName(): string
    {
        return 'get_page_content';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Get content of a specific page or about information. Use this to retrieve static pages, terms, privacy policy, or about us information.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['page', 'about'],
                        'description' => 'Type of content to retrieve',
                    ],
                    'slug' => [
                        'type' => 'string',
                        'description' => 'Page slug (only required for type=page)',
                    ],
                ],
                'required' => ['type'],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        $type = $arguments['type'] ?? 'page';

        try {
            if ($type === 'about') {
                return $this->getAboutContent();
            } else {
                $slug = $arguments['slug'] ?? null;

                if (!$slug) {
                    return $this->error('Page slug is required');
                }

                return $this->getPageContent($slug);
            }
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve content: ' . $e->getMessage());
        }
    }

    protected function getPageContent(string $slug): array
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$page) {
            return $this->error('Page not found or inactive');
        }

        return $this->success([
            'id' => $page->id,
            'name_en' => $page->name_en,
            'name_ar' => $page->name_ar,
            'title_en' => $page->title_en,
            'title_ar' => $page->title_ar,
            'short_description_en' => $page->short_description_en,
            'short_description_ar' => $page->short_description_ar,
            'description_en' => $page->description_en,
            'description_ar' => $page->description_ar,
            'slug' => $page->slug,
            'banner' => $page->banner,
        ]);
    }

    protected function getAboutContent(): array
    {
        $about = About::first();

        if (!$about) {
            return $this->error('About information not found');
        }

        return $this->success([
            'about_website_en' => $about->about_website_en,
            'about_website_ar' => $about->about_website_ar,
            'about_us_en' => $about->about_us_en,
            'about_us_ar' => $about->about_us_ar,
            'address_en' => $about->address_en,
            'address_ar' => $about->address_ar,
            'email' => $about->email,
            'phone' => $about->phone,
            'maps' => $about->maps,
            'social_links' => $about->social_links,
            'image' => $about->image,
        ]);
    }
}
