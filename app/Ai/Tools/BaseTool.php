<?php

namespace App\Ai\Tools;

use App\Models\User;

abstract class BaseTool
{
    /**
     * Execute the tool with given arguments
     */
    abstract public function execute(array $arguments, ?User $user): array;

    /**
     * Get the tool definition for OpenAI function calling
     */
    abstract public static function getDefinition(): array;

    /**
     * Get the tool name
     */
    abstract public static function getName(): string;

    /**
     * Check if user is authenticated
     */
    protected function requiresAuth(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * Return error response
     */
    protected function error(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }

    /**
     * Return success response
     */
    protected function success(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }
}
