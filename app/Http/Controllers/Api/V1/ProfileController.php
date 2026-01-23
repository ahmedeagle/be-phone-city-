<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show()
    {
        $user = Auth::user();

        return Response::success(
            __('Profile fetched successfully'),
            new UserResource($user)
        );
    }

    /**
     * Update authenticated user's profile
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        $updateData = [];

        if ($request->filled('name')) {
            $updateData['name'] = $request->name;
        }

        if ($request->filled('email')) {
            // Check if email is being changed
            if ($user->email !== $request->email) {
                // Check if new email already exists
                $emailExists = User::where('email', $request->email)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($emailExists) {
                    return Response::error(__('Email already taken'), null, 422);
                }

                $updateData['email'] = $request->email;
                // Reset email verification if email is changed
                // $updateData['email_verified_at'] = null;
            }
        }

        if ($request->filled('phone')) {
            $updateData['phone'] = $request->phone;
        }

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if (!empty($updateData)) {
            $user->update($updateData);
            $user->refresh();
        }

        return Response::success(
            __('Profile updated successfully'),
            new UserResource($user)
        );
    }
}

