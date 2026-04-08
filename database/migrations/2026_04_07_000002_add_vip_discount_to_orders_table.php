<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vip_discount', 10, 2)->default(0)->after('discount_id');
            $table->string('vip_tier_at_order', 20)->nullable()->after('vip_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vip_discount', 'vip_tier_at_order']);
        });
    }
};
