<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have at least one admin
        $admin = Admin::first();
        
        if (!$admin) {
            $this->command->warn('No admin found. Please run AdminSeeder first.');
            return;
        }

        // Create 20 blog posts
        $blogs = Blog::factory(20)->create();

        $this->command->info('Created 20 blog posts successfully!');
        $this->command->info('Published: ' . $blogs->where('is_published', true)->count());
        $this->command->info('Drafts: ' . $blogs->where('is_published', false)->count());
    }
}
