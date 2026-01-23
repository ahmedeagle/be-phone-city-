<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportProductsFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products-from-json
                            {file=product-data.json : Path to the JSON file}
                            {--truncate : Truncate existing products before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from WordPress/WooCommerce JSON export file';

    /**
     * System categories to filter out
     */
    protected array $systemCategories = [
        'outofstock',
        'simple',
        'variable',
        'grouped',
        'external',
        'تكلفة الشحن داخل السعودية',
    ];

    /**
     * Statistics
     */
    protected array $stats = [
        'processed' => 0,
        'created' => 0,
        'skipped' => 0,
        'errors' => 0,
        'options_created' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Reading JSON file: {$filePath}");

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON file: " . json_last_error_msg());
            return Command::FAILURE;
        }

        if (!isset($data['item']) || !is_array($data['item'])) {
            $this->error("Invalid JSON structure. Expected 'item' array.");
            return Command::FAILURE;
        }

        $items = $data['item'];
        $totalItems = count($items);

        $this->info("Found {$totalItems} products to import");

        if ($this->option('truncate')) {
            // Auto-confirm if running non-interactively (e.g., from seeder)
            $shouldTruncate = $this->option('no-interaction')
                ? true
                : $this->confirm('This will delete all orders, order items, reviews, points, offerables, carts, favorites, product options, and products. Continue?', true);

            if ($shouldTruncate) {
                $this->warn('Truncating existing data...');

                // Disable foreign key checks temporarily
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                try {
                    // Delete in order: order_items -> orders -> reviews -> points -> offerables -> carts -> favorites -> product_options -> products
                    $this->info('Deleting order items...');
                    DB::table('order_items')->delete();

                    $this->info('Deleting orders...');
                    DB::table('orders')->delete();

                    $this->info('Deleting reviews...');
                    DB::table('reviews')->delete();

                    $this->info('Deleting points...');
                    DB::table('points')->delete();

                    $this->info('Deleting offerables (product-offer relationships)...');
                    // Delete offerables that reference products (polymorphic relationship)
                    DB::table('offerables')
                        ->where('offerable_type', 'App\\Models\\Product')
                        ->delete();

                    $this->info('Deleting carts...');
                    DB::table('carts')->delete();

                    $this->info('Deleting favorites...');
                    DB::table('favorites')->delete();

                    $this->info('Deleting product options...');
                    DB::table('product_options')->delete();

                    $this->info('Deleting category-product relationships...');
                    DB::table('category_product')->delete();

                    $this->info('Deleting products...');
                    DB::table('products')->delete();

                    $this->info('All existing data deleted successfully.');
                } finally {
                    // Re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                }
            }
        }

        $this->newLine();
        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        DB::beginTransaction();

        try {
            // Filter only product items (exclude attachments and other post types)
            $productItems = array_filter($items, function ($item) {
                $postType = $this->extractCdata($item['post_type'] ?? []);
                return $postType === 'product';
            });

            $totalProducts = count($productItems);
            $this->info("Found {$totalProducts} products to import (filtered from {$totalItems} total items)");
            $this->newLine();

            // Update progress bar to reflect actual product count
            $bar = $this->output->createProgressBar($totalProducts);
            $bar->start();

            foreach ($productItems as $item) {
                $this->stats['processed']++;

                try {
                    $this->importProduct($item);
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->newLine();
                    $this->warn("Error importing product: " . $e->getMessage());
                    $this->line("Product data: " . json_encode($this->extractCdata($item['title'] ?? [])));
                }

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            $this->displaySummary();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error("Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Import a single product
     */
    protected function importProduct(array $item): void
    {
        // Extract basic product data
        $nameAr = $this->extractCdata($item['title'] ?? []);

        if (empty($nameAr)) {
            $this->stats['skipped']++;
            return;
        }

        // Extract meta values
        $meta = $this->extractMetaValues($item['postmeta'] ?? []);

        // Skip if no price
        if (empty($meta['_regular_price']) && empty($meta['_sale_price'])) {
            $this->stats['skipped']++;
            return;
        }

        // Get categories
        $categories = $this->findOrCreateCategories($item['category'] ?? []);

        // Extract descriptions from encoded field
        $encoded = $item['encoded'] ?? [];
        $htmlContent = $this->extractCdata($encoded[1] ?? []);
        $descriptions = $this->extractDescriptions($htmlContent);

        // Determine price (use sale price if exists, otherwise regular price)
        $mainPrice = !empty($meta['_sale_price'])
            ? (float) $meta['_sale_price']
            : (float) ($meta['_regular_price'] ?? 0);

        // Get quantity
        $quantity = isset($meta['_stock']) ? (int) $meta['_stock'] : 0;

        // Determine if product is new (based on post_date - within last 30 days)
        $postDate = $this->extractCdata($item['post_date'] ?? []);
        $isNew = $this->isProductNew($postDate);

        // Extract capacity if available
        $capacity = $this->extractCapacity($descriptions['description_ar'] ?? '');

        // Create product (don't set slug manually - let HasSlug trait handle it)
        $product = Product::create([
            'name_ar' => $nameAr,
            'name_en' => $this->generateEnglishName($nameAr),
            'description_ar' => $descriptions['description_ar'] ?? '',
            'description_en' => $descriptions['description_en'] ?? '',
            'details_ar' => $descriptions['details_ar'] ?? [],
            'details_en' => $descriptions['details_en'] ?? [],
            'about_ar' => $descriptions['about_ar'] ?? '',
            'about_en' => $descriptions['about_en'] ?? '',
            'capacity' => $capacity,
            'points' => 0,
            'main_price' => $mainPrice,
            'quantity' => $quantity,
            'is_new' => $isNew,
            'is_new_arrival' => $isNew, // Set as new arrival if it's new
            'is_featured' => false,
        ]);

        // Sync categories
        if (!empty($categories)) {
            $product->categories()->sync(collect($categories)->pluck('id')->toArray());
        }

        $this->stats['created']++;

        // Create product options if attributes exist
        if (!empty($meta['_product_attributes'])) {
            $this->createProductOptions($product, $meta['_product_attributes']);
        }
    }

    /**
     * Extract meta values from postmeta array
     */
    protected function extractMetaValues(array $postmeta): array
    {
        $meta = [];

        foreach ($postmeta as $metaItem) {
            $key = $this->extractCdata($metaItem['meta_key'] ?? []);
            $value = $this->extractCdata($metaItem['meta_value'] ?? []);

            if (!empty($key)) {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    /**
     * Extract value from __cdata wrapper
     */
    protected function extractCdata(array $data): string
    {
        return $data['__cdata'] ?? '';
    }

    /**
     * Extract slug from post_name (URL decoded)
     */
    protected function extractSlug(array $postName): ?string
    {
        $encodedSlug = $this->extractCdata($postName);

        if (empty($encodedSlug)) {
            return null;
        }

        // Decode URL-encoded slug
        $slug = urldecode($encodedSlug);

        // Clean and generate slug
        return Str::slug($slug);
    }

    /**
     * Extract and clean descriptions from HTML content
     */
    protected function extractDescriptions(string $htmlContent): array
    {
        if (empty($htmlContent)) {
            return [
                'description_ar' => '',
                'description_en' => '',
                'about_ar' => '',
                'about_en' => '',
                'details_ar' => [],
                'details_en' => [],
            ];
        }

        // Clean HTML but preserve structure
        $cleanedHtml = $this->cleanHtmlContent($htmlContent);

        // Extract description (everything before "حول هذه السلعة" or "عن هذة السلعة")
        $descriptionAr = $this->extractDescriptionSection($cleanedHtml);

        // Extract about section
        $aboutAr = $this->extractAboutSection($cleanedHtml);

        // Extract details as array (from lists)
        $detailsAr = $this->extractDetailsList($cleanedHtml);

        return [
            'description_ar' => $descriptionAr,
            'description_en' => '', // No English content in JSON
            'about_ar' => $aboutAr,
            'about_en' => '',
            'details_ar' => $detailsAr,
            'details_en' => [],
        ];
    }

    /**
     * Clean HTML content
     */
    protected function cleanHtmlContent(string $html): string
    {
        // Remove script and style tags
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);

        // Decode HTML entities
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($html);
    }

    /**
     * Extract description section
     */
    protected function extractDescriptionSection(string $html): string
    {
        // Try to extract content before "حول هذه السلعة" or "عن هذة السلعة"
        $patterns = [
            '/(.*?)(?:<h[12]><strong>حول هذه السلعة<\/strong><\/h[12]>|حول هذه السلعة|عن هذة السلعة)/s',
            '/(.*?)(?:<h[12]>.*?<\/h[12]>)/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(strip_tags($matches[1]));
            }
        }

        // If no pattern matches, return first paragraph or first 500 chars
        $text = strip_tags($html);
        return mb_substr($text, 0, 500);
    }

    /**
     * Extract about section
     */
    protected function extractAboutSection(string $html): string
    {
        // Extract content after "حول هذه السلعة" or "عن هذة السلعة"
        $patterns = [
            '/(?:<h[12]><strong>حول هذه السلعة<\/strong><\/h[12]>|حول هذه السلعة|عن هذة السلعة)(.*?)(?:<h[14]>|$)/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return trim(strip_tags($matches[1]));
            }
        }

        return '';
    }

    /**
     * Extract details as list items
     */
    protected function extractDetailsList(string $html): array
    {
        $details = [];

        // Extract <li> items
        if (preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $html, $matches)) {
            foreach ($matches[1] as $item) {
                $text = trim(strip_tags($item));
                if (!empty($text)) {
                    $details[] = $text;
                }
            }
        }

        return $details;
    }

    /**
     * Find or create categories
     */
    protected function findOrCreateCategories(array $categories): array
    {
        // Filter out system categories
        $validCategories = array_filter($categories, function ($cat) {
            $catName = $this->extractCdata($cat);
            return !in_array($catName, $this->systemCategories, true) && !empty($catName);
        });

        if (empty($validCategories)) {
            return [];
        }

        $result = [];

        foreach ($validCategories as $cat) {
            $categoryName = $this->extractCdata($cat);
            
            if (empty($categoryName)) {
                continue;
            }

            // Try to find existing category by Arabic name
            $category = Category::where('name_ar', $categoryName)->first();

            if (!$category) {
                // Create category if not found
                $category = Category::create([
                    'name_ar' => $categoryName,
                    'name_en' => $this->generateEnglishName($categoryName),
                ]);
            }

            $result[] = $category;
        }

        return $result;
    }

    /**
     * Generate English name from Arabic (simple transliteration placeholder)
     */
    protected function generateEnglishName(string $arabic): string
    {
        // For now, just use a transliterated version or default
        // In production, you might want to use a translation service
        return Str::slug($arabic) ?: 'Product';
    }


    /**
     * Extract capacity from description
     */
    protected function extractCapacity(string $description): ?string
    {
        // Look for capacity patterns like "128GB", "500ml", etc.
        if (preg_match('/(\d+\s*(?:GB|MB|ml|L|g|kg))/i', $description, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if product is new (created within last 30 days)
     */
    protected function isProductNew(?string $postDate): bool
    {
        if (empty($postDate)) {
            return false;
        }

        try {
            $date = Carbon::parse($postDate);
            return $date->isAfter(now()->subDays(30));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create product options from attributes
     */
    protected function createProductOptions(Product $product, string $attributes): void
    {
        // Unserialize PHP serialized data
        $unserialized = @unserialize($attributes);

        if (!is_array($unserialized)) {
            return;
        }

        // Look for color attributes (pa_color)
        if (isset($unserialized['pa_color'])) {
            $colorAttr = $unserialized['pa_color'];

            // Check if it's a taxonomy (has terms)
            if (isset($colorAttr['is_taxonomy']) && $colorAttr['is_taxonomy']) {
                // For taxonomy-based attributes, we'd need to fetch terms separately
                // For now, skip if it's taxonomy-based
                return;
            }

            // If it's not a taxonomy, check if there are values
            if (isset($colorAttr['value']) && !empty($colorAttr['value'])) {
                $colors = explode('|', $colorAttr['value']);

                foreach ($colors as $color) {
                    $color = trim($color);
                    if (!empty($color)) {
                        ProductOption::create([
                            'product_id' => $product->id,
                            'type' => ProductOption::TYPE_COLOR,
                            'value_ar' => $color,
                            'value_en' => $this->generateEnglishName($color),
                            'price' => null, // Use product's main price
                            'sku' => null,
                        ]);

                        $this->stats['options_created']++;
                    }
                }
            }
        }

        // Look for size attributes (pa_size)
        if (isset($unserialized['pa_size'])) {
            $sizeAttr = $unserialized['pa_size'];

            if (isset($sizeAttr['is_taxonomy']) && $sizeAttr['is_taxonomy']) {
                return;
            }

            if (isset($sizeAttr['value']) && !empty($sizeAttr['value'])) {
                $sizes = explode('|', $sizeAttr['value']);

                foreach ($sizes as $size) {
                    $size = trim($size);
                    if (!empty($size)) {
                        ProductOption::create([
                            'product_id' => $product->id,
                            'type' => ProductOption::TYPE_SIZE,
                            'value_ar' => $size,
                            'value_en' => $this->generateEnglishName($size),
                            'price' => null,
                            'sku' => null,
                        ]);

                        $this->stats['options_created']++;
                    }
                }
            }
        }
    }

    /**
     * Display import summary
     */
    protected function displaySummary(): void
    {
        $this->info('Import Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products Processed', $this->stats['processed']],
                ['Products Created', $this->stats['created']],
                ['Products Skipped', $this->stats['skipped']],
                ['Errors', $this->stats['errors']],
                ['Product Options Created', $this->stats['options_created']],
            ]
        );
    }
}
