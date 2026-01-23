<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{
    protected function successResponse($data = null, string $message = 'تم بنجاح', int $status = 200, array $pagination = null): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => __($message),
            'data' => $data,
        ];

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(string $message = 'حدث خطأ', $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => __($message),
            'errors' => $errors,
        ], $status);
    }

    protected function validationErrorResponse(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        return $this->errorResponse(
            'البيانات المدخلة غير صحيحة',
            $validator->errors(),
            422
        );
    }

    protected function notFoundResponse(string $message = 'العنصر غير موجود'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    protected function unauthorizedResponse(string $message = 'غير مصرح لك بالوصول'): JsonResponse
    {
        return $this->errorResponse($message, null, 403);
    }

}
