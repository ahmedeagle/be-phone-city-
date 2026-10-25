<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\TikTokCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class GenerateTikTokCatalog extends Command
{
    protected $signature = 'tiktok:generate-catalog';
    protected $description = 'Generate the TikTok product CSV and a private report of skipped products';

    public function handle(TikTokCatalogService $catalog): int
    {
        $lock = Cache::lock('tiktok-generate-catalog', 3600);
        if (!$lock->get()) {
            $this->warn('Catalog generation is already running.');
            return self::SUCCESS;
        }
        $directory = storage_path('app/tiktok-catalog');
        $temp = null;
        $stream = null;
        try {
            File::ensureDirectoryExists($directory);
            $temp = tempnam($directory, 'feed-');
            $stream = fopen($temp, 'wb');
            if (!$stream) {
                throw new \RuntimeException('Cannot create catalog file.');
            }
            $write = function (array $row) use ($stream): void {
                if (fputcsv($stream, $row, ',', '"', '') === false) {
                    throw new \RuntimeException('Cannot write catalog row.');
                }
            };
            $write(TikTokCatalogService::HEADERS);
            $categories = Category::all()->keyBy('id');
            $count = 0;
            $skipped = [];
            foreach (Product::with(['categories', 'options'])->lazyById(100) as $product) {
                try {
                    $row = $catalog->row($product, $categories);
                } catch (InvalidArgumentException $e) {
                    $skipped[] = ['product_id' => $product->id, 'reason' => $e->getMessage()];
                    continue;
                }
                $write(array_map(fn ($key) => $row[$key], TikTokCatalogService::HEADERS));
                $count++;
            }
            fclose($stream);
            $stream = null;
            File::replace($directory.'/report.json', json_encode([
                'generated_at' => now()->toIso8601String(), 'exported' => $count, 'skipped' => $skipped,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            if ($count === 0) {
                $this->error('No valid products. Previous feed preserved; check storage/app/tiktok-catalog/report.json.');
                return self::FAILURE;
            }
            // Readers always receive a complete snapshot, never a partially written CSV.
            if (!rename($temp, $directory.'/products.csv')) {
                throw new \RuntimeException('Cannot publish catalog file.');
            }
            $temp = null;
            $this->info('Exported '.$count.' products; skipped '.count($skipped).'.');
            $this->line('Feed: '.url('/feeds/tiktok-products.csv'));
            $this->line('Review skipped products: storage/app/tiktok-catalog/report.json');
            return self::SUCCESS;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($temp && is_file($temp)) {
                unlink($temp);
            }
            $lock->release();
        }
    }
}
