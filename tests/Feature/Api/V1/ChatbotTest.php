<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ChatbotConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test data
        Category::factory()->create([
            'name_en' => 'Smartphones',
            'name_ar' => 'الهواتف الذكية',
        ]);

        Product::factory()->create([
            'name_en' => 'iPhone 15 Pro',
            'name_ar' => 'آيفون 15 برو',
            'main_price' => 5000,
            'quantity' => 10,
        ]);
    }

    /** @test */
    public function guest_can_send_chat_message()
    {
        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Hello, I need help',
            'language' => 'en',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'message',
                    'session_id',
                    'conversation_id',
                ],
            ]);

        $this->assertDatabaseHas('chatbot_conversations', [
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'role' => 'user',
            'content' => 'Hello, I need help',
        ]);
    }

    /** @test */
    public function authenticated_user_can_send_chat_message()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/chatbot/chat', [
                'message' => 'Show me my orders',
                'language' => 'en',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'message',
                    'session_id',
                    'conversation_id',
                ],
            ]);

        $this->assertDatabaseHas('chatbot_conversations', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function chat_message_validation_fails_with_empty_message()
    {
        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function chat_message_validation_fails_with_too_long_message()
    {
        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => str_repeat('a', 2001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function conversation_continues_with_same_session_id()
    {
        $firstResponse = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Hello',
        ]);

        $sessionId = $firstResponse->json('data.session_id');

        $secondResponse = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Tell me more',
            'session_id' => $sessionId,
        ]);

        $secondResponse->assertStatus(200);

        $this->assertEquals(
            $sessionId,
            $secondResponse->json('data.session_id')
        );

        // Should have 2 user messages in the same conversation
        $conversation = ChatbotConversation::where('session_id', $sessionId)->first();
        $this->assertEquals(2, $conversation->messages()->where('role', 'user')->count());
    }

    /** @test */
    public function authenticated_user_can_get_conversation_history()
    {
        $user = User::factory()->create();

        $chatResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/chatbot/chat', [
                'message' => 'Hello',
            ]);

        $sessionId = $chatResponse->json('data.session_id');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/chatbot/history/{$sessionId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'messages',
                    'session_id',
                ],
            ]);
    }

    /** @test */
    public function user_cannot_access_another_users_conversation_history()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $chatResponse = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/chatbot/chat', [
                'message' => 'Hello',
            ]);

        $sessionId = $chatResponse->json('data.session_id');

        $response = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/v1/chatbot/history/{$sessionId}");

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.messages'));
    }

    /** @test */
    public function authenticated_user_can_clear_conversation_history()
    {
        $user = User::factory()->create();

        $chatResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/chatbot/chat', [
                'message' => 'Hello',
            ]);

        $sessionId = $chatResponse->json('data.session_id');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/chatbot/clear/{$sessionId}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('chatbot_conversations', [
            'session_id' => $sessionId,
        ]);
    }

    /** @test */
    public function rate_limiting_works_for_guests()
    {
        // Make 21 requests (limit is 20 for guests)
        for ($i = 0; $i < 21; $i++) {
            $response = $this->postJson('/api/v1/chatbot/chat', [
                'message' => "Message {$i}",
            ]);

            if ($i < 20) {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }

    /** @test */
    public function html_tags_are_stripped_from_messages()
    {
        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => '<script>alert("xss")</script>Hello',
        ]);

        $response->assertStatus(200);

        // Verify message was sanitized in database
        $this->assertDatabaseHas('chatbot_messages', [
            'role' => 'user',
            'content' => 'Hello',
        ]);

        $this->assertDatabaseMissing('chatbot_messages', [
            'content' => '<script>alert("xss")</script>Hello',
        ]);
    }

    /** @test */
    public function language_parameter_is_validated()
    {
        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Hello',
            'language' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['language']);
    }

    /** @test */
    public function conversation_activity_is_updated()
    {
        $firstResponse = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Hello',
        ]);

        $sessionId = $firstResponse->json('data.session_id');
        $conversation = ChatbotConversation::where('session_id', $sessionId)->first();
        $firstActivity = $conversation->last_activity_at;

        sleep(1);

        $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Another message',
            'session_id' => $sessionId,
        ]);

        $conversation->refresh();
        $this->assertNotEquals($firstActivity, $conversation->last_activity_at);
    }
}
