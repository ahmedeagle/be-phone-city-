<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vip_tier', 20)->default('regular')->after('email_verified_at');
            $table->decimal('vip_tier_discount', 5, 2)->default(0)->after('vip_tier');
            $table->unsignedInteger('completed_orders_count')->default(0)->after('vip_tier_discount');
            $table->decimal('completed_orders_total', 12, 2)->default(0)->after('completed_orders_count');
            $table->timestamp('vip_tier_updated_at')->nullable()->after('completed_orders_total');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vip_tier',
                'vip_tier_discount',
                'completed_orders_count',
                'completed_orders_total',
                'vip_tier_updated_at',
            ]);
        });
    }
};
