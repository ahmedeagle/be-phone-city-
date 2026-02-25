<?php

namespace App\Services;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Exceptions\RateLimitException;
use OpenAI\Exceptions\ErrorException;

class ChatbotService
{
    protected array $tools = [];
    protected array $availableTools = [];

    public function __construct()
    {
        $this->initializeTools();
    }

    /**
     * Process a chat message and return AI response
     */
    public function chat(
        string $message,
        ?User $user = null,
        ?string $sessionId = null,
        string $language = 'ar'
    ): array {
        // Sanitize input message
        $message = strip_tags($message);
        $message = trim($message);

        if (empty($message)) {
            throw new \InvalidArgumentException('Message cannot be empty');
        }

        // Get or create conversation
        $conversation = $this->getOrCreateConversation($sessionId, $user);

        // Store user message
        $this->storeMessage($conversation, 'user', $message);

        // Build conversation history
        $messages = $this->buildMessages($conversation, $language);

        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];
        try {
            // Call OpenAI API with function calling
            $response = $this->callOpenAI([
                'model' => $this->getModel(),
                'messages' => $messages,
                'tools' => $this->tools,
                'tool_choice' => 'auto',
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            $assistantMessage = $response->choices[0]->message;
            $toolCalls = $assistantMessage->toolCalls ?? [];

            // Handle tool calls if any
            if (!empty($toolCalls)) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $assistantMessage->content ?? '',
                    'tool_calls' => $toolCalls,
                ];

                // Execute tools and collect results
                foreach ($toolCalls as $toolCall) {
                    $toolResult = $this->executeTool(
                        $toolCall->function->name,
                        json_decode($toolCall->function->arguments, true),
                        $user
                    );

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall->id,
                        'content' => json_encode($toolResult),
                    ];
                }

                // Get final response with tool results
                $response = $this->callOpenAI([
                    'model' => $this->getModel(),
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1000,
                ]);

                $finalMessage = $response->choices[0]->message->content;
            } else {
                $finalMessage = $assistantMessage->content;
            }

            // Store assistant response
            $this->storeMessage(
                $conversation,
                'assistant',
                $finalMessage,
                !empty($toolCalls) ? $toolCalls : null
            );

            // Update conversation activity
            $conversation->updateActivity();

