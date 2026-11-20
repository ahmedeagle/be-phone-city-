<?php

namespace App\Console\Commands;

use App\Jobs\GenerateProductSeoJob;
use App\Models\Product;
use Illuminate\Console\Command;

class FillMissingProductSeo extends Command
{
    protected $signature = 'seo:fill-missing {--limit=40 : Maximum products to queue}';
    protected $description = 'Queue AI SEO generation only for products with missing SEO metadata.';

    public function handle(): int
    {
        if (blank(config('openai.api_key'))) {
            $this->error('OPENAI_API_KEY is not configured.');
            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 500));
        $fields = ['seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en', 'seo_keywords_ar', 'seo_keywords_en'];
        $products = Product::query()->where(function ($query) use ($fields) {
            foreach ($fields as $field) {
                $query->orWhereNull($field)->orWhere($field, '');
            }
        })->orderBy('id')->limit($limit)->pluck('id');

        foreach ($products as $productId) {
            GenerateProductSeoJob::dispatch($productId);
        }

        $this->info("Queued {$products->count()} product SEO jobs. Existing admin-written fields will not be changed.");
        return self::SUCCESS;
    }
}