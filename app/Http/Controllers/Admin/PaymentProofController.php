<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PaymentProofController extends Controller
{
    /**
     * Serve payment proof file with authorization check
     *
     * @param PaymentTransaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentTransaction $transaction)
    {
        // Check if admin is authenticated (using admin guard like Filament)
        if (!Auth::guard('admin')->check()) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        $user = Auth::guard('admin')->user();

        // Check if user has permission to view payment transactions
        if (!$user || !Gate::forUser($user)->allows('payment_transactions.show')) {
            abort(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        // Check if transaction has payment proof
        if (!$transaction->hasPaymentProof()) {
            abort(Response::HTTP_NOT_FOUND, 'Payment proof not found');
        }

        // Get file path
        $filePath = $transaction->payment_proof_path;

        // Check if file exists
        if (!Storage::disk('local')->exists($filePath)) {
            abort(Response::HTTP_NOT_FOUND, 'File not found');
        }

        // Get full path to file
        $fullPath = Storage::disk('local')->path($filePath);

        // Get file mime type
        $mimeType = File::mimeType($fullPath) ?: 'application/octet-stream';

        // Return file response
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ]);
    }
}
