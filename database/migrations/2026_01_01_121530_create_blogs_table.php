<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();

            // Author relationship
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('set null');

            // Slug for SEO-friendly URLs
            $table->string('slug')->unique();

            // Featured image
            $table->string('featured_image')->nullable();

            // Title translations
            $table->string('title_en');
            $table->string('title_ar');

            // Short description/excerpt translations
            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();

            // Content translations
            $table->longText('content_en')->nullable();
            $table->longText('content_ar')->nullable();

            // SEO fields
            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->text('meta_keywords_en')->nullable();
            $table->text('meta_keywords_ar')->nullable();

            // Publishing status
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            // Views count for analytics
            $table->unsignedInteger('views_count')->default(0);

            // Allow comments flag
            $table->boolean('allow_comments')->default(true);

            $table->timestamps();

            // Indexes for better query performance
            $table->index('is_published');
            $table->index('published_at');
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
