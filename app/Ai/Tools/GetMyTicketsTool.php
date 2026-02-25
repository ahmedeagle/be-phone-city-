<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use App\Models\User;

class GetMyTicketsTool extends BaseTool
{
    public static function getName(): string
    {
        return 'get_my_tickets';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Get user\'s support tickets. Requires authentication. Use this when user wants to check their ticket status or history.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'enum' => ['pending', 'in_progress', 'resolved', 'closed', 'all'],
                        'description' => 'Filter by ticket status (default: all)',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of tickets to return (default: 10, max: 50)',
                        'minimum' => 1,
                        'maximum' => 50,
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        if (!$this->requiresAuth($user)) {
            return $this->error('Authentication required to view tickets');
        }

        try {
            $status = $arguments['status'] ?? 'all';
            $limit = min($arguments['limit'] ?? 10, 50);

            $query = Ticket::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $tickets = $query->limit($limit)->get();

            $ticketsData = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'type' => $ticket->type,
                    'type_label' => $ticket->type_label,
                    'created_at' => $ticket->created_at->toDateTimeString(),
                    'resolved_at' => $ticket->resolved_at?->toDateTimeString(),
                ];
            })->toArray();

            return $this->success([
                'tickets' => $ticketsData,
                'count' => count($ticketsData),
                'total' => Ticket::where('user_id', $user->id)->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve tickets: ' . $e->getMessage());
        }
    }
}