            return [
                'message' => $finalMessage,
                'session_id' => $conversation->session_id,
                'conversation_id' => $conversation->id,
            ];
        } catch (\Exception $e) {
            Log::error('Chatbot error: ' . $e->getMessage(), [
                'user_id' => $user?->id,
                'message' => $message,
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw with proper error message
            throw $e;
        }
    }

    /**
     * Call OpenAI API with retry logic for rate limits
     */
    protected function callOpenAI(array $params, int $maxRetries = 3): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                return OpenAI::chat()->create($params);
            } catch (RateLimitException $e) {
                // If this was the last attempt, throw error
                if ($attempt >= $maxRetries) {
                    Log::error('OpenAI rate limit exceeded after retries', [
                        'attempts' => $attempt,
                        'max_retries' => $maxRetries,
                    ]);

                    throw new \Exception(
                        'The service is currently busy. Please try again in a few moments.',
                        429
                    );
                }

                // Extract retry-after from response headers
                $retryAfter = $this->extractRetryAfter($e->response);
                
                // If Retry-After is very long (more than 60 seconds), don't retry
                if ($retryAfter && $retryAfter > 60) {
                    Log::error('OpenAI rate limit with long retry-after, aborting', [
                        'retry_after' => $retryAfter,
                        'attempt' => $attempt,
                    ]);
                    
                    throw new \Exception(
                        'The service is currently busy. Please try again in a few minutes.',
                        429
                    );
                }
                
                // Use attempt - 1 for exponential backoff since we already incremented
                $waitTime = $retryAfter ?? min(pow(2, $attempt - 1), 60); // Exponential backoff, max 60 seconds

                Log::warning('OpenAI rate limit hit, retrying', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'wait_time' => $waitTime,
                    'retry_after_header' => $retryAfter,
                ]);

                sleep($waitTime);
                continue;
            } catch (ErrorException $e) {
                $lastException = $e;
                $statusCode = $e->getStatusCode();
                $errorMessage = $e->getErrorMessage();

                // Handle authentication errors
                if ($statusCode === 401) {
                    Log::error('OpenAI authentication error', ['error' => $errorMessage]);
                    throw new \Exception(
                        'Authentication error. Please check your API configuration.',
                        401
                    );
                }

                // Handle server errors with retry
                if ($statusCode >= 500 && $statusCode < 600) {
                    // If this was the last attempt, throw error
                    if ($attempt >= $maxRetries) {
                        Log::error('OpenAI server error after retries', [
                            'attempts' => $attempt,
                            'max_retries' => $maxRetries,
                            'status_code' => $statusCode,
                            'error' => $errorMessage,
                        ]);

                        throw new \Exception(
                            'The AI service is temporarily unavailable. Please try again later.',
                            503
                        );
                    }

                    // Wait before retrying server errors (use attempt - 1 since we already incremented)
                    sleep(min(($attempt - 1) * 2, 10));
                    continue;
                }

                // For other errors (400, 403, etc.), throw immediately
                Log::error('OpenAI API error', [
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'error_type' => $e->getErrorType(),
                    'error_code' => $e->getErrorCode(),
                ]);

                throw new \Exception(
                    'An error occurred while processing your request. Please try again.',
                    500
                );
            } catch (\Exception $e) {
                // Re-throw if it's not an OpenAI exception
                if (!($e instanceof RateLimitException || $e instanceof ErrorException)) {
                    throw $e;
                }
                $lastException = $e;
            }
        }

        // If we get here, all retries failed
        throw $lastException ?? new \Exception('Failed to connect to AI service');
    }

    /**
     * Extract retry-after time from response headers
     */
    protected function extractRetryAfter($response): ?int
    {
        try {
            if ($response && method_exists($response, 'getHeader')) {
                $retryAfterHeader = $response->getHeader('Retry-After');
                if (!empty($retryAfterHeader)) {
                    return (int) $retryAfterHeader[0];
                }
            }
        } catch (\Exception $ex) {
            // Ignore errors extracting retry-after
        }

        return null;
    }

    /**
     * Get conversation history
     */
    public function getHistory(string $sessionId, ?User $user = null): array
    {
        $query = ChatbotConversation::where('session_id', $sessionId);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id');
        }

        $conversation = $query->first();

        if (!$conversation) {
            return ['messages' => []];
        }

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at->toIso8601String(),
            ])
            ->toArray();

        return [
            'messages' => $messages,
            'session_id' => $conversation->session_id,
        ];
    }

    /**
     * Clear conversation history
     */
    public function clearHistory(string $sessionId, ?User $user = null): void
    {
        $query = ChatbotConversation::where('session_id', $sessionId);

        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id');
        }

        $query->delete();
    }

    /**
     * Get or create conversation
     */
    protected function getOrCreateConversation(?string $sessionId, ?User $user): ChatbotConversation
    {
        if ($sessionId) {
            $query = ChatbotConversation::where('session_id', $sessionId);

            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereNull('user_id');
            }

            $conversation = $query->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return ChatbotConversation::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId ?? Str::uuid()->toString(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Store a message in the conversation
     */
    protected function storeMessage(
        ChatbotConversation $conversation,
        string $role,
        string $content,
        ?array $toolCalls = null
    ): ChatbotMessage {
        return ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'tool_calls' => $toolCalls,
        ]);
    }

    /**
     * Build messages array for OpenAI API
     */
    protected function buildMessages(ChatbotConversation $conversation, string $language): array
    {
        $systemMessage = $this->getSystemMessage($language, $conversation->user);

        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
        ];

        // Get recent conversation history (last 10 messages)
        $recentMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse();

        foreach ($recentMessages as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        return $messages;
    }

    /**
     * Get system message based on language and user context
     */
    protected function getSystemMessage(string $language, ?User $user): string
    {
        $isAuthenticated = $user !== null;

        if ($language === 'ar') {
            $base = "أنت مساعد ذكي لمتجر City Phone، متجر إلكتروني متخصص في بيع الهواتف المحمولة والإكسسوارات.\n\n";
            $base .= "مهامك:\n";
            $base .= "- الإجابة على أسئلة العملاء حول المنتجات والفئات والعروض\n";
            $base .= "- مساعدة العملاء في العثور على المنتجات المناسبة\n";
            $base .= "- تقديم معلومات عن الصفحات والمحتوى المتاح\n";

            if ($isAuthenticated) {
                $base .= "- مساعدة العميل في إضافة المنتجات إلى السلة أو المفضلة\n";
                $base .= "- إنشاء تذاكر الدعم الفني\n";
                $base .= "- عرض معلومات الطلبات والتذاكر الخاصة بالعميل\n";
            }

            $base .= "\nالقواعد:\n";
            $base .= "- كن مهذباً ومحترفاً دائماً\n";
            $base .= "- قدم إجابات دقيقة ومختصرة\n";
            $base .= "- إذا لم تكن متأكداً من شيء، اعترف بذلك\n";
            $base .= "- لا تخترع معلومات غير موجودة\n";

            if (!$isAuthenticated) {
                $base .= "- العميل غير مسجل دخول، لا يمكنه إجراء عمليات تتطلب تسجيل الدخول\n";
            }

            return $base;
        } else {
            $base = "You are an intelligent assistant for City Phone, an e-commerce store specialized in selling mobile phones and accessories.\n\n";
            $base .= "Your tasks:\n";
            $base .= "- Answer customer questions about products, categories, and offers\n";
            $base .= "- Help customers find suitable products\n";
            $base .= "- Provide information about available pages and content\n";

            if ($isAuthenticated) {
                $base .= "- Help customers add products to cart or favorites\n";
                $base .= "- Create support tickets\n";
                $base .= "- Display customer orders and tickets information\n";
            }

            $base .= "\nRules:\n";
            $base .= "- Always be polite and professional\n";
            $base .= "- Provide accurate and concise answers\n";
            $base .= "- If you're not sure about something, admit it\n";
            $base .= "- Don't make up information that doesn't exist\n";

            if (!$isAuthenticated) {
                $base .= "- Customer is not logged in, cannot perform actions requiring authentication\n";
            }

            return $base;
        }
    }

    /**
     * Initialize available tools
     */
    protected function initializeTools(): void
    {
        // Register public tools
        $this->registerTool(
            \App\Ai\Tools\SearchCatalogTool::getName(),
            \App\Ai\Tools\SearchCatalogTool::class,
            \App\Ai\Tools\SearchCatalogTool::getDefinition()
        );

        $this->registerTool(
            \App\Ai\Tools\GetPageContentTool::getName(),
            \App\Ai\Tools\GetPageContentTool::class,
            \App\Ai\Tools\GetPageContentTool::getDefinition()
        );

        // Register authenticated tools
        $this->registerTool(
            \App\Ai\Tools\CreateTicketTool::getName(),
            \App\Ai\Tools\CreateTicketTool::class,
            \App\Ai\Tools\CreateTicketTool::getDefinition()
        );

        $this->registerTool(
            \App\Ai\Tools\AddToCartTool::getName(),
            \App\Ai\Tools\AddToCartTool::class,
            \App\Ai\Tools\AddToCartTool::getDefinition()
        );

        $this->registerTool(
            \App\Ai\Tools\AddToFavoriteTool::getName(),
            \App\Ai\Tools\AddToFavoriteTool::class,
            \App\Ai\Tools\AddToFavoriteTool::getDefinition()
        );

        $this->registerTool(
            \App\Ai\Tools\GetMyTicketsTool::getName(),
            \App\Ai\Tools\GetMyTicketsTool::class,
            \App\Ai\Tools\GetMyTicketsTool::getDefinition()
        );

        $this->registerTool(
            \App\Ai\Tools\GetMyOrdersTool::getName(),
            \App\Ai\Tools\GetMyOrdersTool::class,
            \App\Ai\Tools\GetMyOrdersTool::getDefinition()
        );
    }

    /**
     * Execute a tool function
     */
    protected function executeTool(string $toolName, array $arguments, ?User $user): array
    {
        if (!isset($this->availableTools[$toolName])) {
            Log::warning("Tool not found: {$toolName}", [
                'user_id' => $user?->id,
                'arguments' => $arguments,
            ]);
            return ['error' => 'Tool not found'];
        }

        // Log tool invocation (without sensitive data)
        Log::info("Tool invoked: {$toolName}", [
            'user_id' => $user?->id,
            'tool' => $toolName,
            'has_arguments' => !empty($arguments),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $toolClass = $this->availableTools[$toolName];
            $tool = new $toolClass();

            $result = $tool->execute($arguments, $user);

            // Log successful execution
            if (isset($result['success']) && $result['success']) {
                Log::info("Tool executed successfully: {$toolName}", [
                    'user_id' => $user?->id,
                    'tool' => $toolName,
                ]);
            } else {
                Log::warning("Tool execution returned error: {$toolName}", [
                    'user_id' => $user?->id,
                    'tool' => $toolName,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Tool execution exception: {$toolName}", [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
                'tool' => $toolName,
                'trace' => $e->getTraceAsString(),
            ]);

            return ['error' => 'Tool execution failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get the OpenAI model to use
     */
    protected function getModel(): string
    {
        // Check environment variable first, then config, then use default
        return env('OPENAI_CHATBOT_MODEL', config('openai.chatbot_model', 'gpt-3.5-turbo'));
    }

    /**
     * Register a tool
     */
    public function registerTool(string $name, string $class, array $definition): void
    {
        $this->availableTools[$name] = $class;
        $this->tools[] = [
            'type' => 'function',
            'function' => $definition,
        ];
    }
}
