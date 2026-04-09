<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class StockNotificationController extends Controller
{
    /**
     * Subscribe to back-in-stock notification for a product/option.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'product_option_id' => 'nullable|exists:product_options,id',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $exists = StockNotification::where('product_id', $request->product_id)
            ->where('product_option_id', $request->product_option_id)
            ->where('email', $request->email)
            ->where('notified', false)
            ->exists();

        if ($exists) {
            return Response::success(__('You are already subscribed for this notification'));
        }

        StockNotification::create([
            'product_id' => $request->product_id,
            'product_option_id' => $request->product_option_id,
            'email' => $request->email,
            'user_id' => Auth::id(),
        ]);

        return Response::success(__('You will be notified when this product is back in stock'), null, 201);
    }

    /**
     * Unsubscribe from back-in-stock notification.
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'product_option_id' => 'nullable|exists:product_options,id',
        ]);

        if ($validator->fails()) {
            return Response::error(__('Validation error'), $validator->errors(), 422);
        }

        $query = StockNotification::where('product_id', $request->product_id)
            ->where('product_option_id', $request->product_option_id)
            ->where('notified', false);

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('email', $request->email);
        }

        $query->delete();

        return Response::success(__('Notification subscription removed'));
    }

    /**
     * Check if user is subscribed for a product.
     */
    public function check(Request $request, int $productId)
    {
        $productOptionId = $request->query('product_option_id');

        $query = StockNotification::where('product_id', $productId)
            ->where('notified', false);

        if ($productOptionId) {
            $query->where('product_option_id', $productOptionId);
        }

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            return Response::success(__('Check status'), ['is_subscribed' => false]);
        }

        return Response::success(__('Check status'), [
            'is_subscribed' => $query->exists(),
        ]);
    }
}
