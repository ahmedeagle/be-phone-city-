<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shipping\Oto\OtoHttpClient;
use App\Services\Shipping\OtoShippingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderAwbController extends Controller
{
    public function print(Order $order)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->to('/dashboard/login');
        }

        if (!$order->oto_order_id) {
            abort(404, 'هذا الطلب غير مرتبط بشحنة OTO');
        }

        $awbUrl = $order->shipping_payload['printAWBURL'] ?? null;

        // If AWB URL not stored, fetch it from OTO in real-time
        if (!$awbUrl) {
            try {
                $shippingService = app(OtoShippingService::class);
                $shippingService->syncShipmentStatus($order);
                $order->refresh();
                $awbUrl = $order->shipping_payload['printAWBURL'] ?? null;
            } catch (\Exception $e) {
                Log::warning('AWB: Real-time sync failed, AWB URL not available', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$awbUrl) {
            return response()->view('admin.orders.awb-not-ready', [
                'order' => $order,
                'status' => $order->tracking_status ?? $order->shipping_payload['status'] ?? 'unknown',
            ], 404);
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

            return response()->view('admin.orders.awb-not-ready', [
                'order' => $order,
                'status' => 'error',
                'errorMessage' => $e->getMessage(),
            ], 502);
        }
    }
}
