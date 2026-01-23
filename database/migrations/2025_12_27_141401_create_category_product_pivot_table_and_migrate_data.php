<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create pivot table
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combination
            $table->unique(['category_id', 'product_id']);
        });

        // Migrate existing data from category_id to pivot table
        DB::statement('
            INSERT INTO category_product (category_id, product_id, created_at, updated_at)
            SELECT category_id, id, created_at, updated_at
            FROM products
            WHERE category_id IS NOT NULL
        ');

        // Drop the category_id column from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add category_id column back
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('points')->constrained()->onDelete('set null');
        });

        // Migrate data back from pivot table (take first category for each product)
        DB::statement('
            UPDATE products p
            INNER JOIN (
                SELECT product_id, MIN(category_id) as category_id
                FROM category_product
                GROUP BY product_id
            ) cp ON p.id = cp.product_id
            SET p.category_id = cp.category_id
        ');

        // Drop pivot table
        Schema::dropIfExists('category_product');
    }
};
