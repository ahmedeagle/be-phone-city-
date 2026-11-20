<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_title_ar', 70)->nullable()->after('about_ar');
            $table->string('seo_title_en', 70)->nullable()->after('seo_title_ar');
            $table->string('seo_description_ar', 170)->nullable()->after('seo_title_en');
            $table->string('seo_description_en', 170)->nullable()->after('seo_description_ar');
            $table->string('seo_keywords_ar', 255)->nullable()->after('seo_description_en');
            $table->string('seo_keywords_en', 255)->nullable()->after('seo_keywords_ar');
            $table->string('seo_canonical_url', 500)->nullable()->after('seo_keywords_en');
            $table->string('seo_robots', 40)->default('index, follow')->after('seo_canonical_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['seo_title_ar', 'seo_title_en', 'seo_description_ar', 'seo_description_en', 'seo_keywords_ar', 'seo_keywords_en', 'seo_canonical_url', 'seo_robots']);
        });
    }
};
