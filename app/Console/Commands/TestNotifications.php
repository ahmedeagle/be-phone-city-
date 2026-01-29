<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test 
                            {type : Type of notification to test (order|ticket|payment)}
                            {--user-id= : User ID to send notification to}
                            {--order-id= : Order ID for order/payment notifications}
                            {--ticket-id= : Ticket ID for ticket notifications}
                            {--sync : Run synchronously (bypass queue)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notification system with queues';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $type = $this->argument('type');
        $sync = $this->option('sync');

        if ($sync) {
            $this->info('⚠️  Running in SYNC mode (bypassing queue)');
            config(['queue.default' => 'sync']);
        } else {
            $this->info('✅ Running in QUEUE mode');
            $this->info('Queue connection: ' . config('queue.default'));
        }

        $this->newLine();

        try {
            switch ($type) {
                case 'order':
                    $this->testOrderNotification($notificationService);
                    break;
                case 'ticket':
                    $this->testTicketNotification($notificationService);
                    break;
                case 'payment':
                    $this->testPaymentNotification($notificationService);
                    break;
                default:
                    $this->error("Unknown notification type: {$type}");
                    $this->info('Available types: order, ticket, payment');
                    return 1;
            }

            if (!$sync) {
                $this->newLine();
                $this->info('📋 Next steps:');
                $this->line('1. Check jobs table: SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5;');
                $this->line('2. Process queue: php artisan queue:work');
                $this->line('3. Check notifications table: SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5;');
                $this->line('4. Check failed_jobs if any errors: SELECT * FROM failed_jobs ORDER BY failed_at DESC;');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    protected function testOrderNotification(NotificationService $notificationService)
    {
        $orderId = $this->option('order-id');
        $userId = $this->option('user-id');

        if (!$orderId) {
            $orderId = $this->ask('Enter Order ID');
        }

        $order = Order::with('user')->find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }

        $this->info("📦 Testing Order Notification for Order #{$order->order_number}");
        $this->line("Order ID: {$order->id}");
        $this->line("User: " . ($order->user ? $order->user->name . " ({$order->user->email})" : 'No user'));

        if ($userId && $order->user_id != $userId) {
            $user = User::find($userId);
            if ($user) {
                $order->user_id = $userId;
                $order->user = $user;
                $this->warn("⚠️  Using different user: {$user->name} ({$user->email})");
            }
        }

        $this->newLine();
        $this->info('Sending notification...');

        $notificationService->notifyOrderCreated($order);

        $this->info('✅ Notification dispatched successfully!');
        $this->line("Frontend URL will be: " . config('app.frontend_url') . "/" . ($order->user->locale ?? app()->getLocale()) . "/myorder/{$order->id}");
    }

    protected function testTicketNotification(NotificationService $notificationService)
    {
        $ticketId = $this->option('ticket-id');
        $userId = $this->option('user-id');

        if (!$ticketId) {
            $ticketId = $this->ask('Enter Ticket ID');
        }

        $ticket = \App\Models\Ticket::with('user')->find($ticketId);
        if (!$ticket) {
            $this->error("Ticket #{$ticketId} not found");
            return;
        }

        $this->info("🎫 Testing Ticket Notification for Ticket #{$ticket->ticket_number}");
        $this->line("Ticket ID: {$ticket->id}");
        $this->line("User: " . ($ticket->user ? $ticket->user->name . " ({$ticket->user->email})" : 'No user'));

        if ($userId && $ticket->user_id != $userId) {
            $user = User::find($userId);
            if ($user) {
                $ticket->user_id = $userId;
                $ticket->user = $user;
                $this->warn("⚠️  Using different user: {$user->name} ({$user->email})");
            }
        }

        $this->newLine();
        $this->info('Sending notification...');

        $notificationService->notifyTicketCreated($ticket);

        $this->info('✅ Notification dispatched successfully!');
        $this->line("Frontend URL will be: " . config('app.frontend_url') . "/" . ($ticket->user->locale ?? app()->getLocale()) . "/mytickets/{$ticket->id}");
    }

    protected function testPaymentNotification(NotificationService $notificationService)
    {
        $orderId = $this->option('order-id');
        $userId = $this->option('user-id');

        if (!$orderId) {
            $orderId = $this->ask('Enter Order ID');
        }

        $order = Order::with('user')->find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }

        $transaction = \App\Models\PaymentTransaction::where('order_id', $order->id)->first();
        if (!$transaction) {
            $this->error("No payment transaction found for Order #{$order->order_number}");
            $this->info('Creating a test transaction...');
            $transaction = \App\Models\PaymentTransaction::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'currency' => 'SAR',
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
            ]);
        }

        $this->info("💳 Testing Payment Notification for Order #{$order->order_number}");
        $this->line("Transaction ID: {$transaction->id}");
        $this->line("User: " . ($order->user ? $order->user->name . " ({$order->user->email})" : 'No user'));

        $this->newLine();
        $this->info('Sending notification...');

        $notificationService->notifyPaymentProofUploaded($transaction);

        $this->info('✅ Notification dispatched successfully!');
        $this->line("Frontend URL will be: " . config('app.frontend_url') . "/" . ($order->user->locale ?? app()->getLocale()) . "/myorder/{$order->id}");
    }
}
