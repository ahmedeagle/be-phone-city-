<?php

namespace App\Console\Commands;

use App\Services\Shipping\Oto\OtoHttpClient;
use Illuminate\Console\Command;

class OtoListSendersCommand extends Command
{
    protected $signature = 'oto:list-senders';

    protected $description = 'List all registered senders/pickup locations from OTO account';

    public function handle(): int
    {
        $this->info('Fetching senders from OTO...');

        try {
            $client = app(OtoHttpClient::class);
            $response = $client->getSenders();

            if (empty($response)) {
                $this->warn('No senders found or empty response.');
                $this->line('Raw response: ' . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return 0;
            }

            $this->line('');
            $this->line('Raw API response:');
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line('');

            // Try to extract senders from different response structures
            $senders = $response['senders'] ?? $response['data'] ?? $response['pickupLocations'] ?? $response;

            if (!is_array($senders)) {
                $this->warn('Unexpected response format.');
                return 0;
            }

            // If it's a flat response (single sender), wrap it
            if (isset($senders['senderId']) || isset($senders['id'])) {
                $senders = [$senders];
            }

            $this->info('Found ' . count($senders) . ' sender(s):');
            $this->line('');

            foreach ($senders as $i => $sender) {
                $id = $sender['senderId'] ?? $sender['id'] ?? $sender['sender_id'] ?? 'N/A';
                $name = $sender['senderFullName'] ?? $sender['name'] ?? $sender['fullName'] ?? 'N/A';
                $city = $sender['senderCity'] ?? $sender['city'] ?? 'N/A';
                $phone = $sender['senderMobile'] ?? $sender['mobile'] ?? $sender['phone'] ?? 'N/A';
                $address = $sender['senderAddressLine1'] ?? $sender['address'] ?? 'N/A';

                $this->line("--- Sender #" . ($i + 1) . " ---");
                $this->line("  ID:      {$id}");
                $this->line("  Name:    {$name}");
                $this->line("  City:    {$city}");
                $this->line("  Phone:   {$phone}");
                $this->line("  Address: {$address}");
                $this->line('');
            }

            $this->info('Use the sender ID in branch admin → "معرّف مستودع OTO"');

        } catch (\Exception $e) {
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
