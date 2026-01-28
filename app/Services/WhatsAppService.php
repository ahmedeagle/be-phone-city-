<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send WhatsApp message to user
     *
     * @param User $user
     * @param string $message
     * @return bool
     */
    public function sendMessage(User $user, string $message): bool
    {
        // Get full phone number with country code for chatId
        $phoneData = $this->formatPhoneNumberForHypersender($user->phone);

        if (!$phoneData) {
            Log::warning('WhatsApp: Invalid phone number for user', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
            return false;
        }

        try {
            // Get Hypersender/WhatsApp API configuration
            $apiUrl = config('services.whatsapp.api_url');
            $apiKey = config('services.whatsapp.api_key');
            $apiToken = config('services.whatsapp.api_token');
            $provider = config('services.whatsapp.provider', 'hypersender');

            if (!$apiUrl || (!$apiKey && !$apiToken)) {
                Log::warning('WhatsApp: API configuration missing', [
                    'api_url' => $apiUrl ? 'set' : 'missing',
                    'api_key' => $apiKey ? 'set' : 'missing',
                    'api_token' => $apiToken ? 'set' : 'missing',
                ]);
                return false;
            }

            // Send message via Hypersender API
            $response = $this->sendViaHypersender($phoneData['full'], $phoneData['local'], $message, $apiUrl, $apiKey, $apiToken);

            if ($response && $response->successful()) {
                $responseData = $response->json();

                // Check if message was queued or actually sent
                $isQueued = $responseData['queued'] ?? false;
                $isSent = $responseData['sent'] ?? false;

                if ($isQueued && !$isSent) {
                    // Message is queued, check status after a delay
                    $queuedRequestLink = $responseData['queued_request_link'] ?? null;

                    Log::info('WhatsApp message queued via Hypersender', [
                        'user_id' => $user->id,
                        'phone' => $phoneData['full'],
                        'chatId' => $phoneData['full'] . '@c.us',
                        'queued_request_link' => $queuedRequestLink,
                        'response' => $responseData,
                    ]);

                    // Note: Message is queued, actual delivery depends on WhatsApp instance connection
                    // You may want to check the queued request status later
                    return true;
                } elseif ($isSent) {
                    Log::info('WhatsApp message sent successfully via Hypersender', [
                        'user_id' => $user->id,
                        'phone' => $phoneData['full'],
                        'response' => $responseData,
                    ]);
                    return true;
                }
            }

            Log::error('Hypersender API error', [
                'user_id' => $user->id,
                'phone' => $phoneData['full'] ?? null,
                'status' => $response?->status(),
                'response' => $response?->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp service exception', [
                'user_id' => $user->id,
                'phone' => $phoneData['full'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send product offer message via WhatsApp
     *
     * @param User $user
     * @param Product $product
     * @param Offer|null $offer
     * @return bool
     */
    public function sendProductOffer(User $user, Product $product, ?Offer $offer = null): bool
    {
        $message = $this->buildOfferMessage($user, $product, $offer);
        return $this->sendMessage($user, $message);
    }

    /**
     * Build offer message content
     *
     * @param User $user
     * @param Product $product
     * @param Offer|null $offer
     * @return string
     */
    protected function buildOfferMessage(User $user, Product $product, ?Offer $offer = null): string
    {
        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';

        $productName = $isArabic ? $product->name_ar : $product->name_en;
        $productUrl = config('app.frontend_url', config('app.url')) . '/singleproduct/' . $product->slug;
        $finalPrice = $product->getFinalPrice();

        if ($offer) {
            // Message with offer
            $offerName = $isArabic ? $offer->name_ar : $offer->name_en;
            $discountValue = $offer->type === 'percentage'
                ? $offer->value . '%'
                : number_format($offer->value, 2) . ' ' . __('SAR');

            if ($isArabic) {
                return "مرحباً {$user->name} 👋\n\n"
                    . "لاحظنا أنك كنت مهتماً بمنتج: {$productName}\n\n"
                    . "🎉 لدينا عرض خاص لك!\n"
                    . "العرض: {$offerName}\n"
                    . "الخصم: {$discountValue}\n"
                    . "السعر النهائي: " . number_format($finalPrice, 2) . " ريال\n\n"
                    . "لا تفوت هذه الفرصة! 🛒\n"
                    . "{$productUrl}";
            } else {
                return "Hello {$user->name} 👋\n\n"
                    . "We noticed you were interested in: {$productName}\n\n"
                    . "🎉 We have a special offer for you!\n"
                    . "Offer: {$offerName}\n"
                    . "Discount: {$discountValue}\n"
                    . "Final Price: " . number_format($finalPrice, 2) . " SAR\n\n"
                    . "Don't miss this opportunity! 🛒\n"
                    . "{$productUrl}";
            }
        } else {
            // Message without offer (just reminder)
            if ($isArabic) {
                return "مرحباً {$user->name} 👋\n\n"
                    . "لاحظنا أنك كنت مهتماً بمنتج: {$productName}\n"
                    . "السعر: " . number_format($finalPrice, 2) . " ريال\n\n"
                    . "هل ما زلت مهتماً؟ 🛒\n"
                    . "{$productUrl}";
            } else {
                return "Hello {$user->name} 👋\n\n"
                    . "We noticed you were interested in: {$productName}\n"
                    . "Price: " . number_format($finalPrice, 2) . " SAR\n\n"
                    . "Are you still interested? 🛒\n"
                    . "{$productUrl}";
            }
        }
    }

    /**
     * Format phone number for Hypersender WhatsApp
     * Supports Saudi Arabia (966) and Egypt (20) country codes
     * Returns both full number (with country code) and local number
     *
     * @param string|null $phone
     * @return array|null Returns ['full' => '966501234567', 'local' => '501234567', 'country_code' => '966']
     */
    protected function formatPhoneNumberForHypersender(?string $phone): ?array
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($phone) || strlen($phone) < 9) {
            return null;
        }

        $countryCode = null;
        $localNumber = null;

        // Detect and handle Saudi Arabia numbers (966)
        if (strpos($phone, '966') === 0 && strlen($phone) >= 12) {
            $countryCode = '966';
            $localNumber = substr($phone, 3);
        } elseif (strlen($phone) === 9 && strpos($phone, '5') === 0) {
            // 9 digits starting with 5 (Saudi Arabia)
            $countryCode = '966';
            $localNumber = $phone;
        } elseif (strlen($phone) === 10 && strpos($phone, '05') === 0) {
            // 10 digits starting with 05 (Saudi Arabia)
            $countryCode = '966';
            $localNumber = substr($phone, 1);
        }
        // Detect and handle Egypt numbers (20)
        elseif (strpos($phone, '20') === 0 && strlen($phone) >= 12) {
            $countryCode = '20';
            $localNumber = substr($phone, 2);
        } elseif (strlen($phone) === 11 && strpos($phone, '01') === 0) {
            // 11 digits starting with 01 (Egypt)
            $countryCode = '20';
            $localNumber = $phone;
        } elseif (strlen($phone) === 10 && strpos($phone, '1') === 0) {
            // 10 digits starting with 1 (Egypt)
            $countryCode = '20';
            $localNumber = $phone;
        }
        // Handle other formats
        elseif (strlen($phone) > 12) {
            if (strpos($phone, '966') === 0) {
                $countryCode = '966';
                $localNumber = substr($phone, 3);
            } elseif (strpos($phone, '20') === 0) {
                $countryCode = '20';
                $localNumber = substr($phone, 2);
            } else {
                // Try to detect - take last 9-11 digits
                $localNumber = substr($phone, -10);
                // Default to Saudi Arabia if can't detect
                $countryCode = '966';
            }
        }

        if (!$countryCode || !$localNumber) {
            return null;
        }

        $fullNumber = $countryCode . $localNumber;

        return [
            'full' => $fullNumber,
            'local' => $localNumber,
            'country_code' => $countryCode,
        ];
    }

    /**
     * Send message via Hypersender API
     * Based on Hypersender API documentation: https://hypersender.io/docs/api/whatsapp/send-text-safe
     *
     * @param string $fullPhone Full phone number with country code (e.g., 966501234567)
     * @param string $localPhone Local phone number without country code (e.g., 501234567)
     * @param string $message Message text
     * @param string $apiUrl Base API URL
     * @param string|null $apiKey API key (not used, kept for compatibility)
     * @param string|null $apiToken Bearer token for authentication
     * @return \Illuminate\Http\Client\Response|null
     */
    protected function sendViaHypersender(string $fullPhone, string $localPhone, string $message, string $apiUrl, ?string $apiKey, ?string $apiToken)
    {
        if (!$apiToken) {
            Log::error('Hypersender: API token is required');
            return null;
        }

        // Get instance ID from config
        $instanceId = config('services.whatsapp.instance_id');
        if (!$instanceId) {
            Log::error('Hypersender: Instance ID is required');
            return null;
        }

        // Hypersender uses chatId format: FULL phone number (with country code) + @c.us
        // Example: 966501234567@c.us or 201558125032@c.us
        $chatId = $fullPhone . '@c.us';

        // Hypersender API endpoint format: /api/whatsapp/v2/{instance}/send-text-safe
        $apiUrl = rtrim($apiUrl, '/');
        $endpoint = "/api/whatsapp/v2/{$instanceId}/send-text-safe";

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiToken,
        ];

        // Hypersender API payload structure
        $payload = [
            'chatId' => $chatId,
            'text' => $message,
        ];

        try {
            Log::info('Sending message via Hypersender', [
                'url' => $apiUrl . $endpoint,
                'chatId' => $chatId,
                'full_phone' => $fullPhone,
                'local_phone' => $localPhone,
                'message_length' => strlen($message),
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($apiUrl . $endpoint, $payload);

            return $response;
        } catch (\Exception $e) {
            Log::error('Hypersender API request failed', [
                'error' => $e->getMessage(),
                'url' => $apiUrl . $endpoint,
                'chatId' => $chatId,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Check status of a queued request
     *
     * @param string $queuedRequestLink Full URL to check queued request status
     * @param string $apiToken Bearer token
     * @return array|null
     */
    public function checkQueuedRequestStatus(string $queuedRequestLink, string $apiToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept' => 'application/json',
            ])->get($queuedRequestLink);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to check queued request status', [
                'url' => $queuedRequestLink,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
