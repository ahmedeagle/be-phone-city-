<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderPrintController extends Controller
{
    public function print(Order $order)
    {
        // Check if admin is authenticated
        if (!Auth::guard('admin')->check()) {
            return redirect()->to('/dashboard/login');
        }

        // Load all relationships
        $order->load([
            'user',
            'location.city',
            'paymentMethod',
            'items.product',
            'items.productOption',
            'invoice' // Optional - will show invoice number if exists
        ]);

        return view('admin.orders.print', compact('order'));
    }
}
