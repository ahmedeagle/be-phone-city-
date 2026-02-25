<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class CreateTicketTool extends BaseTool
{
    public static function getName(): string
    {
        return 'create_ticket';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Create a support ticket for the user. Requires authentication. Use this when user wants to report an issue, ask for support, or submit a complaint.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'subject' => [
                        'type' => 'string',
                        'description' => 'Ticket subject/title',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Detailed description of the issue or request',
                    ],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['support', 'complaint', 'inquiry', 'technical', 'billing', 'other'],
                        'description' => 'Type of ticket',
                    ],
                    'priority' => [
                        'type' => 'string',
                        'enum' => ['low', 'medium', 'high', 'urgent'],
                        'description' => 'Priority level (default: medium)',
                    ],
                ],
                'required' => ['subject', 'description', 'type'],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        if (!$this->requiresAuth($user)) {
            return $this->error('Authentication required to create a ticket');
        }

        $validator = Validator::make($arguments, [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:support,complaint,inquiry,technical,billing,other',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        try {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'subject' => $arguments['subject'],
                'description' => $arguments['description'],
                'type' => $arguments['type'],
                'priority' => $arguments['priority'] ?? 'medium',
                'status' => 'pending',
            ]);

            return $this->success([
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'type' => $ticket->type,
                'created_at' => $ticket->created_at->toDateTimeString(),
                'message' => 'Ticket created successfully. Our support team will respond soon.',
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to create ticket: ' . $e->getMessage());
        }
    }
}
