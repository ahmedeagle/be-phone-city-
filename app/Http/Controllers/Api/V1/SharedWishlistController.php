<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class SharedWishlistController extends Controller
{
    /**
     * Generate or return the share link for the authenticated user's wishlist.
     */
    public function generateLink()
    {
        $user = Auth::user();

        if (!$user->wishlist_share_token) {
            $user->wishlist_share_token = Str::random(32);
            $user->save();
        }

        $frontendUrl = config('app.frontend_url', config('app.url'));
        $shareUrl = rtrim($frontendUrl, '/') . '/ar/shared-wishlist/' . $user->wishlist_share_token;

        return Response::success(__('Wishlist share link generated'), [
            'share_token' => $user->wishlist_share_token,
            'share_url' => $shareUrl,
        ]);
    }

    /**
     * Regenerate a new share token (invalidates old link).
     */
    public function regenerateLink()
    {
        $user = Auth::user();
        $user->wishlist_share_token = Str::random(32);
        $user->save();

        $frontendUrl = config('app.frontend_url', config('app.url'));
        $shareUrl = rtrim($frontendUrl, '/') . '/ar/shared-wishlist/' . $user->wishlist_share_token;

        return Response::success(__('Wishlist share link regenerated'), [
            'share_token' => $user->wishlist_share_token,
            'share_url' => $shareUrl,
        ]);
    }

    /**
     * View a shared wishlist (public - no auth required).
     */
    public function show(string $token)
    {
        $user = User::where('wishlist_share_token', $token)->first();

        if (!$user) {
            return Response::error(__('Wishlist not found or link expired'), null, 404);
        }

        $favorites = Favorite::where('user_id', $user->id)
            ->with(['product.images', 'product.categories'])
            ->get();

        return Response::success(__('Shared wishlist'), [
            'owner_name' => $user->name,
            'items' => FavoriteResource::collection($favorites),
            'count' => $favorites->count(),
        ]);
    }
}
