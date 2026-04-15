<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_amount', 10, 2)->default(0);
            $table->decimal('max_amount', 10, 2)->nullable(); // null = unlimited (e.g. 3000+)
            $table->integer('points_awarded')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_tiers');
    }
};
