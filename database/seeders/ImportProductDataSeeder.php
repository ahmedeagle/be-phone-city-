<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ImportProductDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->log('Starting product import from JSON...');

        // Call the artisan command with truncate enabled
        // This will delete orders, order items, product options, and products
        // Use --no-interaction to auto-confirm prompts when running from seeder
        $exitCode = Artisan::call('import:products-from-json', [
            'file' => 'product-data.json',
            '--truncate' => true, // Delete all orders, order items, product options, and products before import
            '--no-interaction' => true, // Auto-confirm prompts
        ]);

        // Output the command output
        $output = Artisan::output();
        if (!empty($output)) {
            $this->log($output);
        }

        if ($exitCode === 0) {
            $this->log('Product import completed successfully!');
        } else {
            $this->log('Product import failed!', 'error');
        }
    }

    /**
     * Log message (works in both seeder and command contexts)
     */
    protected function log(string $message, string $type = 'info'): void
    {
        if (isset($this->command)) {
            match ($type) {
                'error' => $this->command->error($message),
                'warn' => $this->command->warn($message),
                default => $this->command->info($message),
            };
        } else {
            // Fallback for when running programmatically
            echo $message . PHP_EOL;
        }
    }
}
