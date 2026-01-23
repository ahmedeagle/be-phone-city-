<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CertificateController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all certificates
     */
    public function index(Request $request)
    {
        $certificates = Certificate::query()->orderBy('created_at', 'desc');

        $data = $this->paginateData($certificates);
        $certificates = CertificateResource::collection($data['data']);

        return Response::success(
            __('Certificates fetched successfully'),
            $certificates,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single certificate
     */
    public function show(Request $request, int $id)
    {
        $certificate = Certificate::findOrFail($id);

        return Response::success(
            __('Certificate fetched successfully'),
            new CertificateResource($certificate),
            200
        );
    }
}

