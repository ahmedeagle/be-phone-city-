<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_email_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationCode = VerificationCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'type' => 'email_verification',
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-code', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                ],
            ]);

        // Check that user is verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // Check that code is marked as used
        $verificationCode->refresh();
        $this->assertTrue($verificationCode->used);
    }

    public function test_user_cannot_verify_with_expired_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        VerificationCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'type' => 'email_verification',
            'expires_at' => now()->subMinutes(1),
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-code', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
            ]);
    }

    public function test_user_cannot_verify_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-code', [
            'email' => $user->email,
            'code' => '999999',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
            ]);
    }
}
