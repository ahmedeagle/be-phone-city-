<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have at least one user
        $user = User::first();
        
        if (!$user) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Create 30 tickets with various statuses
        $tickets = Ticket::factory(30)->create();

        $this->command->info('Created 30 tickets successfully!');
        $this->command->info('Pending: ' . $tickets->where('status', Ticket::STATUS_PENDING)->count());
        $this->command->info('In Progress: ' . $tickets->where('status', Ticket::STATUS_IN_PROGRESS)->count());
        $this->command->info('Resolved: ' . $tickets->where('status', Ticket::STATUS_RESOLVED)->count());
        $this->command->info('Closed: ' . $tickets->where('status', Ticket::STATUS_CLOSED)->count());
    }
}
