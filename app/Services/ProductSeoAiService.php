<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class ProductSeoAiService
{
    public function generate(Product $product): array
    {
        if (blank(config('openai.api_key'))) {
            throw new RuntimeException('OPENAI_API_KEY is not configured on this server.');
        }

        $product->loadMissing('categories');
        $context = [
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'description_ar' => strip_tags((string) $product->description_ar),
            'description_en' => strip_tags((string) $product->description_en),
            'details_ar' => $product->details_ar,
            'details_en' => $product->details_en,
            'categories' => $product->categories->map(fn ($category) => ['ar' => $category->name_ar, 'en' => $category->name_en]),
            'price_sar' => $product->getFinalPrice(),
            'in_stock' => $product->quantity > 0,
        ];

        $response = OpenAI::chat()->create([
            'model' => config('openai.seo_model', 'gpt-4o-mini'),
            'temperature' => 0.25,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are an e-commerce SEO editor for a Saudi electronics store. Return JSON only. Never invent specifications, prices, stock, reviews, offers, guarantees, or rankings. Write natural Saudi Arabic and English.'],
                ['role' => 'user', 'content' => json_encode([
                    'task' => 'Create reviewable product SEO metadata. Arabic and English titles must be 30-70 characters. Descriptions must be 110-170 characters. Keywords are a comma-separated, non-repetitive list. Use only supplied product facts.',
                    'required_json' => ['seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en', 'seo_keywords_ar', 'seo_keywords_en'],
                    'product' => $context,
                ], JSON_UNESCAPED_UNICODE)],
            ],
        ]);

        $data = json_decode((string) $response->choices[0]->message->content, true);
        if (! is_array($data)) {
            throw new RuntimeException('OpenAI returned an invalid SEO response.');
        }

        return [
            'seo_title_ar' => Str::limit(trim((string) Arr::get($data, 'seo_title_ar')), 70, ''),
            'seo_title_en' => Str::limit(trim((string) Arr::get($data, 'seo_title_en')), 70, ''),
            'seo_description_ar' => Str::limit(trim((string) Arr::get($data, 'seo_description_ar')), 170, ''),
            'seo_description_en' => Str::limit(trim((string) Arr::get($data, 'seo_description_en')), 170, ''),
            'seo_keywords_ar' => Str::limit(trim((string) Arr::get($data, 'seo_keywords_ar')), 255, ''),
            'seo_keywords_en' => Str::limit(trim((string) Arr::get($data, 'seo_keywords_en')), 255, ''),
        ];
    }
}
