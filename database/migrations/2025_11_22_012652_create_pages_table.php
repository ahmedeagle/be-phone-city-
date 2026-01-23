<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('banner')->nullable();
            $table->string('slug')->unique();

            $table->string('title_en')->nullable();
            $table->string('title_ar')->nullable();

            $table->text('short_description_en')->nullable();
            $table->text('short_description_ar')->nullable();

            $table->longText('description_en')->nullable();
            $table->longText('description_ar')->nullable();

            $table->integer('order')->default(0);

            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_ar')->nullable();

            $table->text('meta_keywords_en')->nullable();
            $table->text('meta_keywords_ar')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('can_delete')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
