<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class InvoiceController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all invoices for authenticated user
     */
    public function index(Request $request)
    {
        $query = Invoice::whereHas('order', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with([
                'order' => function ($query) {
                    $query->with([
                        'items.product.images',
                        'items.product.categories',
                        'items.productOption.images',
                        'location',
                        'paymentMethod',
                        'discountCode'
                    ]);
                }
            ])
            ->orderBy('created_at', 'desc');

        // Filter by invoice type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $data = $this->paginateData($query);
        $invoices = InvoiceResource::collection($data['data']);

        return Response::success(
            __('Invoices fetched successfully'),
            $invoices,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single invoice with full details
     */
    public function show(int $id)
    {
        $invoice = Invoice::whereHas('order', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->with([
                'order' => function ($query) {
                    $query->with([
                        'items.product.images',
                        'items.product.categories',
                        'items.productOption.images',
                        'location',
                        'paymentMethod',
                        'discountCode',
                        'user'
                    ]);
                }
            ])
            ->findOrFail($id);

        return Response::success(
            __('Invoice fetched successfully'),
            new InvoiceResource($invoice),
            200
        );
    }
}

