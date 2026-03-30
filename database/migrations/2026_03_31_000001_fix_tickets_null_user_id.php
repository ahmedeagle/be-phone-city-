<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix tickets created with null user_id by matching email to users table.
     * This happened because the ticket store route was outside auth middleware,
     * so Auth::id() returned null even for authenticated users.
     */
    public function up(): void
    {
        // Match tickets with null user_id to users by email
        DB::statement('
            UPDATE tickets
            INNER JOIN users ON tickets.email = users.email
            SET tickets.user_id = users.id
            WHERE tickets.user_id IS NULL
              AND tickets.email IS NOT NULL
        ');
    }

    public function down(): void
    {
        // Cannot reliably reverse — we don't know which tickets were originally null
    }
};
