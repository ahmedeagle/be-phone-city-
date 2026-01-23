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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Unique ticket number/reference
            $table->string('ticket_number')->unique();

            // Customer who submitted the ticket
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Support agent handling the ticket
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('set null');

            // Subject and description (user's language)
            $table->string('subject');
            $table->longText('description');

            // Ticket status
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed'])->default('pending');

            // Priority level
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Ticket type
            $table->enum('type', ['support', 'complaint', 'inquiry', 'technical', 'billing', 'other'])->default('support');

            // Resolution tracking
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Indexes for better query performance
            $table->index('user_id');
            $table->index('admin_id');
            $table->index('status');
            $table->index('priority');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
