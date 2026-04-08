<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'vip_max_discount')) {
                $table->decimal('vip_max_discount', 10, 2)->default(0)->after('vip_tier_discount');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'vip_tier_label')) {
                $table->string('vip_tier_label', 50)->nullable()->after('vip_tier_at_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vip_max_discount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vip_tier_label']);
        });
    }
};
