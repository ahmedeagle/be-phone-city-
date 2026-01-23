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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Blog post relationship
            $table->foreignId('blog_id')->constrained('blogs')->onDelete('cascade');

            // User who made the comment (nullable for guest comments)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Guest comment fields (when user_id is null)
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();

            // Comment content
            $table->text('content');

            // Moderation
            $table->boolean('is_approved')->default(false);

            // Nested comments (replies)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');

            $table->timestamps();

            // Indexes for better query performance
            $table->index('blog_id');
            $table->index('user_id');
            $table->index('is_approved');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
