<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, delete any orphaned invoices (invoices referencing non-existent orders)
        DB::statement('
            DELETE invoices FROM invoices
            LEFT JOIN orders ON invoices.order_id = orders.id
            WHERE orders.id IS NULL
        ');

        // Get the actual foreign key constraint name
        $constraint = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'invoices'
            AND COLUMN_NAME = 'order_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        // Drop the existing foreign key constraint if it exists
        if ($constraint) {
            DB::statement("ALTER TABLE invoices DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
        }

        Schema::table('invoices', function (Blueprint $table) {
            // Recreate the foreign key constraint with CASCADE delete
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the cascade foreign key constraint
            $table->dropForeign(['order_id']);

            // Restore the original RESTRICT foreign key constraint
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('restrict');
        });
    }
};
