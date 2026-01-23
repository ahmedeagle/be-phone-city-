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
        if (!Schema::hasColumn('categories', 'slug')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name_ar');
            });
        }

        // Step 2: Generate slugs for all existing categories
        $categories = DB::table('categories')
            ->where(function($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->get();

        foreach ($categories as $category) {
            if (!empty($category->name_en)) {
                $slug = Str::slug($category->name_en);
                $originalSlug = $slug;
                $count = 1;

                // Ensure uniqueness
                while (DB::table('categories')->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['slug' => $slug]);
            }
        }

        // Step 3: Check if unique index exists, if not add it
        $indexes = DB::select("SHOW INDEXES FROM `categories` WHERE Key_name = 'categories_slug_unique'");
        if (empty($indexes)) {
            DB::statement('ALTER TABLE `categories` ADD UNIQUE INDEX `categories_slug_unique` (`slug`)');
        }

        // Step 4: Make slug NOT NULL
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
