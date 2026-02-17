<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        try {
            // Check if unverified user exists with same email or phone
            $existingUserByEmail = User::where('email', $request->email)
                ->whereNull('email_verified_at')
                ->first();
            
            $existingUserByPhone = User::where('phone', $request->phone)
                ->whereNull('email_verified_at')
                ->first();

            // If unverified user exists, delete it to allow re-registration
            if ($existingUserByEmail) {
                // Delete related verification codes first
                $existingUserByEmail->verificationCodes()->delete();
                $existingUserByEmail->delete();
            }

            // Handle phone separately if it's a different user
            if ($existingUserByPhone && (!$existingUserByEmail || $existingUserByPhone->id !== $existingUserByEmail->id)) {
                $existingUserByPhone->verificationCodes()->delete();
                $existingUserByPhone->delete();
            }

            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Send verification code
            $user->sendVerificationCode('email_verification');

            return Response::success(
                __('Account created successfully'),
                [
                    'user' => new UserResource($user),
                    'needs_verification' => true,
                ],
                201
            );
        } catch (\Exception $e) {
            return Response::error(__('Registration failed'), null, 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return Response::error(__('Invalid credentials'), null, 401);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            return Response::error(__('Email not verified'), [
                'needs_verification' => true,
            ], 403);
        }

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return Response::success(
            __('Login successful'),
            [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        );
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return Response::success(__('Logged out successfully'));
    }

    /**
     * Verify email code
     */
    public function verifyCode(VerifyCodeRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user->verifyCode($request->code, 'email_verification')) {
            return Response::error(__('Invalid or expired code'), null, 400);
        }

        // Create token after verification
        $token = $user->createToken('auth_token')->plainTextToken;

        return Response::success(
            __('Email verified successfully'),
            [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        );
    }

    /**
     * Resend verification code
     */
    public function resendCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Rate limiting: max 3 attempts per hour
        $key = 'resend-code:' . $request->email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return Response::error(
                __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
                null,
                429
            );
        }

        RateLimiter::hit($key, 3600); // 1 hour

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return Response::error(__('Email already verified'), null, 400);
        }

        $user->sendVerificationCode('email_verification');

        return Response::success(__('Verification code sent'));
    }

    /**
     * Forgot password - send reset code
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Rate limiting
        $key = 'forgot-password:' . $request->email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return Response::error(
                __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
                null,
                429
            );
        }

        RateLimiter::hit($key, 3600);

        $user->sendVerificationCode('password_reset');

        return Response::success(__('Password reset code sent to your email'));
    }

    /**
     * Reset password using code
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Verify code
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->where('type', 'password_reset')
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verificationCode) {
            return Response::error(__('Invalid or expired code'), null, 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Mark code as used
        $verificationCode->update(['used' => true]);

        return Response::success(__('Password reset successfully'));
    }

    /**
     * Social login (Google, Facebook, Apple)
     */
    public function socialLogin(Request $request, string $provider)
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        if (!in_array($provider, ['google', 'facebook', 'apple'])) {
            return Response::error(__('Invalid provider'), null, 400);
        }

        try {
            // Get user info from provider
            $socialUser = Socialite::driver($provider)->userFromToken($request->access_token);

            // Find or create user
            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$user) {
                // Check if email already exists
                $existingUser = User::where('email', $socialUser->getEmail())->first();

                if ($existingUser) {
                    // Link social account to existing user
                    $existingUser->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'email_verified_at' => $existingUser->email_verified_at ?? now(),
                    ]);
                    $user = $existingUser;
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'phone' => $socialUser->phone ?? 'N/A', // Handle missing phone
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'email_verified_at' => now(), // Social login auto-verifies
                        'password' => Hash::make(uniqid()), // Random password
                    ]);
                }
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return Response::success(
                __('Login successful'),
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            );
        } catch (\Exception $e) {
            return Response::error(__('Social login failed: ') . $e->getMessage(), null, 500);
        }
    }
}
