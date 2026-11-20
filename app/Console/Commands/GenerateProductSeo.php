<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductSeoAiService;
use Illuminate\Console\Command;

class GenerateProductSeo extends Command
{
    protected $signature = 'seo:product {product : Product ID or slug} {--save : Save the generated draft}';
    protected $description = 'Generate reviewable AI SEO metadata for one product.';

    public function handle(ProductSeoAiService $seo): int
    {
        $product = Product::query()->where('id', $this->argument('product'))->orWhere('slug', $this->argument('product'))->first();
        if (! $product) { $this->error('Product not found.'); return self::FAILURE; }

        $draft = $seo->generate($product);
        $this->table(['Field', 'Draft'], collect($draft)->map(fn ($value, $key) => [$key, $value])->all());

        if ($this->option('save')) { $product->update($draft); $this->info('SEO draft saved.'); }
        else { $this->warn('Preview only. Review it, then rerun with --save.'); }
        return self::SUCCESS;
    }
}
