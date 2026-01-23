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
        Schema::table('sliders', function (Blueprint $table) {
            $table->boolean('have_button')->default(false)->after('description_ar');
            $table->string('button_text_en')->nullable()->after('have_button');
            $table->string('button_text_ar')->nullable()->after('button_text_en');
            $table->enum('type', ['page', 'offer', 'product', 'category'])->nullable()->after('button_text_ar');
            $table->string('url_slug')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            $table->dropColumn(['have_button', 'button_text_en', 'button_text_ar', 'type', 'url_slug']);
        });
    }
};
