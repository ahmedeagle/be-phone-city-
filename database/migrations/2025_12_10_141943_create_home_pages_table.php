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
        Schema::create('home_pages', function (Blueprint $table) {
            $table->id();
            $table->text('offer_text_en')->nullable();
            $table->text('offer_text_ar')->nullable();
            $table->json('offer_images')->nullable();
            $table->string('app_title_en')->nullable();
            $table->string('app_title_ar')->nullable();
            $table->text('app_description_en')->nullable();
            $table->text('app_description_ar')->nullable();
            $table->string('app_main_image')->nullable();
            $table->json('app_images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_pages');
    }
};
