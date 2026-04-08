<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();        // bronze, silver, gold, platinum
            $table->string('name_ar', 50);               // برونزي
            $table->string('name_en', 50);               // Bronze
            $table->unsignedInteger('min_orders');        // Minimum completed orders
            $table->decimal('min_total', 12, 2);         // Minimum total spent
            $table->decimal('discount_percentage', 5, 2); // Discount %
            $table->decimal('max_discount', 10, 2);      // Max discount cap per order
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default tiers
        DB::table('vip_tiers')->insert([
            [
                'key' => 'bronze',
                'name_ar' => 'برونزي',
                'name_en' => 'Bronze',
                'min_orders' => 4,
                'min_total' => 10000,
                'discount_percentage' => 2,
                'max_discount' => 100,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'silver',
                'name_ar' => 'فضي',
                'name_en' => 'Silver',
                'min_orders' => 8,
                'min_total' => 20000,
                'discount_percentage' => 4,
                'max_discount' => 150,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'gold',
                'name_ar' => 'ذهبي',
                'name_en' => 'Gold',
                'min_orders' => 12,
                'min_total' => 30000,
                'discount_percentage' => 6,
                'max_discount' => 200,
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'platinum',
                'name_ar' => 'بلاتيني',
                'name_en' => 'Platinum',
                'min_orders' => 16,
                'min_total' => 40000,
                'discount_percentage' => 8,
                'max_discount' => 250,
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vip_tiers');
    }
};
