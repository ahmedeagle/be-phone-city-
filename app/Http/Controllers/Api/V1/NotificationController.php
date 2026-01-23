<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller
{
    use PaginatesResponses;

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $user->notifications();

        if ($request->boolean('unread_only')) {
            $query = $user->unreadNotifications();
        }

        $data = $this->paginateData($query);
        $notifications = NotificationResource::collection($data['data']);

        return Response::success(
            __('Notifications fetched successfully'),
            $notifications,
            200,
            $data['pagination']
        );
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        return Response::success(
            __('Unread count fetched successfully'),
            ['unread_count' => Auth::user()->unreadNotifications()->count()]
        );
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return Response::success(__('Notification marked as read'));
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return Response::success(__('All notifications marked as read'));
    }

    /**
     * Delete a notification
     */
    public function destroy(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();

        return Response::success(__('Notification deleted successfully'));
    }

    /**
     * Delete all notifications
     */
    public function clearAll()
    {
        Auth::user()->notifications()->delete();

        return Response::success(__('All notifications cleared successfully'));
    }
}
