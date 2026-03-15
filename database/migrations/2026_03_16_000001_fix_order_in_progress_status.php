<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'Delivery is in progress')
            ->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', 'in_progress')
            ->update(['status' => 'Delivery is in progress']);
    }
};
