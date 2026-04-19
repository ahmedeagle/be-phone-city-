<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shipping\Oto\OtoHttpClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderAwbController extends Controller
{
    public function print(Order $order)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->to('/dashboard/login');
        }

        $awbUrl = $order->shipping_payload['printAWBURL'] ?? null;

        if (!$awbUrl) {
            abort(404, 'رابط بوليصة الشحن غير متوفر لهذا الطلب');
        }

        try {
            $client = app(OtoHttpClient::class);
            $pdfContent = $client->fetchAwbPdf($awbUrl);

            $filename = "AWB-{$order->order_number}.pdf";

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('AWB print failed', [
                'order_id' => $order->id,
                'awb_url' => $awbUrl,
                'error' => $e->getMessage(),
            ]);

            abort(502, 'فشل تحميل بوليصة الشحن من OTO. يرجى المحاولة لاحقاً.');
        }
    }
}
