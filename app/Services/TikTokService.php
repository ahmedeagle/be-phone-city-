<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TikTokPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TikTokService
{
    public function enabled(): bool
    {
        return (bool) config('tiktok.enabled') && filled(config('tiktok.pixel_id'))
            && filled(config('tiktok.access_token'));
    }

    // Called AFTER the checkout transaction; tracking cannot roll back a payment.
    public function capture(Order $order, Request $request): void
    {
        if (! $this->enabled()) {
            return;
        }
        $context = $request->input('marketing');
        if (! is_array($context) || ($context['consent'] ?? null) !== true) {
            return;
        }
        $validator = Validator::make($context, [
            'ttclid' => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'ttp' => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'page_url' => ['required', 'url:http,https', 'max:2000'],
        ]);
        if ($validator->fails()) {
            return;
        }
        try {
            $data = $validator->validated();
            $url = parse_url($data['page_url']);
            $user = array_filter([
                'ttclid' => $data['ttclid'] ?? null, 'ttp' => $data['ttp'] ?? null,
                'ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'email' => $request->user()?->email
                    ? hash('sha256', strtolower(trim($request->user()->email))) : null,
            ]);
            TikTokPurchase::firstOrCreate(['order_id' => $order->id], [
                'event_id' => (string) Str::uuid(),
                'paid_at' => $order->payment_status === Order::PAYMENT_STATUS_PAID ? now() : null,
                'context' => [
                    'user' => $user,
                    'page' => ['url' => $url['scheme'].'://'.$url['host']
                        .(isset($url['port']) ? ':'.$url['port'] : '').($url['path'] ?? '/')],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('TikTok attribution capture failed', ['order_id' => $order->id]);
        }
    }

    public function recordPaid(Order $order): void
    {
        if (! $this->enabled() || $order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            return;
        }
        try {
            TikTokPurchase::where('order_id', $order->id)->whereNull('paid_at')->update(['paid_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('TikTok payment timestamp capture failed', ['order_id' => $order->id]);
        }
    }

    public function send(TikTokPurchase $event): bool
    {
        $order = $event->order;
        if (! $this->enabled() || $event->sent_at || ! $order || ! $event->context
            || $event->attempts >= 12 || $order->payment_status !== Order::PAYMENT_STATUS_PAID
            || $order->status === Order::STATUS_CANCELLED) {
            return false;
        }
        // Retry inside the deduplication window, always preserving the event ID.
        if ($event->first_attempt_at?->lt(now()->subHours(24))) {
            $event->update(['last_error' => 'retry_window_expired', 'attempts' => 12]);
            return false;
        }
        if (! $event->payload) {
            $event->paid_at ??= $order->updated_at;
            if ($event->paid_at->lt(now()->subDays(7))) {
                $event->update(['last_error' => 'event_too_old', 'attempts' => 12]);
                return false;
            }
            $event->payload = [
                'event' => 'Purchase', 'event_id' => $event->event_id,
                'event_time' => $event->paid_at->timestamp, ...$event->context,
                'properties' => [
                    'currency' => config('tiktok.currency'), 'value' => (float) $order->total,
                    'content_type' => 'product',
                    'contents' => $order->items->map(fn ($item) => [
                        'content_id' => (string) $item->product_id,
                        'price' => (float) $item->price, 'quantity' => (int) $item->quantity,
                    ])->values()->all(),
                ],
            ];
            $event->save();
        }
        $event->first_attempt_at ??= now();
        $event->attempts++;
        $event->next_attempt_at = now()->addMinutes(min(60, 2 ** min($event->attempts, 6)));
        $event->save();
        $body = ['event_source' => 'web', 'event_source_id' => config('tiktok.pixel_id'),
            'data' => [$event->payload]];
        if (config('tiktok.test_event_code')) {
            $body['test_event_code'] = config('tiktok.test_event_code');
        }
        try {
            $response = Http::withHeaders(['Access-Token' => config('tiktok.access_token')])
                ->connectTimeout(3)->timeout(10)
                ->post('https://business-api.tiktok.com/open_api/v1.3/event/track/', $body);
            if (! $response->successful() || $response->json('code') !== 0) {
                $event->update(['last_error' => 'provider_rejected_http_'.$response->status()]);
                return false;
            }
            $event->update(['sent_at' => now(), 'last_error' => null, 'context' => null, 'payload' => null]);
            return true;
        } catch (\Throwable $e) {
            $event->update(['last_error' => 'transport_error']);
            return false;
        }
    }
}
