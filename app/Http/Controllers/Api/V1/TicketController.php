<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\NotificationService;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class TicketController extends Controller
{
    use PaginatesResponses;

    /**
     * Get authenticated user's tickets only
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required'),
                null,
                401
            );
        }

        $query = Ticket::with(['user', 'admin', 'images', 'replies.user', 'replies.admin'])
            ->where('user_id', $userId);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by ticket number or subject
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSorts = ['created_at', 'updated_at', 'status', 'priority'];
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'priority') {
                $query->orderByPriority();
                if ($sortOrder === 'asc') {
                    $query->orderBy('priority', 'desc');
                }
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $data = $this->paginateData($query);
        $tickets = TicketResource::collection($data['data']);

        return Response::success(
            __('Your tickets fetched successfully'),
            $tickets,
            200,
            $data['pagination']
        );
    }

    /**
     * Get single ticket (only user's own tickets)
     */
    public function show(int $id)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required'),
                null,
                401
            );
        }

        $ticket = Ticket::with(['user', 'admin', 'images', 'replies.user', 'replies.admin'])
            ->where('user_id', $userId)
            ->findOrFail($id);

        return Response::success(
            __('Ticket fetched successfully'),
            new TicketResource($ticket)
        );
    }

    /**
     * Create a new ticket (guest or authenticated user)
     */
    public function store(StoreTicketRequest $request)
    {
        // Generate subject from message (first 50 characters)
        $subject = mb_substr(strip_tags($request->message), 0, 50);
        if (mb_strlen($request->message) > 50) {
            $subject .= '...';
        }

        // Resolve user from Sanctum guard even without middleware
        // (store route is public for guests, but authenticated users should be linked)
        $userId = auth('sanctum')->id();

        $ticket = Ticket::create([
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $subject,
            'description' => $request->message,
            'status' => Ticket::STATUS_PENDING,
            'priority' => Ticket::PRIORITY_MEDIUM,
            'type' => Ticket::TYPE_SUPPORT,
        ]);

        // Load relationships
        $ticket->load(['user', 'admin', 'images', 'replies']);

        // Send notifications to admins and user
        try {
            app(NotificationService::class)->notifyTicketCreated($ticket);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Ticket notification failed: ' . $e->getMessage());
        }

        return Response::success(
            __('Ticket created successfully'),
            new TicketResource($ticket),
            201
        );
    }

    /**
     * Update a ticket (only owner can update)
     */
    public function update(UpdateTicketRequest $request, int $id)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required'),
                null,
                401
            );
        }

        $ticket = Ticket::where('user_id', $userId)
            ->findOrFail($id);

        $updateData = [];

        if ($request->filled('subject')) {
            $updateData['subject'] = $request->subject;
        }

        if ($request->filled('description')) {
            $updateData['description'] = $request->description;
        }

        if (!empty($updateData)) {
            $ticket->update($updateData);
            $ticket->refresh();
        }

        $ticket->load(['user', 'admin', 'images', 'replies.user', 'replies.admin']);

        return Response::success(
            __('Ticket updated successfully'),
            new TicketResource($ticket)
        );
    }

    /**
     * Add a reply to a ticket (authenticated user only)
     */
    public function reply(Request $request, int $id)
    {
        $userId = Auth::id();

        if (!$userId) {
            return Response::error(
                __('Authentication required'),
                null,
                401
            );
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $ticket = Ticket::where('user_id', $userId)
            ->findOrFail($id);

        // Don't allow replies on closed tickets
        if ($ticket->isClosed()) {
            return Response::error(
                __('Cannot reply to a closed ticket'),
                null,
                422
            );
        }

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'admin_id' => null,
            'message' => $request->message,
            'is_admin' => false,
        ]);

        // Reopen ticket if it was resolved
        if ($ticket->isResolved()) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        $ticket->touch();
        $reply->load(['user', 'admin']);

        // Notify admins about new reply
        try {
            app(NotificationService::class)->notifyTicketCreated($ticket);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Ticket reply notification failed: ' . $e->getMessage());
        }

        $ticket->load(['user', 'admin', 'images', 'replies.user', 'replies.admin']);

        return Response::success(
            __('Reply added successfully'),
            new TicketResource($ticket),
            201
        );
    }
}

