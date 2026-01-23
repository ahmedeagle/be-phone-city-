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
        if (!Schema::hasColumn('offers', 'slug')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name_ar');
            });
        }

        // Step 2: Generate slugs for all existing offers
        $offers = DB::table('offers')
            ->where(function($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->get();

        foreach ($offers as $offer) {
            if (!empty($offer->name_en)) {
                $slug = Str::slug($offer->name_en);
                $originalSlug = $slug;
                $count = 1;

                // Ensure uniqueness
                while (DB::table('offers')->where('slug', $slug)->where('id', '!=', $offer->id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                DB::table('offers')
                    ->where('id', $offer->id)
                    ->update(['slug' => $slug]);
            }
        }

        // Step 3: Check if unique index exists, if not add it
        $indexes = DB::select("SHOW INDEXES FROM `offers` WHERE Key_name = 'offers_slug_unique'");
        if (empty($indexes)) {
            DB::statement('ALTER TABLE `offers` ADD UNIQUE INDEX `offers_slug_unique` (`slug`)');
        }

        // Step 4: Make slug NOT NULL
        Schema::table('offers', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
