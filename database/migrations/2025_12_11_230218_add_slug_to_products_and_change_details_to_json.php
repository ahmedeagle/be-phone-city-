<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add slug column as nullable first (without unique constraint)
        // Check if column already exists (from partial migration)
        if (!Schema::hasColumn('products', 'slug')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name_ar');
            });
        }

        // Step 2: Generate slugs for all existing products that don't have one
        $products = DB::table('products')
            ->where(function($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->get();

        foreach ($products as $product) {
            if (!empty($product->name_en)) {
                $slug = Str::slug($product->name_en);
                $originalSlug = $slug;
                $count = 1;

                // Ensure uniqueness
                while (DB::table('products')->where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['slug' => $slug]);
            }
        }

        // Step 3: Check if unique index exists, if not add it
        $indexes = DB::select("SHOW INDEXES FROM `products` WHERE Key_name = 'products_slug_unique'");
        if (empty($indexes)) {
            DB::statement('ALTER TABLE `products` ADD UNIQUE INDEX `products_slug_unique` (`slug`)');
        }

        // Step 4: Make slug NOT NULL
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        // Step 5: Convert existing details text to JSON array format before changing column type
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $updates = [];

            // Convert details_en
            if (!empty($product->details_en)) {
                // Check if it's already JSON
                $decoded = json_decode($product->details_en, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Already JSON, keep it
                    $updates['details_en'] = json_encode($decoded);
                } else {
                    // Convert text to empty array (or you could parse it if needed)
                    $updates['details_en'] = json_encode([]);
                }
            } else {
                $updates['details_en'] = json_encode([]);
            }

            // Convert details_ar
            if (!empty($product->details_ar)) {
                // Check if it's already JSON
                $decoded = json_decode($product->details_ar, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Already JSON, keep it
                    $updates['details_ar'] = json_encode($decoded);
                } else {
                    // Convert text to empty array (or you could parse it if needed)
                    $updates['details_ar'] = json_encode([]);
                }
            } else {
                $updates['details_ar'] = json_encode([]);
            }

            if (!empty($updates)) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update($updates);
            }
        }

        // Step 6: Change details columns to JSON using raw SQL (more reliable)
        DB::statement('ALTER TABLE `products` MODIFY `details_en` JSON NULL');
        DB::statement('ALTER TABLE `products` MODIFY `details_ar` JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('details_en')->change();
            $table->text('details_ar')->change();
        });
    }
};
