<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\ContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ContactRequestController extends Controller
{
    /**
     * Store a new contact request
     */
    public function store(StoreContactRequest $request)
    {
        $contactRequest = ContactRequest::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
        ]);

        return Response::success(
            __('Contact request submitted successfully'),
            [
                'id' => $contactRequest->id,
                'name' => $contactRequest->name,
                'email' => $contactRequest->email,
            ],
            201
        );
    }
}

