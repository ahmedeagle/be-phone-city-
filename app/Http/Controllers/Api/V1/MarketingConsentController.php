<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TikTokPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketingConsentController extends Controller
{
    public function destroy(Request $request)
    {
        $events = TikTokPurchase::whereNull('sent_at')->whereNotNull('context')
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))->get();
        foreach ($events as $event) {
            Cache::lock('tiktok-purchase-'.$event->id, 60)->block(12, function () use ($event) {
                $event->update(['context' => null, 'payload' => null, 'last_error' => 'consent_withdrawn']);
            });
        }
        return response()->noContent();
    }
}
