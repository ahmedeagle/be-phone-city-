<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UpdatePagesNameFromTitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder copies title_en and title_ar values to name_en and name_ar
     * for all existing pages that don't have name values set.
     */
    public function run(): void
    {
        $pages = Page::all();
        $updatedCount = 0;

        foreach ($pages as $page) {
            $updated = false;

            // Only update if name fields are null or empty
            if (empty($page->name_en) && !empty($page->title_en)) {
                $page->name_en = $page->title_en;
                $updated = true;
            }

            if (empty($page->name_ar) && !empty($page->title_ar)) {
                $page->name_ar = $page->title_ar;
                $updated = true;
            }

            // Save only if we made changes
            if ($updated) {
                $page->save();
                $updatedCount++;
            }
        }

        Log::info("Updated {$updatedCount} pages: copied title fields to name fields");
    }
}
