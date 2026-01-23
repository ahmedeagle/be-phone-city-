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
        Schema::table('tickets', function (Blueprint $table) {
            // Make user_id nullable to support guest tickets
            $table->foreignId('user_id')->nullable()->change();
            
            // Add guest fields for non-authenticated users
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            
            // Add index for email to improve search performance
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Remove guest fields
            $table->dropIndex(['email']);
            $table->dropColumn(['name', 'email', 'phone']);
            
            // Revert user_id to required
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
