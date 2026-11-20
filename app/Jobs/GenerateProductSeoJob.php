<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ProductSeoAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateProductSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(public int $productId)
    {
        $this->onQueue('default');
    }

    public function handle(ProductSeoAiService $seo): void
    {
        $product = Product::find($this->productId);
        if (! $product) {
            return;
        }

        $fields = ['seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en', 'seo_keywords_ar', 'seo_keywords_en'];
        $missingFields = array_filter($fields, fn (string $field) => blank($product->{$field}));
        if ($missingFields === []) {
            return;
        }

        $draft = $seo->generate($product);
        $updates = [];
        foreach ($missingFields as $field) {
            if (filled($draft[$field] ?? null)) {
                $updates[$field] = $draft[$field];
            }
        }

        if ($updates !== []) {
            $product->forceFill($updates)->saveQuietly();
            Log::info('AI SEO metadata generated.', ['product_id' => $product->id, 'fields' => array_keys($updates)]);
        }
    }
}