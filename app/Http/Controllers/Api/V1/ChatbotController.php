<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chatbot\ChatRequest;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function __construct(
        protected ChatbotService $chatbotService
    ) {}

    /**
     * Handle chat message from user
     */
    public function chat(ChatRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $message = $request->input('message');
            $sessionId = $request->input('session_id');
            $language = $request->input('language', 'ar');

            $response = $this->chatbotService->chat(
                message: $message,
                user: $user,
                sessionId: $sessionId,
                language: $language
            );

            return response()->success(
                message: __('Chat response generated successfully'),
                data: $response
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            $errorMessage = $e->getMessage();

            // Use the exception message if it's user-friendly, otherwise use generic message
            $userMessage = match (true) {
                $statusCode === 429 => __('The service is currently busy. Please try again in a few moments.'),
                $statusCode === 401 => __('Authentication error. Please check your API configuration.'),
                $statusCode === 503 => __('The AI service is temporarily unavailable. Please try again later.'),
                default => __('An error occurred while processing your message. Please try again.'),
            };

            Log::error('Chatbot error: ' . $errorMessage, [
                'user_id' => $request->user()?->id,
                'message' => $request->input('message'),
                'status_code' => $statusCode,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->error(
                message: $userMessage,
                code: $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500
            );
        }
    }

    /**
     * Get conversation history
     */
    public function history(string $sessionId): JsonResponse
    {
        try {
            $user = request()->user();

            $history = $this->chatbotService->getHistory($sessionId, $user);

            return response()->success(
                message: __('Conversation history retrieved successfully'),
                data: $history
            );
        } catch (\Exception $e) {
            Log::error('Chatbot history error: ' . $e->getMessage());

            return response()->error(
                message: __('An error occurred while retrieving conversation history'),
                code: 500
            );
        }
    }

    /**
     * Clear conversation history
     */
    public function clear(string $sessionId): JsonResponse
    {
        try {
            $user = request()->user();

            $this->chatbotService->clearHistory($sessionId, $user);

            return response()->success(
                message: __('Conversation history cleared successfully')
            );
        } catch (\Exception $e) {
            Log::error('Chatbot clear error: ' . $e->getMessage());

            return response()->error(
                message: __('An error occurred while clearing conversation history'),
                code: 500
            );
        }
    }
}
